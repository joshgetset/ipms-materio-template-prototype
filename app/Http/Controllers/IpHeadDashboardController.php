<?php

namespace App\Http\Controllers;

use App\Models\InnovationDisclosure;
use App\Models\DisclosureAttachment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use XMLWriter;

class IpHeadDashboardController extends Controller
{
  public function index(Request $request): View
  {
    $this->ensureIpHead($request);

    return view('content.dashboard.ip-head.ip-head-index', [
      'statusCounts' => InnovationDisclosure::query()
        ->selectRaw('status, count(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status'),
    ]);
  }

  public function submissions(Request $request): View
  {
    $this->ensureIpHead($request);

    return view('content.dashboard.ip-head.ip-head-submissions', [
      'reviewQueue' => InnovationDisclosure::query()
        ->with(['user', 'attachments', 'inventors'])
        ->whereIn('status', ['submitted', 'under_review', 'revised_submission'])
        ->latest()
        ->paginate(20, ['*'], 'reviewQueuePage'),
      'reviewedSubmissions' => InnovationDisclosure::query()
        ->with(['user', 'attachments', 'inventors'])
        ->whereIn('status', ['returned', 'endorsed', 'filed'])
        ->latest()
        ->paginate(20, ['*'], 'reviewedPage'),
    ]);
  }

  public function showReview(Request $request, InnovationDisclosure $disclosure): View
  {
    $this->ensureIpHead($request);
    $disclosure->load(['user', 'attachments', 'inventors', 'costItems', 'technologies.files.versions']);
    $reviewableAttachments = $this->reviewableAttachments($disclosure);
    $queue = $request->query('queue') === 'reviewed' ? 'reviewed' : 'for-review';
    $pageParameter = $queue === 'reviewed' ? 'reviewedPage' : 'reviewQueuePage';

    return view('content.dashboard.ip-head.ip-head-review', [
      'disclosure' => $disclosure,
      'page' => max(1, $request->integer('page', $request->integer($pageParameter, 1))),
      'pageParameter' => $pageParameter,
      'queue' => $queue,
      'reviewableAttachments' => $reviewableAttachments,
    ]);
  }

