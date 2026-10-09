<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;
use App\Models\DisclosureAttachment;
use App\Models\DisclosureTechnologyFile;
use App\Models\InnovationDisclosure;

class InnovationDisclosureController extends Controller
{
  private const STATUSES = ['submitted', 'under_review', 'revised_submission', 'returned', 'endorsed', 'filed'];
  private const TECHNOLOGY_NARRATIVES = [
    'abstract_summary',
    'technical_field',
    'background_summary',
    'invention_summary',
    'detailed_description',
    'examples_results',
  ];

  public function index(Request $request): View
  {
    $userDisclosures = $request->user()->innovationDisclosures();
    $statusCounts = $userDisclosures
      ->select('status', DB::raw('count(*) as total'))
      ->groupBy('status')
      ->pluck('total', 'status');

    return view('content.dashboard.inventor.inventor-disclosure-index', [
      'totalDisclosures' => $statusCounts->sum(),
      'statusCounts' => $statusCounts,
      'chartLabels' => array_map(fn(string $status) => str_replace('_', ' ', ucfirst($status)), self::STATUSES),
      'chartValues' => array_map(fn(string $status) => (int) ($statusCounts[$status] ?? 0), self::STATUSES),
    ]);
  }

  public function create(): View
  {
    return view('content.dashboard.inventor.inventor-disclosure-index', [
      'page' => 'form',
    ]);
  }

  public function submissions(Request $request): View
  {
    return view('content.dashboard.inventor.inventor-index', [
      'disclosures' => $request->user()
        ->innovationDisclosures()
        ->with(['attachments', 'inventors'])
        ->latest()
        ->paginate(10),
      'deletedDisclosures' => $request->user()
        ->innovationDisclosures()
        ->onlyTrashed()
        ->latest('deleted_at')
        ->paginate(10, ['*'], 'deletedPage'),
    ]);
  }

  public function showSubmission(Request $request, InnovationDisclosure $disclosure): View
  {
    $ownedDisclosure = $request->user()
      ->innovationDisclosures()
      ->with(['attachments.events', 'inventors', 'costItems', 'technologies.files'])
      ->findOrFail($disclosure->id);

    return view('content.dashboard.inventor.inventor-index', [
      'disclosures' => collect([$ownedDisclosure]),
      'detailView' => true,
    ]);
  }

  public function submissionStatusUpdates(Request $request)
  {
    return response()->json(
      $request->user()->innovationDisclosures()
        ->with('attachments:id,innovation_disclosure_id,type,needs_revision,revision_notes,is_latest_submission,version')
        ->latest()
        ->get(['id', 'status', 'review_notes', 'updated_at'])
    );
  }

  public function destroy(Request $request, InnovationDisclosure $disclosure): RedirectResponse
  {
    $ownedDisclosure = $request->user()->innovationDisclosures()->findOrFail($disclosure->id);
    $technologyTitle = $ownedDisclosure->technology_title;
    $ownedDisclosure->delete();

    return redirect()
      ->route('dashboard.submissions.index')
      ->with('toast_type', 'success')
      ->with('toast_message', "{$technologyTitle} was moved to Deleted submissions. Its data and files are retained.");
  }

  public function downloadAttachment(
    Request $request,
    InnovationDisclosure $disclosure,
    DisclosureAttachment $attachment
  ) {
    $ownedDisclosure = $request->user()->innovationDisclosures()->findOrFail($disclosure->id);
    $ownedAttachment = $ownedDisclosure->attachments()->findOrFail($attachment->id);

    abort_unless(Storage::disk('local')->exists($ownedAttachment->stored_path), 404);

    $stream = Storage::disk('local')->readStream($ownedAttachment->stored_path);

    abort_unless(is_resource($stream), 404);

    return response()->streamDownload(function () use ($stream): void {
      fpassthru($stream);
      fclose($stream);
    }, $ownedAttachment->original_name, [
      'Content-Type' => $ownedAttachment->mime_type,
    ]);
  }

