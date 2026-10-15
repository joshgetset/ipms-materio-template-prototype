<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Throwable;
use App\Models\DisclosureAttachment;
use App\Models\DisclosureTechnology;
use App\Models\DisclosureTechnologyFile;
use App\Models\DisclosureTechnologyFileVersion;
use App\Models\IdfStagedUpload;
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
  private const TECHNOLOGY_REQUIRED_FILES = ['sample_photo', 'technical_drawing'];
  private const TECHNOLOGY_FILE_KEYS = [
    'abstract_summary',
    'technical_field',
    'background_summary',
    'invention_summary',
    'detailed_description',
    'examples_results',
    'sample_photo',
    'technical_drawing',
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

  public function create(Request $request): View
  {
    $this->purgeExpiredStagedUploads();
    $stagedTokens = array_filter(
      Arr::flatten($request->old('idf_upload_tokens', [])),
      static fn ($token): bool => is_string($token) && preg_match('/^[A-Za-z0-9]{64}$/', $token) === 1
    );
    $stagedUploadNames = [];
    if ($stagedTokens !== []) {
      $tokenByHash = [];
      foreach ($stagedTokens as $token) {
        $tokenByHash[hash('sha256', $token)] = $token;
      }
      $stagedUploadNames = IdfStagedUpload::query()
        ->where('user_id', $request->user()->id)
        ->whereIn('token_hash', array_keys($tokenByHash))
        ->where('expires_at', '>', now())
        ->get(['token_hash', 'original_name'])
        ->mapWithKeys(fn (IdfStagedUpload $upload): array => [
          $tokenByHash[$upload->token_hash] => $upload->original_name,
        ])
        ->all();
    }

    return view('content.dashboard.inventor.inventor-disclosure-index', [
      'page' => 'form',
      'stagedUploadNames' => $stagedUploadNames,
    ]);
  }

  public function stageIdfUpload(Request $request): \Illuminate\Http\JsonResponse
  {
    $this->purgeExpiredStagedUploads();
    $validated = $request->validate([
      'file' => ['required', 'file', 'mimes:pdf', 'max:15360'],
    ], [
      'file.mimes' => 'Upload a PDF document.',
      'file.max' => 'Each PDF must be no larger than 15 MB.',
    ]);

    $file = $validated['file'];
    if (mb_strlen($file->getClientOriginalName()) > 255) {
      throw ValidationException::withMessages([
        'file' => 'The uploaded file name may not exceed 255 characters.',
      ]);
    }
    $token = Str::random(64);
    $storedPath = $file->store("idf-staging/{$request->user()->id}", 'local');
    if (! is_string($storedPath)) {
      throw new \RuntimeException('The PDF could not be staged for submission.');
    }

    try {
      IdfStagedUpload::create([
        'user_id' => $request->user()->id,
        'token_hash' => hash('sha256', $token),
        'stored_path' => $storedPath,
        'original_name' => $file->getClientOriginalName(),
        'mime_type' => 'application/pdf',
        'size_bytes' => $file->getSize(),
        // Extended from 2 hours: users filling the multi-page wizard
        // could exceed the old window, causing "staged PDFs have
        // expired" errors at submit time.
        'expires_at' => now()->addHours(24),
      ]);
    } catch (Throwable $exception) {
      $this->deleteStagedUploadFile($storedPath);
      throw $exception;
    }

    return response()->json([
      'token' => $token,
      'original_name' => $file->getClientOriginalName(),
      'size_bytes' => $file->getSize(),
    ], 201);
  }

  public function destroyStagedIdfUpload(Request $request, string $token): \Illuminate\Http\JsonResponse
  {
    abort_unless(preg_match('/^[A-Za-z0-9]{64}$/', $token) === 1, 404);
    $stagedUpload = IdfStagedUpload::query()
      ->where('user_id', $request->user()->id)
      ->where('token_hash', hash('sha256', $token))
      ->firstOrFail();

    if (! $this->deleteStagedUploadFile($stagedUpload->stored_path)) {
      throw new \RuntimeException('The staged PDF could not be removed. Please try again.');
    }
    $stagedUpload->delete();

    return response()->json(['deleted' => true]);
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
      ->with(['attachments.events', 'inventors', 'costItems', 'technologies.files.versions'])
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
        ->with([
          'attachments:id,innovation_disclosure_id,type,original_name,needs_revision,revision_notes,is_latest_submission,version,review_status,updated_at',
          'technologies:id,innovation_disclosure_id,technology_title',
          'technologies.files:id,disclosure_technology_id,original_name,review_status,updated_at',
        ])
        ->latest()
        ->get(['id', 'technology_title', 'status', 'review_notes', 'updated_at'])
    );
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
    $isGuidancePdf = $request->boolean('guidance');
    $storedPath = $isGuidancePdf
      ? $ownedFile->revision_guidance_path
      : $ownedFile->stored_path;
    $originalName = $isGuidancePdf
      ? $ownedFile->revision_guidance_name
      : $ownedFile->original_name;
    abort_unless(is_string($storedPath) && is_string($originalName), 404);
    abort_unless(Storage::disk('local')->exists($storedPath), 404);

    $response = response()->file(Storage::disk('local')->path($storedPath), [
      'Content-Type' => 'application/pdf',
      'X-Content-Type-Options' => 'nosniff',
    ]);
    $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');

    return $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_INLINE,
      $originalName
    );
  }

  public function previewTechnologyFileVersion(
    Request $request,
    InnovationDisclosure $disclosure,
    DisclosureTechnologyFile $file,
    DisclosureTechnologyFileVersion $version
  ): BinaryFileResponse {
    abort_unless(
      $request->user()->is_ip_head || $disclosure->user_id === $request->user()->id,
      403
    );
    $technology = $disclosure->technologies()
      ->whereHas('files', fn ($query) => $query->whereKey($file->id))
      ->firstOrFail();
    $ownedFile = $technology->files()->findOrFail($file->id);
    $ownedVersion = $ownedFile->versions()->findOrFail($version->id);
    $disk = Storage::disk('local');
    $isGuidancePdf = $request->boolean('guidance');
    $storedPath = $isGuidancePdf
      ? $ownedVersion->revision_guidance_path
      : $ownedVersion->stored_path;
    $originalName = $isGuidancePdf
      ? $ownedVersion->revision_guidance_name
      : $ownedVersion->original_name;
    abort_unless(is_string($storedPath) && is_string($originalName), 404);
    abort_unless($disk->exists($storedPath), 404);

    $response = response()->file($disk->path($storedPath), [
      'Content-Type' => 'application/pdf',
      'X-Content-Type-Options' => 'nosniff',
    ]);
    $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');

    return $response->setContentDisposition(
      ResponseHeaderBag::DISPOSITION_INLINE,
      $originalName
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
            'review_status' => 'review_needed',
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
          ->exists()
          || $ownedDisclosure->technologies()
            ->whereHas('files', fn ($query) => $query->where('review_status', 'revision_requested'))
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

  public function storeTechnologyFileRevision(
    Request $request,
    InnovationDisclosure $disclosure,
    DisclosureTechnology $technology,
    DisclosureTechnologyFile $file
  ): RedirectResponse {
    $ownedDisclosure = $request->user()->innovationDisclosures()->findOrFail($disclosure->id);
    abort_unless(
      in_array($ownedDisclosure->status, ['submitted', 'under_review', 'returned', 'revised_submission'], true),
      409,
      'Files cannot be changed in the current submission status.'
    );
    $ownedTechnology = $ownedDisclosure->technologies()->findOrFail($technology->id);
    $technologyFile = $ownedTechnology->files()->findOrFail($file->id);
    abort_unless(
      $technologyFile->review_status === 'revision_requested',
      409,
      'Only files marked for revision can be updated.'
    );

    $validated = $request->validate([
      'file' => $this->attachmentRules(true),
      'submission_note' => ['nullable', 'string', 'max:2000'],
    ]);
    $upload = $validated['file'];
    $storedPath = null;

    try {
      DB::transaction(function () use ($ownedDisclosure, $ownedTechnology, $technologyFile, $upload, $validated, &$storedPath): void {
        $lockedDisclosure = InnovationDisclosure::query()->lockForUpdate()->findOrFail($ownedDisclosure->id);
        abort_unless(
          in_array($lockedDisclosure->status, ['submitted', 'under_review', 'returned', 'revised_submission'], true),
          409,
          'Files cannot be changed in the current submission status.'
        );
        $lockedFile = $ownedTechnology->files()->lockForUpdate()->findOrFail($technologyFile->id);
        abort_unless(
          $lockedFile->review_status === 'revision_requested',
          409,
          'Only files marked for revision can be updated.'
        );

        $storedPath = $upload->store(
          "innovation-disclosures/{$lockedDisclosure->id}/technologies/{$ownedTechnology->id}/{$lockedFile->narrative_key}",
          'local'
        );
        if (! is_string($storedPath)) {
          throw new \RuntimeException('The technology PDF could not be stored.');
        }
        $lockedFile->versions()->create([
          'version' => $lockedFile->version,
          'original_name' => $lockedFile->original_name,
          'stored_path' => $lockedFile->stored_path,
          'mime_type' => $lockedFile->mime_type,
          'size_bytes' => $lockedFile->size_bytes,
          'review_status' => $lockedFile->review_status,
          'revision_notes' => $lockedFile->revision_notes,
          'revision_guidance_path' => $lockedFile->revision_guidance_path,
          'revision_guidance_name' => $lockedFile->revision_guidance_name,
          'submission_note' => $lockedFile->submission_note,
          'submitted_at' => $lockedFile->updated_at,
        ]);
        $lockedFile->update([
          'original_name' => $upload->getClientOriginalName(),
          'stored_path' => $storedPath,
          'mime_type' => 'application/pdf',
          'size_bytes' => $upload->getSize(),
          'version' => $lockedFile->version + 1,
          'review_status' => 'review_needed',
          'revision_notes' => null,
          'revision_guidance_path' => null,
          'revision_guidance_name' => null,
          'submission_note' => $validated['submission_note'] ?? null,
        ]);

        $hasRemainingRevisions = $lockedDisclosure->attachments()
          ->where('needs_revision', true)
          ->exists()
          || $lockedDisclosure->technologies()
            ->whereHas('files', fn ($query) => $query->where('review_status', 'revision_requested'))
            ->exists();
        $lockedDisclosure->update($hasRemainingRevisions
          ? ['status' => 'returned']
          : ['status' => 'revised_submission', 'review_notes' => null]);
      });
    } catch (Throwable $exception) {
      if (is_string($storedPath)) {
        Storage::disk('local')->delete($storedPath);
      }
      throw $exception;
    }

    return redirect()
      ->route('dashboard.submissions.show', $ownedDisclosure)
      ->with('toast_type', 'success')
      ->with('toast_message', 'The revised technology file was submitted successfully.');
  }

  public function updateRevisionDetails(Request $request, InnovationDisclosure $disclosure): RedirectResponse
  {
    $ownedDisclosure = $request->user()->innovationDisclosures()
      ->with(['technologies.files', 'inventors', 'costItems'])
      ->findOrFail($disclosure->id);
    abort_unless(
      $ownedDisclosure->status === 'returned'
        && ! $ownedDisclosure->attachments()->where('needs_revision', true)->exists()
        && ! $ownedDisclosure->technologies()
          ->whereHas('files', fn ($query) => $query->where('review_status', 'revision_requested'))
          ->exists(),
      409,
      'Submission details can only be edited when no files are awaiting revision.'
    );

    $section = $request->validate([
      'section' => ['required', Rule::in(['technologies', 'inventors', 'cost_items'])],
    ])['section'];

    if ($section === 'technologies') {
      $validated = $request->validate([
        'technologies' => ['required', 'array', 'min:1', 'max:2'],
        'technologies.*.technology_title' => ['required', 'string', 'max:500'],
      ]);
      $this->ensureExistingRowsOnly($validated['technologies'], $ownedDisclosure->technologies);

      $fileRules = [];
      foreach ($ownedDisclosure->technologies as $technology) {
        foreach (self::TECHNOLOGY_FILE_KEYS as $fileKey) {
          $hasExistingPdf = $technology->files->contains('narrative_key', $fileKey);
          $fileRules["technologies.{$technology->id}.files.{$fileKey}"] = $this->attachmentRules(! $hasExistingPdf);
          $fileRules["technologies.{$technology->id}.file_notes.{$fileKey}"] = ['nullable', 'string', 'max:2000'];
        }
      }
      $validatedFiles = $request->validate($fileRules, [
        'technologies.*.files.*.required' => 'Upload a PDF for every technology narrative.',
        'technologies.*.files.*.mimes' => 'Technology uploads must be PDF documents.',
      ]);
      foreach ($validatedFiles['technologies'] ?? [] as $technologyId => $technologyFiles) {
        $validated['technologies'][$technologyId]['files'] = $technologyFiles['files'] ?? [];
        $validated['technologies'][$technologyId]['file_notes'] = $technologyFiles['file_notes'] ?? [];
      }

      $storedPaths = [];
      try {
        DB::transaction(function () use ($ownedDisclosure, $validated, &$storedPaths): void {
          foreach ($ownedDisclosure->technologies as $technology) {
            $technologyData = $validated['technologies'][$technology->id];
            $technology->update([
              'technology_title' => $technologyData['technology_title'],
              ...array_fill_keys(self::TECHNOLOGY_NARRATIVES, ''),
            ]);

            foreach (self::TECHNOLOGY_FILE_KEYS as $fileKey) {
              $file = $technologyData['files'][$fileKey] ?? null;
              if (! $file instanceof UploadedFile) {
                continue;
              }
              $storedPath = $file->store("innovation-disclosures/{$ownedDisclosure->id}/technologies/{$technology->id}/{$fileKey}", 'local');
              if (! is_string($storedPath)) {
                throw new \RuntimeException('The technology PDF could not be stored.');
              }
              $storedPaths[] = $storedPath;

              $technologyFile = $technology->files->firstWhere('narrative_key', $fileKey);
              if ($technologyFile) {
                $technologyFile->versions()->create([
                  'version' => $technologyFile->version,
                  'original_name' => $technologyFile->original_name,
                  'stored_path' => $technologyFile->stored_path,
                  'mime_type' => $technologyFile->mime_type,
                  'size_bytes' => $technologyFile->size_bytes,
                  'review_status' => $technologyFile->review_status,
                  'revision_notes' => $technologyFile->revision_notes,
                  'revision_guidance_path' => $technologyFile->revision_guidance_path,
                  'revision_guidance_name' => $technologyFile->revision_guidance_name,
                  'submission_note' => $technologyFile->submission_note,
                  'submitted_at' => $technologyFile->updated_at,
                ]);
                $technologyFile->update([
                  'original_name' => $file->getClientOriginalName(),
                  'stored_path' => $storedPath,
                  'mime_type' => 'application/pdf',
                  'size_bytes' => $file->getSize(),
                  'version' => $technologyFile->version + 1,
                  'review_status' => 'review_needed',
                  'revision_notes' => null,
                  'revision_guidance_path' => null,
                  'revision_guidance_name' => null,
                  'submission_note' => $technologyData['file_notes'][$fileKey] ?? null,
                ]);
              } else {
                $technology->files()->create([
                  'narrative_key' => $fileKey,
                  'original_name' => $file->getClientOriginalName(),
                  'stored_path' => $storedPath,
                  'mime_type' => 'application/pdf',
                  'size_bytes' => $file->getSize(),
                  'review_status' => 'review_needed',
                  'submission_note' => $technologyData['file_notes'][$fileKey] ?? null,
                ]);
              }
            }
          }
          $primaryTechnology = $ownedDisclosure->technologies->sortBy('id')->first();
          $ownedDisclosure->update([
            'technology_title' => $validated['technologies'][$primaryTechnology->id]['technology_title'],
            ...array_fill_keys(self::TECHNOLOGY_NARRATIVES, ''),
          ]);
        });
      } catch (Throwable $error) {
        Storage::disk('local')->delete($storedPaths);
        throw $error;
      }

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
    $this->purgeExpiredStagedUploads();
    $stagedUploads = $this->resolveStagedIdfUploads($request);

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
      'technology_type' => ['required', Rule::in(['mechanical', 'chemical'])],
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
      'attachments.sample_photo' => $this->attachmentRules(false),
      'attachments.drawings' => $this->attachmentRules(false),
      'attachments.methodology' => $this->attachmentRules(),
      'attachments.assistance_form' => $this->attachmentRules(),
    ];
    foreach (self::TECHNOLOGY_NARRATIVES as $narrative) {
      $rules["technologies.*.files.{$narrative}"] = $this->attachmentRules(true);
    }
    foreach (self::TECHNOLOGY_REQUIRED_FILES as $fileKey) {
      $rules["technologies.*.files.{$fileKey}"] = $this->attachmentRules(true);
    }
    $validated = $request->validate($rules, [
      'attachments.*.mimes' => 'All uploaded files must be PDF documents.',
      'technologies.*.files.*.mimes' => 'Technology uploads must be PDF documents.',
      'technologies.*.files.*.required' => 'Upload a PDF for every required technology document.',
      'technologies.*.files.sample_photo.required' => 'Upload a sample picture PDF for each technology.',
      'technologies.*.files.technical_drawing.required' => 'Upload a technical drawing PDF for each technology.',
      'attachments.deed_assignment.required' => 'Upload the signed Deed of Assignment for an assignment to SLSU.',
      'attachments.additional_signed_sheet.required' => 'For more than six creators, upload an additional signed creator sheet as a PDF.',
    ]);

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
          $legacyNarratives[$field] = '';
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
          ]);
          foreach ([...self::TECHNOLOGY_NARRATIVES, ...self::TECHNOLOGY_REQUIRED_FILES] as $fileKey) {
            $file = $technologyData['files'][$fileKey] ?? null;
            if (! $file instanceof UploadedFile) {
              continue;
            }
            $storedPath = $file->store("{$storageDirectory}/technologies/{$technology->id}/{$fileKey}", 'local');
            if (! is_string($storedPath)) {
              throw new \RuntimeException('The technology PDF could not be stored.');
            }
            $technology->files()->create([
              'narrative_key' => $fileKey,
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
            'is_latest_submission' => true,
            'review_status' => 'review_needed',
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

    foreach ($stagedUploads as $stagedUpload) {
      $this->deleteStagedUploadFile($stagedUpload->stored_path);
      $stagedUpload->delete();
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

  private function resolveStagedIdfUploads(Request $request): \Illuminate\Database\Eloquent\Collection
  {
    $tokens = $request->input('idf_upload_tokens', []);
    if (! is_array($tokens)) {
      throw ValidationException::withMessages([
        'idf_upload_tokens' => 'The staged PDF upload data is invalid. Please select the PDFs again.',
      ]);
    }

    $tokenMap = [];
    if (array_diff(array_keys($tokens), ['attachments', 'technologies']) !== []) {
      throw ValidationException::withMessages(['idf_upload_tokens' => 'The staged PDF upload data is invalid.']);
    }
    if (! is_array($tokens['attachments'] ?? [])) {
      throw ValidationException::withMessages(['idf_upload_tokens' => 'The staged attachment data is invalid.']);
    }
    foreach ($tokens['attachments'] ?? [] as $key => $token) {
      if (! in_array($key, ['deed_assignment', 'additional_signed_sheet', 'sample_photo', 'drawings', 'methodology', 'assistance_form'], true)) {
        throw ValidationException::withMessages(['idf_upload_tokens' => 'An unrecognized staged attachment was submitted.']);
      }
      $tokenMap[] = ['type' => 'attachment', 'key' => $key, 'technology' => null, 'token' => $token];
    }
    if (! is_array($tokens['technologies'] ?? [])) {
      throw ValidationException::withMessages(['idf_upload_tokens' => 'The staged technology PDF data is invalid.']);
    }
    foreach ($tokens['technologies'] ?? [] as $index => $technologyTokens) {
      if (! in_array((string) $index, ['0', '1'], true) || ! is_array($technologyTokens)) {
        throw ValidationException::withMessages(['idf_upload_tokens' => 'The staged technology PDF data is invalid.']);
      }
      foreach ($technologyTokens as $key => $token) {
        if (! in_array($key, self::TECHNOLOGY_FILE_KEYS, true)) {
          throw ValidationException::withMessages(['idf_upload_tokens' => 'An unrecognized technology PDF was submitted.']);
        }
        $tokenMap[] = ['type' => 'technology', 'key' => $key, 'technology' => (int) $index, 'token' => $token];
      }
    }

    foreach ($tokenMap as $entry) {
      if (! is_string($entry['token']) || preg_match('/^[A-Za-z0-9]{64}$/', $entry['token']) !== 1) {
        throw ValidationException::withMessages([
          'idf_upload_tokens' => 'One or more staged PDFs are invalid. Please select those files again.',
        ]);
      }
    }

    if ($tokenMap === []) {
      return new \Illuminate\Database\Eloquent\Collection();
    }

    $hashes = array_map(static fn (array $entry): string => hash('sha256', $entry['token']), $tokenMap);
    $stagedUploads = IdfStagedUpload::query()
      ->where('user_id', $request->user()->id)
      ->whereIn('token_hash', $hashes)
      ->where('expires_at', '>', now())
      ->get()
      ->keyBy('token_hash');

    if ($stagedUploads->count() !== count($tokenMap)) {
      throw ValidationException::withMessages([
        'idf_upload_tokens' => 'One or more staged PDFs have expired. Please select those files again.',
      ]);
    }

    $files = $request->files->all();
    $attachmentFiles = $files['attachments'] ?? [];
    $technologyFiles = $files['technologies'] ?? [];
    foreach ($tokenMap as $entry) {
      $hash = hash('sha256', $entry['token']);
      $stagedUpload = $stagedUploads->get($hash);
      $path = Storage::disk('local')->path($stagedUpload->stored_path);
      if (! is_file($path)) {
        throw ValidationException::withMessages([
          'idf_upload_tokens' => 'A staged PDF is no longer available. Please select that file again.',
        ]);
      }
      $uploadedFile = new UploadedFile(
        $path,
        $stagedUpload->original_name,
        $stagedUpload->mime_type,
        UPLOAD_ERR_OK,
        true
      );
      if ($entry['type'] === 'attachment') {
        $attachmentFiles[$entry['key']] = $uploadedFile;
      } else {
        $technologyFiles[$entry['technology']]['files'][$entry['key']] = $uploadedFile;
      }
    }

    $files['attachments'] = $attachmentFiles;
    $files['technologies'] = $technologyFiles;
    $request->files->replace($files);

    return $stagedUploads;
  }

  private function purgeExpiredStagedUploads(): void
  {
    IdfStagedUpload::query()
      ->where('expires_at', '<=', now())
      ->chunkById(100, function ($stagedUploads): void {
        foreach ($stagedUploads as $stagedUpload) {
          $this->deleteStagedUploadFile($stagedUpload->stored_path);
          $stagedUpload->delete();
        }
      });
  }

  private function deleteStagedUploadFile(string $path): bool
  {
    $disk = Storage::disk('local');
    if (! $disk->exists($path)) {
      return true;
    }
    if ($disk->delete($path)) {
      return true;
    }

    report(new \RuntimeException("The staged IDF PDF could not be removed: {$path}"));

    return false;
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