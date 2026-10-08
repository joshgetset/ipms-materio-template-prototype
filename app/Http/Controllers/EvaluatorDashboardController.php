<?php

namespace App\Http\Controllers;

use App\Models\InnovationDisclosure;
use App\Models\DisclosureAttachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use XMLWriter;

class EvaluatorDashboardController extends Controller
{
  public function index(Request $request): View
  {
    $this->ensureEvaluator($request);

    return view('content.dashboard.evaluator-dashboard.index', [
      'statusCounts' => InnovationDisclosure::query()
        ->selectRaw('status, count(*) as total')
        ->groupBy('status')
        ->pluck('total', 'status'),
    ]);
  }

  public function submissions(Request $request): View
  {
    $this->ensureEvaluator($request);

    return view('content.dashboard.evaluator-dashboard.submissions', [
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
    $this->ensureEvaluator($request);
    $disclosure->load(['user', 'attachments', 'inventors', 'technologies.files']);
    $queue = $request->query('queue') === 'reviewed' ? 'reviewed' : 'for-review';
    $pageParameter = $queue === 'reviewed' ? 'reviewedPage' : 'reviewQueuePage';

    return view('content.dashboard.evaluator-dashboard.review', [
      'disclosure' => $disclosure,
      'page' => max(1, $request->integer('page', $request->integer($pageParameter, 1))),
      'pageParameter' => $pageParameter,
      'queue' => $queue,
    ]);
  }

  public function exportAsXml(Request $request, InnovationDisclosure $disclosure): StreamedResponse
  {
    $this->ensureEvaluator($request);
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
    $this->ensureEvaluator($request);

    $validated = $request->validate([
      'status' => ['required', Rule::in(['under_review', 'returned', 'endorsed'])],
      'review_notes' => [
        Rule::requiredIf($request->input('status') === 'returned'),
        'nullable',
        'string',
        'max:3000',
      ],
      'queue' => ['nullable', Rule::in(['for-review', 'reviewed'])],
      'page' => ['nullable', 'integer', 'min:1'],
    ]);

    if (
      $validated['status'] === 'endorsed'
      && $disclosure->attachments()->where('needs_revision', true)->exists()
    ) {
      throw ValidationException::withMessages([
        'status' => 'This submission cannot be endorsed while any files still need revision.',
      ]);
    }

    $endorsedAt = $validated['status'] === 'endorsed'
      ? ($disclosure->endorsed_at ?? now())
      : null;

    $disclosure->update([
      'status' => $validated['status'],
      'endorsed_at' => $endorsedAt,
      'review_notes' => $validated['status'] === 'returned'
        ? $validated['review_notes']
        : null,
    ]);

    if ($request->expectsJson()) {
      return response()->json([
        'message' => 'Disclosure review updated.',
        'disclosure' => [
          'id' => $disclosure->id,
          'status' => $disclosure->status,
          'review_notes' => $disclosure->review_notes,
        ],
      ]);
    }

    return redirect()
      ->route('evaluator-dashboard.submissions', [
        'queue' => $request->input('queue') === 'reviewed' ? 'reviewed' : 'for-review',
        ...$this->dashboardPageParameters($request),
      ])
      ->with('toast_type', 'success')
      ->with('toast_message', 'Disclosure review updated.');
  }

  public function updateAttachmentReview(Request $request, DisclosureAttachment $attachment): RedirectResponse|JsonResponse
  {
    $this->ensureEvaluator($request);
    abort_unless(! $attachment->needs_revision, 409);

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

    $attachment->update([
      'needs_revision' => true,
      'revision_notes' => $validated['revision_notes'],
      'is_latest_submission' => false,
    ]);

    $queue = $request->input('queue') === 'reviewed' ? 'reviewed' : 'for-review';
    $page = max(1, $request->integer('page', 1));

    if ($request->expectsJson()) {
      return response()->json([
        'message' => 'File review saved.',
        'attachment' => [
          'id' => $attachment->id,
          'needs_revision' => $attachment->needs_revision,
          'revision_notes' => $attachment->revision_notes,
          'is_latest_submission' => $attachment->is_latest_submission,
        ],
      ]);
    }

    return redirect()
      ->route('evaluator-dashboard.disclosures.show', [
        'disclosure' => $attachment->innovation_disclosure_id,
        'queue' => $queue,
        'page' => $page,
      ])
      ->with('file_review_saved', true);
  }

  public function updateDisclosureAttachmentReviews(Request $request, InnovationDisclosure $disclosure): JsonResponse
  {
    $this->ensureEvaluator($request);

    $validated = $request->validate([
      'attachments' => ['required', 'array', 'min:1'],
      'attachments.*.needs_revision' => ['required', 'accepted'],
      'attachments.*.revision_notes' => ['required', 'string', 'max:2000'],
    ]);

    DB::transaction(function () use ($disclosure, $validated): void {
      foreach ($validated['attachments'] as $attachmentId => $review) {
        abort_unless(ctype_digit((string) $attachmentId), 422);
        $attachment = $disclosure->attachments()->lockForUpdate()->findOrFail($attachmentId);
        abort_unless(! $attachment->needs_revision, 409);

        $attachment->update([
          'needs_revision' => true,
          'revision_notes' => $review['revision_notes'],
          'is_latest_submission' => false,
        ]);
      }
    });

    return response()->json([
      'message' => 'File reviews saved.',
      'reviewed_count' => count($validated['attachments']),
    ]);
  }

  public function previewAttachment(
    Request $request,
    InnovationDisclosure $disclosure,
    DisclosureAttachment $attachment
  ): BinaryFileResponse {
    $this->ensureEvaluator($request);
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

  private function ensureEvaluator(Request $request): void
  {
    abort_unless($request->user()?->is_evaluator, 403);
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