  public function exportAsXml(Request $request, InnovationDisclosure $disclosure): StreamedResponse
  {
    $this->ensureIpHead($request);
    abort_unless($disclosure->status === 'endorsed', 409, 'Only endorsed submissions can be exported as XML.');

    $disclosure->load(['user', 'inventors', 'attachments', 'technologies.files']);
    $primaryInventor = $disclosure->inventors->firstWhere('is_primary', true);
    abort_unless($primaryInventor, 409, 'The submission must have a primary inventor before it can be exported.');

    $endorsementDate = ($disclosure->endorsed_at ?? $disclosure->updated_at)->format('Y-m-d');
    $filename = implode('_', [
      $this->filenameSegment($disclosure->technology_title, 120),
      $this->filenameSegment($primaryInventor->last_name, 60),
      $endorsementDate,
    ]) . '.xml';

    $xml = new XMLWriter();
    $xml->openMemory();
    $xml->setIndent(true);
    $xml->startDocument('1.0', 'UTF-8');
    $xml->startElement('eFiling');
    $this->writeXmlValue($xml, 'RefNo', '');
    $this->writeXmlValue($xml, 'RefDate', now()->format('n/j/Y g:i:s A'));
    $this->writeXmlValue($xml, 'Title', $disclosure->technology_title);
    $this->writeXmlValue($xml, 'Category', match ($disclosure->technology_type) {
      'chemical' => 'Chemical',
      'mechanical', 'mechanical_engineering' => 'Mechanical',
      default => null,
    });
    $this->writeXmlValue($xml, 'Route', '');

    $xml->startElement('Applicant');
    $this->writeXmlValue($xml, 'Type', '');
    $this->writeXmlValue($xml, 'Name', $disclosure->user->name);
    $this->writeXmlValue($xml, 'CompanyName', '');
    $this->writeXmlValue($xml, 'LastName', '');
    $this->writeXmlValue($xml, 'FirstName', '');
    $this->writeXmlValue($xml, 'MI', '');
    $this->writeXmlValue($xml, 'CountryIncorporationOrCitizenship', '');
    $this->writeXmlValue($xml, 'CountryIncorporationOrCitizenship_Code', '');
    $this->writeEfilingAddress($xml);
    $this->writeXmlValue($xml, 'eMail', $disclosure->email);
    $this->writeXmlValue($xml, 'ContactNo', $disclosure->mobile_no);
    $this->writeXmlValue($xml, 'ApplicantMaker', '');
    $xml->endElement();

    foreach ($disclosure->inventors as $inventor) {
      $xml->startElement('Maker');
      $this->writeXmlValue($xml, 'Name', $inventor->full_name ?: implode(', ', array_filter([
        $inventor->last_name,
        trim(implode(' ', array_filter([$inventor->first_name, $inventor->middle_initial]))),
      ])));
      $this->writeXmlValue($xml, 'LastName', $inventor->last_name);
      $this->writeXmlValue($xml, 'FirstName', $inventor->first_name);
      $this->writeXmlValue($xml, 'MI', $inventor->middle_initial ?? '');
      $this->writeXmlValue($xml, 'CountryCitizenship', $inventor->country_of_citizenship);
      $this->writeXmlValue($xml, 'CountryCitizenship_Code', '');
      $this->writeEfilingAddress($xml);
      $this->writeXmlValue($xml, 'eMail', '');
      $xml->endElement();
    }

    foreach ($disclosure->attachments->where('type', 'drawings') as $drawing) {
      if (strtolower(pathinfo($drawing->original_name, PATHINFO_EXTENSION)) !== 'pdf') {
        continue;
      }

      $xml->startElement('Document');
      $this->writeXmlValue($xml, 'UserDoc', 'Drawing');
      $this->writeXmlValue($xml, 'Type', 'Drawing(s)');
      $this->writeXmlValue($xml, 'FileName', $drawing->original_name);
      $this->writeXmlValue($xml, 'Pages', '');
      $xml->endElement();
    }

    $xml->startElement('IPMSData');
    $this->writeModel($xml, 'disclosure', $disclosure);
    $xml->startElement('technologies');
    foreach ($disclosure->technologies as $technology) {
      $xml->startElement('technology');
      $this->writeModel($xml, 'technology', $technology);
      $xml->startElement('files');
      foreach ($technology->files as $file) {
        $xml->startElement('file');
        $this->writeXmlValue($xml, 'narrative_key', $file->narrative_key);
        $this->writeXmlValue($xml, 'original_name', $file->original_name);
        $this->writeXmlValue($xml, 'mime_type', $file->mime_type);
        $xml->endElement();
      }
      $xml->endElement();
      $xml->endElement();
    }
    $xml->endElement();
    $xml->startElement('inventors');
    foreach ($disclosure->inventors as $inventor) {
      $this->writeModel($xml, 'inventor', $inventor);
    }
    $xml->endElement();
    $xml->startElement('attachments');
    foreach ($disclosure->attachments as $attachment) {
      $this->writeModel($xml, 'attachment', $attachment);
    }
    $xml->endElement();
    $xml->endElement();

    $xml->endElement();
    $xml->endDocument();
    $contents = $xml->outputMemory();

    return response()->streamDownload(
      static function () use ($contents): void {
        echo $contents;
      },
      $filename,
      ['Content-Type' => 'application/xml; charset=UTF-8']
    );
  }

  public function updateReview(Request $request, InnovationDisclosure $disclosure): RedirectResponse|JsonResponse
  {
    $this->ensureIpHead($request);

    $validated = $request->validate([
      'status' => ['required', Rule::in(['endorsed'])],
      'queue' => ['nullable', Rule::in(['for-review', 'reviewed'])],
      'page' => ['nullable', 'integer', 'min:1'],
    ]);

    DB::transaction(function () use ($disclosure): void {
      $lockedDisclosure = InnovationDisclosure::query()->lockForUpdate()->findOrFail($disclosure->id);
      if (! $this->allSubmittedFilesApproved($lockedDisclosure)) {
        throw ValidationException::withMessages([
          'status' => 'Approve every current submitted file before approving this submission.',
        ]);
      }

      $lockedDisclosure->update([
        'status' => 'endorsed',
        'endorsed_at' => $lockedDisclosure->endorsed_at ?? now(),
        'review_notes' => null,
      ]);
    });
    $disclosure->refresh();

    if ($request->expectsJson()) {
      return response()->json([
        'message' => 'The submitted IDF has been approved.',
        'disclosure' => [
          'id' => $disclosure->id,
          'status' => $disclosure->status,
          'review_notes' => $disclosure->review_notes,
        ],
      ]);
    }

    return redirect()
      ->route('ip-head-dashboard.submissions', [
        'queue' => $request->input('queue') === 'reviewed' ? 'reviewed' : 'for-review',
        ...$this->dashboardPageParameters($request),
      ])
      ->with('toast_type', 'success')
      ->with('toast_message', 'Submitted IDF approved.');
  }

