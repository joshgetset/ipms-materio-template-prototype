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
use Throwable;
use App\Models\DisclosureAttachment;
use App\Models\InnovationDisclosure;

class InnovationDisclosureController extends Controller
{
  private const STATUSES = ['submitted', 'under_review', 'returned', 'endorsed', 'filed'];

  public function index(Request $request): View
  {
    $userDisclosures = $request->user()->innovationDisclosures();
    $statusCounts = $userDisclosures
      ->select('status', DB::raw('count(*) as total'))
      ->groupBy('status')
      ->pluck('total', 'status');

    return view('content.dashboard.innovation-disclosure.index', [
      'totalDisclosures' => $statusCounts->sum(),
      'statusCounts' => $statusCounts,
      'chartLabels' => array_map(fn(string $status) => str_replace('_', ' ', ucfirst($status)), self::STATUSES),
      'chartValues' => array_map(fn(string $status) => (int) ($statusCounts[$status] ?? 0), self::STATUSES),
    ]);
  }

  public function create(): View
  {
    return view('content.dashboard.innovation-disclosure.index', [
      'page' => 'form',
    ]);
  }

  public function submissions(Request $request): View
  {
    return view('content.dashboard.my-submissions.index', [
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
    $ownedDisclosure = $request->user()->innovationDisclosures()
      ->with(['attachments', 'inventors'])
      ->findOrFail($disclosure->id);

    return view('content.dashboard.my-submissions.index', [
      'disclosures' => collect([$ownedDisclosure]),
      'deletedDisclosures' => collect(),
      'detailView' => true,
    ]);
  }

  public function submissionStatusUpdates(Request $request)
  {
    return response()->json(
      $request->user()->innovationDisclosures()
        ->with('attachments:id,innovation_disclosure_id,type,needs_revision,revision_notes')
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
    $ownedDisclosure = $request->user()->is_admin
      ? $disclosure
      : $request->user()->innovationDisclosures()->findOrFail($disclosure->id);
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

  public function storeAttachment(Request $request, InnovationDisclosure $disclosure): RedirectResponse
  {
    $ownedDisclosure = $request->user()->innovationDisclosures()->findOrFail($disclosure->id);
    $validated = $request->validate([
      'type' => ['required', Rule::in(['drawings', 'methodology', 'assistance_form'])],
      'file' => $this->attachmentRules(true),
    ]);

    $file = $validated['file'];
    $storageDirectory = "innovation-disclosures/{$ownedDisclosure->id}/{$validated['type']}";
    $oldAttachment = $ownedDisclosure->attachments()->where('type', $validated['type'])->first();
    $oldPath = $oldAttachment?->stored_path;
    $newPath = $file->store($storageDirectory, 'local');

    if (! is_string($newPath)) {
      throw new \RuntimeException('The uploaded attachment could not be stored.');
    }

    try {
      DB::transaction(function () use ($ownedDisclosure, $validated, $file, $newPath, $oldAttachment): void {
        $attachment = $oldAttachment ?? new DisclosureAttachment();
        $attachment->fill([
          'type' => $validated['type'],
          'original_name' => $file->getClientOriginalName(),
          'stored_path' => $newPath,
          'mime_type' => $file->getMimeType(),
          'size_bytes' => $file->getSize(),
          'version' => ($oldAttachment?->version ?? 0) + 1,
          'needs_revision' => false,
          'revision_notes' => null,
        ]);
        $ownedDisclosure->attachments()->save($attachment);
      });
    } catch (Throwable $exception) {
      Storage::disk('local')->delete($newPath);
      throw $exception;
    }

    if ($oldPath !== null) {
      Storage::disk('local')->delete($oldPath);
    }

    return redirect()
      ->route('dashboard.submissions.index')
      ->with('toast_type', 'success')
      ->with('toast_message', $oldAttachment
        ? 'The attachment revision was uploaded successfully.'
        : 'The attachment was uploaded successfully.');
  }

  public function store(Request $request): RedirectResponse
  {
    $ownershipDeclaration = $request->input('ownership_declaration');
    $isSlsuEmployee = $request->input('university_relationship') === 'employee';
    $isSlsuFunded = $request->input('funding_source') === 'slsu_funded';
    $isSlsuOwned = $isSlsuEmployee && ($isSlsuFunded || $request->boolean('has_substantial_support'));
    $ownershipOptions = $isSlsuOwned
      ? ['not_applicable']
      : ['assign_to_slsu', 'retain_ownership'];
    $inventorKeys = array_keys($request->input('inventors', []));

    $validated = $request->validate([
      'email' => ['required', 'email', 'max:255'],
      'mobile_no' => ['required', 'string', 'max:20', 'regex:/^(09|\\+639)\d{9}$/'],
      'technology_title' => ['required', 'string', 'max:500'],
      'technology_type' => ['required', Rule::in(['chemical', 'mechanical', 'literary_creative'])],
      'university_relationship' => ['required', Rule::in(['student', 'employee', 'outsider'])],
      'funding_source' => ['required', Rule::in(['personal', 'slsu_funded', 'externally_funded'])],
      'has_substantial_support' => [
        Rule::requiredIf($isSlsuEmployee && ! $isSlsuFunded),
        Rule::prohibitedIf(! $isSlsuEmployee || $isSlsuFunded),
        'nullable',
        'boolean',
      ],
      'ownership_declaration' => ['required', Rule::in($ownershipOptions)],
      'ownership_consent' => ['required', 'accepted'],
      'research_title' => [
        'nullable',
        'string',
        'max:500',
        Rule::requiredIf($ownershipDeclaration === 'not_applicable'),
        Rule::prohibitedIf($ownershipDeclaration !== 'not_applicable'),
      ],
      'research_approval_date' => [
        'nullable',
        'date',
        Rule::requiredIf($ownershipDeclaration === 'not_applicable'),
        Rule::prohibitedIf($ownershipDeclaration !== 'not_applicable'),
      ],
      'background_summary' => ['required', 'string', 'min:100'],
      'detailed_description' => ['required', 'string', 'min:100'],
      'drawings_description' => ['nullable', 'string'],
      'inventors' => ['required', 'array', 'min:1', 'max:20'],
      'inventors.*.last_name' => ['required', 'string', 'max:100'],
      'inventors.*.first_name' => ['required', 'string', 'max:100'],
      'inventors.*.suffix' => ['nullable', 'string', 'max:20'],
      'inventors.*.middle_initial' => ['nullable', 'string', 'size:1'],
      'inventors.*.country_of_citizenship' => ['required', Rule::in(config('countries'))],
      'inventors.*.affiliation' => ['required', Rule::in([
        'slsu_employee',
        'external_inventor',
        'student',
        'faculty_adviser',
        'external_collaborator',
      ])],
      'primary_inventor' => ['required', 'integer', Rule::in($inventorKeys)],
      'attachments' => ['nullable', 'array'],
      'attachments.drawings' => $this->attachmentRules(),
      'attachments.methodology' => $this->attachmentRules(),
      'attachments.assistance_form' => $this->attachmentRules(),
    ]);

    $validated['has_substantial_support'] = $isSlsuEmployee
      ? ($isSlsuFunded || $request->boolean('has_substantial_support'))
      : null;

    $storageDirectory = null;

    try {
      DB::transaction(function () use ($request, $validated, &$storageDirectory): void {
        $disclosure = $request->user()->innovationDisclosures()->create(
          Arr::except($validated, ['inventors', 'primary_inventor', 'attachments'])
        );
        $storageDirectory = "innovation-disclosures/{$disclosure->id}";

        foreach ($validated['inventors'] as $index => $inventorData) {
          $disclosure->inventors()->create([
            ...$inventorData,
            'is_primary' => (string) $index === (string) $validated['primary_inventor'],
          ]);
        }

        foreach ($validated['attachments'] ?? [] as $type => $file) {
          if (! $file instanceof UploadedFile) {
            continue;
          }

          $storedPath = $file->store("{$storageDirectory}/{$type}", 'local');
          if (! is_string($storedPath)) {
            throw new \RuntimeException('The uploaded attachment could not be stored.');
          }

          $disclosure->attachments()->create([
            'type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'mime_type' => $file->getMimeType(),
            'size_bytes' => $file->getSize(),
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
      'mimes:pdf,doc,docx,jpg,jpeg,png',
      'max:15360',
      function (string $attribute, mixed $file, \Closure $fail): void {
        if ($file instanceof UploadedFile && mb_strlen($file->getClientOriginalName()) > 255) {
          $fail('The uploaded file name may not exceed 255 characters.');
        }
      },
    ];
  }
}