  public function previewAttachment(
    Request $request,
    InnovationDisclosure $disclosure,
    DisclosureAttachment $attachment
  ): BinaryFileResponse {
    $ownedDisclosure = $request->user()->innovationDisclosures()->findOrFail($disclosure->id);
    $ownedAttachment = $ownedDisclosure->attachments()->findOrFail($attachment->id);
    $extension = strtolower(pathinfo($ownedAttachment->original_name, PATHINFO_EXTENSION));
    $inlineTypes = [
      'pdf' => 'application/pdf',
      'jpg' => 'image/jpeg',
      'jpeg' => 'image/jpeg',
      'png' => 'image/png',
    ];

    abort_unless(isset($inlineTypes[$extension]), 415);

    $disk = Storage::disk('local');
    abort_unless($disk->exists($ownedAttachment->stored_path), 404);

    return response()->file($disk->path($ownedAttachment->stored_path), [
      'Content-Type' => $inlineTypes[$extension],
      'X-Content-Type-Options' => 'nosniff',
    ])->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_INLINE,
      $ownedAttachment->original_name
    );
  }

  public function previewTechnologyFile(
    Request $request,
    InnovationDisclosure $disclosure,
    DisclosureTechnologyFile $file
  ): BinaryFileResponse {
    abort_unless(
      $request->user()->is_ip_head || $disclosure->user_id === $request->user()->id,
      403
    );
    $technology = $disclosure->technologies()
      ->whereHas('files', fn ($query) => $query->whereKey($file->id))
      ->firstOrFail();
    $ownedFile = $technology->files()->findOrFail($file->id);
    abort_unless(Storage::disk('local')->exists($ownedFile->stored_path), 404);

    return response()->file(Storage::disk('local')->path($ownedFile->stored_path), [
      'Content-Type' => 'application/pdf',
      'X-Content-Type-Options' => 'nosniff',
    ])->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_INLINE,
      $ownedFile->original_name
    );
  }

  public function storeAttachment(Request $request, InnovationDisclosure $disclosure): RedirectResponse
  {
    $ownedDisclosure = $request->user()->innovationDisclosures()->findOrFail($disclosure->id);
    abort_unless(
      in_array($ownedDisclosure->status, ['under_review', 'returned', 'revised_submission'], true),
      409,
      'Files cannot be changed until the IP Head reviews this submission.'
    );

    $attachments = $request->file('attachments', []);
    if ($request->hasFile('file')) {
      $legacyType = $request->validate([
        'type' => ['required', Rule::in(['drawings', 'methodology', 'assistance_form'])],
      ])['type'];
      $attachments[$legacyType] = $request->file('file');
      $request->files->set('attachments', $attachments);
    }

    $rules = [
      'attachments' => ['required', 'array:drawings,methodology,assistance_form,deed_assignment,sample_photo,additional_signed_sheet', 'min:1'],
    ];
    $messages = [];
    foreach (['drawings', 'methodology', 'assistance_form', 'deed_assignment', 'sample_photo', 'additional_signed_sheet'] as $type) {
      $rules["attachments.{$type}"] = $this->attachmentRules();
      $messages["attachments.{$type}.mimes"] = 'All uploaded files must be PDF documents.';
    }
    $validated = $request->validate($rules, $messages);

    $uploadedFiles = array_filter(
      $validated['attachments'],
      fn (mixed $file): bool => $file instanceof UploadedFile
    );
    if ($uploadedFiles === []) {
      $request->validate([
        'attachments' => ['required', 'array', 'min:1'],
      ]);
    }

    $updates = [];
    foreach ($uploadedFiles as $type => $file) {
      $attachment = $ownedDisclosure->attachments()->where('type', $type)->first();
      abort_unless($attachment?->needs_revision, 409, 'Only files marked for revision can be updated.');
      $updates[$type] = [
        'attachment' => $attachment,
        'file' => $file,
      ];
    }

    $newPaths = [];
    $newPathsByType = [];
    $oldPaths = [];
    try {
      foreach ($updates as $type => $update) {
        $newPath = $update['file']->store("innovation-disclosures/{$ownedDisclosure->id}/{$type}", 'local');
        if (! is_string($newPath)) {
          throw new \RuntimeException('The uploaded attachment could not be stored.');
        }
        $newPaths[] = $newPath;
        $newPathsByType[$type] = $newPath;
      }

      DB::transaction(function () use ($ownedDisclosure, $updates, $newPathsByType, &$oldPaths): void {
        foreach ($updates as $type => $update) {
          $attachment = $update['attachment'];
          $file = $update['file'];
          $oldPaths[] = $attachment->stored_path;
          $attachment->update([
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $newPathsByType[$type],
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'version' => $attachment->version + 1,
            'submitted_at' => now(),
            'is_latest_submission' => true,
            'needs_revision' => false,
            'revision_notes' => null,
          ]);
          $attachment->events()->create([
            'event_type' => 'uploaded',
            'version' => $attachment->version,
            'original_name' => $attachment->original_name,
            'occurred_at' => $attachment->submitted_at,
          ]);
        }
        $hasRemainingRevisions = $ownedDisclosure->attachments()
          ->where('needs_revision', true)
          ->exists();
        $ownedDisclosure->update($hasRemainingRevisions
          ? ['status' => 'returned']
          : ['status' => 'revised_submission', 'review_notes' => null]);
      });
    } catch (Throwable $exception) {
      Storage::disk('local')->delete($newPaths);
      throw $exception;
    }

    Storage::disk('local')->delete($oldPaths);

    $redirect = $request->boolean('return_to_review')
      ? redirect()->route('dashboard.submissions.show', $ownedDisclosure)
      : redirect()->route('dashboard.submissions.index');

    return $redirect
      ->with('toast_type', 'success')
      ->with('toast_message', 'The revised file upload was submitted successfully.');
  }

  public function updateRevisionDetails(Request $request, InnovationDisclosure $disclosure): RedirectResponse
  {
    $ownedDisclosure = $request->user()->innovationDisclosures()
      ->with(['technologies.files', 'inventors', 'costItems'])
      ->findOrFail($disclosure->id);
    abort_unless(
      $ownedDisclosure->status === 'returned',
      409,
      'Submission details can only be edited when revision is requested.'
    );

    $section = $request->validate([
      'section' => ['required', Rule::in(['technologies', 'inventors', 'cost_items'])],
    ])['section'];

    if ($section === 'technologies') {
      $validated = $request->validate([
        'technologies' => ['required', 'array', 'min:1', 'max:2'],
        'technologies.*.technology_title' => ['required', 'string', 'max:500'],
        'technologies.*.abstract_summary' => ['nullable', 'string'],
        'technologies.*.technical_field' => ['nullable', 'string'],
        'technologies.*.background_summary' => ['nullable', 'string'],
        'technologies.*.invention_summary' => ['nullable', 'string'],
        'technologies.*.detailed_description' => ['nullable', 'string'],
        'technologies.*.examples_results' => ['nullable', 'string'],
      ]);
      $this->ensureExistingRowsOnly($validated['technologies'], $ownedDisclosure->technologies);

      foreach ($ownedDisclosure->technologies as $technology) {
        $data = $validated['technologies'][$technology->id];
        foreach (self::TECHNOLOGY_NARRATIVES as $narrative) {
          if (blank($data[$narrative] ?? null)
            && ! $technology->files->contains('narrative_key', $narrative)) {
            return back()
              ->withErrors(["technologies.{$technology->id}.{$narrative}" => 'Enter this narrative or provide its uploaded PDF.'])
              ->withInput();
          }
        }
      }

      DB::transaction(function () use ($ownedDisclosure, $validated): void {
        foreach ($ownedDisclosure->technologies as $technology) {
          $technology->update($validated['technologies'][$technology->id]);
        }
        $primaryTechnology = $ownedDisclosure->technologies->sortBy('id')->first();
        $legacyNarratives = [];
        foreach (self::TECHNOLOGY_NARRATIVES as $narrative) {
          $legacyNarratives[$narrative] = $primaryTechnology->{$narrative} ?? '';
        }
        $ownedDisclosure->update([
          'technology_title' => $primaryTechnology->technology_title,
          ...$legacyNarratives,
        ]);
      });
    } elseif ($section === 'inventors') {
      $natureOptions = [
        'conception',
        'identification_essential_element',
        'technical_feature',
        'experimental_refinement',
        'reduction_to_practice',
        'testing_validation',
        'optimization',
        'other_substantive',
      ];
      $validated = $request->validate([
        'inventors' => ['required', 'array', 'min:1', 'max:20'],
        'inventors.*.first_name' => ['required', 'string', 'max:100'],
        'inventors.*.last_name' => ['required', 'string', 'max:100'],
        'inventors.*.middle_initial' => ['nullable', 'string', 'max:1'],
        'inventors.*.suffix' => ['nullable', 'string', 'max:20'],
        'inventors.*.email' => ['required', 'email', 'max:255'],
        'inventors.*.country_of_citizenship' => ['nullable', 'string', 'max:255'],
        'inventors.*.position_affiliation' => ['required', 'string', 'max:500'],
        'inventors.*.technical_contribution' => ['required', 'string'],
        'inventors.*.contribution_percent' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,100'],
        'inventors.*.contribution_nature' => ['required', 'array', 'min:1'],
        'inventors.*.contribution_nature.*' => ['required', Rule::in($natureOptions)],
        'inventors.*.contribution_other' => ['nullable', 'string', 'max:500'],
        'inventors.*.signature_name' => ['required', 'string', 'max:255'],
        'inventors.*.declaration_confirmed' => ['required', 'accepted'],
        'inventors.*.declaration_date' => ['required', 'date'],
      ]);
      $this->ensureExistingRowsOnly($validated['inventors'], $ownedDisclosure->inventors);

      $contributionTotal = round(array_sum(array_map(
        static fn (array $inventor): float => (float) $inventor['contribution_percent'],
        $validated['inventors']
      )), 2);
      if ($contributionTotal !== 100.0) {
        return back()
          ->withErrors(['inventors' => 'Declared creator contributions must add up to exactly 100%.'])
          ->withInput();
      }

      foreach ($validated['inventors'] as $id => $inventor) {
        if (in_array('other_substantive', $inventor['contribution_nature'], true)
          && blank($inventor['contribution_other'] ?? null)) {
          return back()
            ->withErrors(["inventors.{$id}.contribution_other" => 'Specify the other substantive contribution.'])
            ->withInput();
        }
        if (! in_array('other_substantive', $inventor['contribution_nature'], true)
          && filled($inventor['contribution_other'] ?? null)) {
          return back()
            ->withErrors(["inventors.{$id}.contribution_other" => 'This field is only used for other substantive contributions.'])
            ->withInput();
        }
      }

      DB::transaction(function () use ($ownedDisclosure, $validated): void {
        foreach ($ownedDisclosure->inventors as $inventor) {
          $data = $validated['inventors'][$inventor->id];
          $fullName = $data['last_name'] . ', ' . trim(implode(' ', array_filter([
            $data['first_name'],
            filled($data['middle_initial'] ?? null) ? strtoupper(trim($data['middle_initial'])) . '.' : null,
            $data['suffix'] ?? null,
          ])));
          $inventor->update([
            ...$data,
            'full_name' => $fullName,
            'affiliation' => $data['position_affiliation'],
          ]);
        }
        $primaryInventor = $ownedDisclosure->inventors->firstWhere('is_primary', true);
        if ($primaryInventor) {
          $ownedDisclosure->update([
            'lead_creator_name' => $primaryInventor->full_name,
            'email' => $primaryInventor->email,
          ]);
        }
      });
    } else {
      $validated = $request->validate([
        'cost_items' => ['required', 'array', 'min:1', 'max:100'],
        'cost_items.*.item' => ['required', 'string', 'max:255'],
        'cost_items.*.description' => ['required', 'string', 'max:2000'],
        'cost_items.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
        'cost_items.*.unit_cost' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        'cost_items.*.purpose' => ['nullable', 'string', 'max:2000'],
      ]);
      $this->ensureExistingRowsOnly($validated['cost_items'], $ownedDisclosure->costItems);

      DB::transaction(function () use ($ownedDisclosure, $validated): void {
        $totalDevelopmentCost = 0.0;
        foreach ($ownedDisclosure->costItems as $costItem) {
          $data = $validated['cost_items'][$costItem->id];
          $lineTotal = round((float) $data['quantity'] * (float) $data['unit_cost'], 2);
          $costItem->update([
            ...$data,
            'total_cost' => $lineTotal,
          ]);
          $totalDevelopmentCost += $lineTotal;
        }
        $ownedDisclosure->update([
          'total_development_cost' => round($totalDevelopmentCost, 2),
        ]);
      });
    }

    return redirect()
      ->route('dashboard.submissions.show', $ownedDisclosure)
      ->with('toast_type', 'success')
      ->with('toast_message', 'The requested submission details were updated successfully.');
  }

  public function store(Request $request): RedirectResponse
  {
    $materials = $request->input('materials');
    if (is_array($materials)) {
      $request->merge([
        'materials' => array_values(array_filter($materials, static function ($material): bool {
          if (! is_array($material)) {
            return true;
          }
          foreach (['item', 'description', 'quantity', 'unit_cost', 'purpose'] as $field) {
            if (filled($material[$field] ?? null)) {
              return true;
            }
          }
          return false;
        })),
      ]);
    }

    $ownershipDeclaration = $request->input('ownership_declaration');
    $relationship = $request->input('university_relationship');
    $inventors = $request->input('inventors', []);
    $inventorCount = is_array($inventors) ? count($inventors) : 0;
    $natureOptions = [
      'conception',
      'identification_essential_element',
      'technical_feature',
      'experimental_refinement',
      'reduction_to_practice',
      'testing_validation',
      'optimization',
      'other_substantive',
    ];
    $rules = [
      'lead_creator_name' => ['required', 'string', 'max:255'],
      'college_department' => ['required', 'string', 'max:255'],
      'campus' => ['required', 'string', 'max:255'],
      'university_relationship' => ['required', Rule::in([
        'faculty_academic',
        'admin_staff',
        'contractual_job_order',
        'external_partner',
        'student',
        'alumni',
        'other_specify',
      ])],
      'other_relationship' => [
        'nullable',
        'string',
        'max:255',
        Rule::requiredIf($relationship === 'other_specify'),
        Rule::prohibitedIf($relationship !== 'other_specify'),
      ],
      'email' => ['required', 'email', 'max:255'],
      'mobile_no' => ['required', 'string', 'max:20', 'regex:/^(09|\\+639)\d{9}$/'],
      'research_title' => ['required', 'string', 'max:500'],
      'funding_source' => ['required', 'string', 'max:500'],
      'date_of_creation' => ['required', 'date'],
      'research_approval_date' => ['required', 'date'],
      'technology_type' => ['required', Rule::in(['mechanical_engineering', 'chemical'])],
      'technologies' => ['required', 'array', 'min:1', 'max:2'],
      'technologies.*.technology_title' => ['required', 'string', 'max:500'],
      'file_drive_link' => ['nullable', 'url', 'max:2048'],
      'ownership_declaration' => ['required', Rule::in([
        'slsu_owned',
        'assigned_to_slsu',
        'creator_retained',
      ])],
      'inventors' => ['required', 'array', 'min:1', 'max:20'],
      'inventors.*.last_name' => ['required', 'string', 'max:100'],
      'inventors.*.first_name' => ['required', 'string', 'max:100'],
      'inventors.*.middle_initial' => ['nullable', 'string', 'max:1'],
      'inventors.*.suffix' => ['nullable', 'string', 'max:20'],
      'inventors.*.email' => ['required', 'email', 'max:255'],
      'inventors.*.position_affiliation' => ['required', 'string', 'max:500'],
      'inventors.*.technical_contribution' => ['required', 'string'],
      'inventors.*.contribution_percent' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,100'],
      'inventors.*.contribution_nature' => ['required', 'array', 'min:1'],
      'inventors.*.contribution_nature.*' => ['required', Rule::in($natureOptions)],
      'inventors.*.contribution_other' => [
        'nullable',
        'string',
        'max:500',
      ],
      'inventors.*.signature_name' => ['required', 'string', 'max:255'],
      'inventors.*.declaration_confirmed' => ['required', 'accepted'],
      'inventors.*.declaration_date' => ['required', 'date'],
      'materials' => ['sometimes', 'array', 'max:100'],
      'materials.*.item' => ['required', 'string', 'max:255'],
      'materials.*.description' => ['required', 'string', 'max:2000'],
      'materials.*.quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
      'materials.*.unit_cost' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
      'materials.*.purpose' => ['nullable', 'string', 'max:2000'],
      'attachments' => ['nullable', 'array:deed_assignment,additional_signed_sheet,sample_photo,drawings,methodology,assistance_form'],
      'attachments.deed_assignment' => [
        ...$this->attachmentRules(false),
        Rule::requiredIf($ownershipDeclaration === 'assigned_to_slsu'),
      ],
      'attachments.additional_signed_sheet' => [
        ...$this->attachmentRules(false),
        Rule::requiredIf($inventorCount > 6),
      ],
      'attachments.sample_photo' => $this->attachmentRules(true),
      'attachments.drawings' => $this->attachmentRules(false),
      'attachments.methodology' => $this->attachmentRules(),
      'attachments.assistance_form' => $this->attachmentRules(),
    ];
    foreach (self::TECHNOLOGY_NARRATIVES as $narrative) {
      $rules["technologies.*.{$narrative}"] = ['nullable', 'string'];
      $rules["technologies.*.files.{$narrative}"] = [
        'nullable',
        'file',
        'mimes:pdf',
        'max:15360',
        function (string $attribute, mixed $file, \Closure $fail): void {
          if ($file instanceof UploadedFile && mb_strlen($file->getClientOriginalName()) > 255) {
            $fail('The uploaded file name may not exceed 255 characters.');
          }
        },
      ];
    }
    $validated = $request->validate($rules, [
      'attachments.*.mimes' => 'All uploaded files must be PDF documents.',
      'technologies.*.files.*.mimes' => 'Narrative uploads must be PDF documents.',
      'attachments.sample_photo.required' => 'Upload a PDF containing the product sample photo.',
      'attachments.deed_assignment.required' => 'Upload the signed Deed of Assignment for an assignment to SLSU.',
      'attachments.additional_signed_sheet.required' => 'For more than six creators, upload an additional signed creator sheet as a PDF.',
    ]);

    foreach ($validated['technologies'] as $technologyIndex => $technology) {
      foreach (self::TECHNOLOGY_NARRATIVES as $narrative) {
        $text = trim((string) ($technology[$narrative] ?? ''));
        $file = $technology['files'][$narrative] ?? null;
        if ($text === '' && ! $file instanceof UploadedFile) {
          return back()
            ->withErrors(["technologies.{$technologyIndex}.{$narrative}" => 'Enter this narrative or upload its PDF.'])
            ->withInput();
        }
      }
    }

    $contributionTotal = round(array_sum(array_map(
      static fn (array $inventor): float => (float) $inventor['contribution_percent'],
      $validated['inventors']
    )), 2);
    if ($contributionTotal !== 100.0) {
      return back()
        ->withErrors(['inventors' => 'Declared creator contributions must add up to exactly 100%.'])
        ->withInput();
    }

    foreach ($validated['inventors'] as $index => $inventor) {
      if (in_array('other_substantive', $inventor['contribution_nature'], true)
        && blank($inventor['contribution_other'] ?? null)) {
        return back()
          ->withErrors(["inventors.{$index}.contribution_other" => 'Specify the other substantive contribution.'])
          ->withInput();
      }
      if (! in_array('other_substantive', $inventor['contribution_nature'], true)
        && filled($inventor['contribution_other'] ?? null)) {
        return back()
          ->withErrors(["inventors.{$index}.contribution_other" => 'This field is only used for other substantive contributions.'])
          ->withInput();
      }
    }

    $lineTotals = [];
    foreach ($validated['materials'] ?? [] as $index => $material) {
      $lineTotal = round((float) $material['quantity'] * (float) $material['unit_cost'], 2);
      if (! is_finite($lineTotal) || $lineTotal > 9999999999.99) {
        return back()
          ->withErrors(["materials.{$index}.unit_cost" => 'The calculated item total may not exceed ₱9,999,999,999.99.'])
          ->withInput();
      }
      $lineTotals[] = $lineTotal;
    }
    $totalDevelopmentCost = round(array_sum($lineTotals), 2);
    if (! is_finite($totalDevelopmentCost) || $totalDevelopmentCost > 9999999999.99) {
      return back()
        ->withErrors(['materials' => 'The total estimated development cost may not exceed ₱9,999,999,999.99.'])
        ->withInput();
    }

    $storageDirectory = null;

    try {
      DB::transaction(function () use ($request, $validated, $lineTotals, $totalDevelopmentCost, &$storageDirectory): void {
        $materials = $validated['materials'] ?? [];
        $attachments = $validated['attachments'] ?? [];
        $inventorRows = $validated['inventors'];
        $technologies = $validated['technologies'];
        $primaryTechnology = $technologies[0];
        $legacyNarratives = [];
        foreach (self::TECHNOLOGY_NARRATIVES as $field) {
          $legacyNarratives[$field] = $primaryTechnology[$field] ?? '';
        }

        $disclosure = $request->user()->innovationDisclosures()->create([
          ...Arr::except($validated, [
            'inventors',
            'materials',
            'attachments',
            'other_relationship',
            'technologies',
          ]),
          'other_relationship' => $validated['other_relationship'] ?? null,
          'disclosure_date' => now()->toDateString(),
          'total_development_cost' => $totalDevelopmentCost,
          'technology_title' => $primaryTechnology['technology_title'],
          ...$legacyNarratives,
          'research_title' => $validated['research_title'],
          'ownership_consent' => true,
          'has_substantial_support' => null,
        ]);
        $storageDirectory = "innovation-disclosures/{$disclosure->id}";

        foreach ($technologies as $technologyData) {
          $technology = $disclosure->technologies()->create([
            'technology_title' => $technologyData['technology_title'],
            ...array_intersect_key($technologyData, array_flip(self::TECHNOLOGY_NARRATIVES)),
          ]);
          foreach (self::TECHNOLOGY_NARRATIVES as $narrative) {
            $file = $technologyData['files'][$narrative] ?? null;
            if (! $file instanceof UploadedFile) {
              continue;
            }
            $storedPath = $file->store("{$storageDirectory}/technologies/{$technology->id}/{$narrative}", 'local');
            if (! is_string($storedPath)) {
              throw new \RuntimeException('The technology narrative PDF could not be stored.');
            }
            $technology->files()->create([
              'narrative_key' => $narrative,
              'original_name' => $file->getClientOriginalName(),
              'stored_path' => $storedPath,
              'mime_type' => 'application/pdf',
              'size_bytes' => $file->getSize(),
            ]);
          }
        }

        foreach ($inventorRows as $index => $inventorData) {
          $fullName = $inventorData['last_name'] . ', ' . trim(implode(' ', array_filter([
            $inventorData['first_name'],
            filled($inventorData['middle_initial'] ?? null) ? strtoupper(trim($inventorData['middle_initial'])) . '.' : null,
            $inventorData['suffix'] ?? null,
          ])));
          $disclosure->inventors()->create([
            ...$inventorData,
            'full_name' => $fullName,
            'affiliation' => $inventorData['position_affiliation'],
            'country_of_citizenship' => null,
            'is_primary' => $index === array_key_first($inventorRows),
          ]);
        }

        foreach ($materials as $index => $material) {
          $disclosure->costItems()->create([
            ...$material,
            'total_cost' => $lineTotals[$index],
          ]);
        }

        foreach ($attachments as $type => $file) {
          if (! $file instanceof UploadedFile) {
            continue;
          }

          $storedPath = $file->store("{$storageDirectory}/{$type}", 'local');
          if (! is_string($storedPath)) {
            throw new \RuntimeException('The uploaded attachment could not be stored.');
          }

          $attachment = $disclosure->attachments()->create([
            'type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'version' => 1,
          ]);
          $attachment->events()->create([
            'event_type' => 'uploaded',
            'version' => 1,
            'original_name' => $attachment->original_name,
            'occurred_at' => $attachment->created_at,
          ]);
        }
      });
    } catch (Throwable $exception) {
      if ($storageDirectory !== null) {
        Storage::disk('local')->deleteDirectory($storageDirectory);
      }

      throw $exception;
    }

    return redirect()
      ->route('dashboard.submissions.index')
      ->with('toast_type', 'success')
      ->with('toast_message', 'Innovation disclosure submitted successfully.');
  }

  private function attachmentRules(bool $required = false): array
  {
    return [
      $required ? 'required' : 'nullable',
      'file',
      'mimes:pdf',
      'max:15360',
      function (string $attribute, mixed $file, \Closure $fail): void {
        if ($file instanceof UploadedFile && mb_strlen($file->getClientOriginalName()) > 255) {
          $fail('The uploaded file name may not exceed 255 characters.');
        }
      },
    ];
  }

  private function ensureExistingRowsOnly(array $submittedRows, \Illuminate\Database\Eloquent\Collection $existingRows): void
  {
    $submittedIds = array_map('intval', array_keys($submittedRows));
    $existingIds = $existingRows->modelKeys();
    sort($submittedIds);
    sort($existingIds);

    abort_unless(
      $submittedIds === $existingIds,
      422,
      'Only existing submission rows can be edited; rows cannot be added or removed.'
    );
  }
}