  public function updateAttachmentReview(Request $request, DisclosureAttachment $attachment): RedirectResponse|JsonResponse
  {
    $this->ensureIpHead($request);
    abort_unless($attachment->innovationDisclosure()->value('status') !== 'endorsed', 409);
    abort_unless(! $attachment->needs_revision, 409);
    abort_unless($attachment->review_status !== 'accepted', 409);

    $validated = $request->validate([
      'needs_revision' => ['required', 'accepted'],
      'revision_notes' => [
        Rule::requiredIf($request->boolean('needs_revision')),
        'nullable',
        'string',
        'max:2000',
      ],
      'queue' => ['nullable', Rule::in(['for-review', 'reviewed'])],
      'page' => ['nullable', 'integer', 'min:1'],
    ]);

    DB::transaction(function () use ($attachment, $validated): void {
      $disclosure = InnovationDisclosure::query()->lockForUpdate()->findOrFail($attachment->innovation_disclosure_id);
      abort_unless($disclosure->status !== 'endorsed', 409);
      $attachment = $disclosure->attachments()->lockForUpdate()->findOrFail($attachment->id);
      abort_unless(! $attachment->needs_revision, 409);
      abort_unless($attachment->review_status !== 'accepted', 409);

      $attachment->update([
        'needs_revision' => true,
        'review_status' => 'revision_requested',
        'revision_notes' => $validated['revision_notes'],
        'is_latest_submission' => false,
      ]);
      $attachment->events()->create([
        'event_type' => 'revision_requested',
        'version' => $attachment->version,
        'original_name' => $attachment->original_name,
        'revision_notes' => $attachment->revision_notes,
        'occurred_at' => $attachment->updated_at,
      ]);
    });

    $attachment->refresh();
    $queue = $request->input('queue') === 'reviewed' ? 'reviewed' : 'for-review';
    $page = max(1, $request->integer('page', 1));

    if ($request->expectsJson()) {
      return response()->json([
        'message' => 'File review saved.',
        'attachment' => [
          'id' => $attachment->id,
          'needs_revision' => $attachment->needs_revision,
          'review_status' => $attachment->review_status,
          'revision_notes' => $attachment->revision_notes,
          'is_latest_submission' => $attachment->is_latest_submission,
        ],
      ]);
    }

    return redirect()
      ->route('ip-head-dashboard.disclosures.show', [
        'disclosure' => $attachment->innovation_disclosure_id,
        'queue' => $queue,
        'page' => $page,
      ])
      ->with('file_review_saved', true);
  }

  public function updateDisclosureAttachmentReviews(Request $request, InnovationDisclosure $disclosure): JsonResponse
  {
    $this->ensureIpHead($request);

    $validated = $request->validate([
      'attachments' => ['sometimes', 'array', 'min:1'],
      'attachments.*.status' => ['required', Rule::in(['review_needed', 'revision_requested', 'accepted'])],
      'attachments.*.revision_notes' => ['nullable', 'string', 'max:2000'],
      'technology_files' => ['sometimes', 'array', 'min:1'],
      'technology_files.*.status' => ['required', Rule::in(['review_needed', 'revision_requested', 'accepted'])],
      'technology_files.*.revision_notes' => ['nullable', 'string', 'max:2000'],
      'technology_files.*.revision_guidance_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:15360'],
    ]);

    $attachmentReviews = $validated['attachments'] ?? [];
    $technologyFileReviews = $validated['technology_files'] ?? [];
    if ($attachmentReviews === [] && $technologyFileReviews === []) {
      throw ValidationException::withMessages([
        'technology_files' => 'Select a file status before saving the review.',
      ]);
    }
    foreach ($technologyFileReviews as $review) {
      if ($review['status'] === 'revision_requested' && blank($review['revision_notes'] ?? null)) {
        throw ValidationException::withMessages([
          'technology_files' => 'Add revision notes for each file marked as needing revision.',
        ]);
      }
      if (
        isset($review['revision_guidance_pdf'])
        && $review['status'] !== 'revision_requested'
      ) {
        throw ValidationException::withMessages([
          'technology_files' => 'A noted PDF can only be uploaded when requesting a revision.',
        ]);
      }
      if (
        ($review['revision_guidance_pdf'] ?? null) instanceof UploadedFile
        && mb_strlen($review['revision_guidance_pdf']->getClientOriginalName()) > 255
      ) {
        throw ValidationException::withMessages([
          'technology_files' => 'The noted PDF file name may not exceed 255 characters.',
        ]);
      }
    }
    foreach ($attachmentReviews as $review) {
      if ($review['status'] === 'revision_requested' && blank($review['revision_notes'] ?? null)) {
        throw ValidationException::withMessages([
          'attachments' => 'Add revision notes for each file marked as needing revision.',
        ]);
      }
    }

    $newGuidancePaths = [];
    $oldGuidancePaths = [];
    try {
      DB::transaction(function () use (
        $disclosure,
        $attachmentReviews,
        $technologyFileReviews,
        &$newGuidancePaths,
        &$oldGuidancePaths
      ): void {
        $lockedDisclosure = InnovationDisclosure::query()->lockForUpdate()->findOrFail($disclosure->id);
        abort_unless($lockedDisclosure->status !== 'endorsed', 409);

        foreach ($attachmentReviews as $attachmentId => $review) {
          abort_unless(ctype_digit((string) $attachmentId), 422);
          $attachment = $disclosure->attachments()->lockForUpdate()->findOrFail($attachmentId);
          abort_unless($attachment->is_latest_submission && ! $attachment->needs_revision, 409);
          if ($attachment->review_status === 'accepted') {
            abort_unless($review['status'] === 'accepted', 409);
            continue;
          }
          $wasRevisionRequested = $review['status'] === 'revision_requested';
          $attachment->update([
            'review_status' => $review['status'],
            'needs_revision' => $wasRevisionRequested,
            'revision_notes' => $wasRevisionRequested ? $review['revision_notes'] : null,
            'is_latest_submission' => ! $wasRevisionRequested,
          ]);
          if ($wasRevisionRequested) {
            $attachment->events()->create([
              'event_type' => 'revision_requested',
              'version' => $attachment->version,
              'original_name' => $attachment->original_name,
              'revision_notes' => $attachment->revision_notes,
              'occurred_at' => $attachment->updated_at,
            ]);
          }
        }
        foreach ($technologyFileReviews as $fileId => $review) {
          abort_unless(ctype_digit((string) $fileId), 422);
          $technology = $disclosure->technologies()
            ->whereHas('files', fn ($query) => $query->whereKey($fileId))
            ->firstOrFail();
          $file = $technology->files()->lockForUpdate()->findOrFail($fileId);
          if ($file->review_status === 'accepted') {
            abort_unless($review['status'] === 'accepted', 409);
            continue;
          }
          $isRevisionRequested = $review['status'] === 'revision_requested';
          $guidancePdf = $review['revision_guidance_pdf'] ?? null;
          $guidancePath = $isRevisionRequested ? $file->revision_guidance_path : null;
          $guidanceName = $isRevisionRequested ? $file->revision_guidance_name : null;

          if ($guidancePdf instanceof UploadedFile) {
            $guidancePath = $guidancePdf->store(
              "innovation-disclosures/{$lockedDisclosure->id}/technologies/{$technology->id}/revision-guidance",
              'local'
            );
            if (! is_string($guidancePath)) {
              throw new \RuntimeException('The noted PDF could not be stored.');
            }
            $newGuidancePaths[] = $guidancePath;
            $guidanceName = $guidancePdf->getClientOriginalName();
          }

          if (
            is_string($file->revision_guidance_path)
            && $file->revision_guidance_path !== $guidancePath
          ) {
            $oldGuidancePaths[] = $file->revision_guidance_path;
          }

          $file->update([
            'review_status' => $review['status'],
            'revision_notes' => $isRevisionRequested
              ? $review['revision_notes']
              : null,
            'revision_guidance_path' => $guidancePath,
            'revision_guidance_name' => $guidanceName,
          ]);
        }
      });
    } catch (\Throwable $exception) {
      Storage::disk('local')->delete($newGuidancePaths);
      throw $exception;
    }
    Storage::disk('local')->delete($oldGuidancePaths);

    return response()->json([
      'message' => 'File reviews saved.',
      'reviewed_count' => count($attachmentReviews) + count($technologyFileReviews),
    ]);
  }

  public function previewAttachment(
    Request $request,
    InnovationDisclosure $disclosure,
    DisclosureAttachment $attachment
  ): BinaryFileResponse {
    $this->ensureIpHead($request);
    $ownedAttachment = $disclosure->attachments()->findOrFail($attachment->id);
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

  private function allSubmittedFilesApproved(InnovationDisclosure $disclosure): bool
  {
    $disclosure->loadMissing(['attachments', 'technologies.files']);
    $reviewableAttachments = $this->reviewableAttachments($disclosure);
    $latestAttachments = $reviewableAttachments->where('is_latest_submission', true);
    $hasTechnologyFiles = $disclosure->technologies->contains(fn ($technology) => $technology->files->isNotEmpty());
    $hasFiles = $hasTechnologyFiles || $latestAttachments->isNotEmpty()
      || $reviewableAttachments->contains(fn ($attachment) => $attachment->needs_revision);

    if (! $hasFiles) {
      return false;
    }

    return ! $reviewableAttachments->contains(fn ($attachment) => $attachment->needs_revision)
      && ! $latestAttachments->contains(fn ($attachment) => $attachment->review_status !== 'accepted')
      && ! $disclosure->technologies()
        ->whereHas('files', fn ($query) => $query->where('review_status', '!=', 'accepted'))
        ->exists();
  }

  private function reviewableAttachments(InnovationDisclosure $disclosure): Collection
  {
    $technologyFileNames = $disclosure->technologies
      ->flatMap(fn ($technology) => $technology->files->pluck('original_name'))
      ->map(fn ($fileName) => Str::lower(trim($fileName)))
      ->flip();

    return $disclosure->attachments
      ->filter(fn ($attachment) => $attachment->is_latest_submission || $attachment->needs_revision)
      ->reject(fn ($attachment) => $technologyFileNames->has(Str::lower(trim($attachment->original_name))));
  }

  private function ensureIpHead(Request $request): void
  {
    abort_unless($request->user()?->is_ip_head, 403);
  }

  private function writeModel(XMLWriter $xml, string $element, Model $model): void
  {
    $xml->startElement($element);

    foreach ($model->getAttributes() as $name => $value) {
      if ($value === null) {
        $xml->writeElement($name, '');
      } else {
        $xml->writeElement($name, (string) $value);
      }
    }

    $xml->endElement();
  }

  private function writeXmlValue(XMLWriter $xml, string $element, ?string $value): void
  {
    if ($value === null) {
      $xml->writeElement($element, '');

      return;
    }

    $xml->writeElement($element, $value);
  }

  private function writeEfilingAddress(XMLWriter $xml): void
  {
    $xml->startElement('Address');
    foreach ([
      'CompleteAddress',
      'Street',
      'TownCity',
      'Country',
      'Country_Code',
      'Province',
      'Province_Code',
      'StateProvince',
      'ZipCode',
    ] as $part) {
      $this->writeXmlValue($xml, $part, '');
    }
    $xml->endElement();
  }

  private function filenameSegment(string $value, int $maxLength): string
  {
    $segment = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]+/', '-', trim($value));
    abort_unless($segment !== null, 500, 'The XML export filename could not be created.');

    $segment = trim(Str::substr($segment, 0, $maxLength), " .-");
    abort_unless($segment !== '', 409, 'The submission is missing a required filename value.');

    return $segment;
  }

  private function dashboardPageParameters(Request $request): array
  {
    $queue = $request->input('queue') === 'reviewed' ? 'reviewed' : 'for-review';
    $page = max(1, $request->integer('page', 1));

    if ($page === 1) {
      return [];
    }

    return [$queue === 'reviewed' ? 'reviewedPage' : 'reviewQueuePage' => $page];
  }
}
