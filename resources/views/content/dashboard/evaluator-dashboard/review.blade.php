@extends('layouts/contentNavbarLayout')
@section('title', 'Review Submission')

@section('content')
@php
$statusColor = match ($disclosure->status) {
'endorsed', 'filed' => 'success',
'returned' => 'danger',
'under_review' => 'warning',
default => 'primary',
};
$statusLabel = match ($disclosure->status) {
'submitted' => 'For Review',
'under_review' => 'Under review',
'returned' => 'Revision requested',
default => ucfirst($disclosure->status),
};
@endphp

<section class="page-content" data-file-review-saved="{{ session('file_review_saved') ? 'true' : 'false' }}">
  <article class="card mb-6" data-review-section="details">
    <header class="card-header border-0 pb-0">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="min-w-0">
          <h4 class="card-title mb-1 fw-semibold d-flex align-items-center gap-3">
            <span class="avatar avatar-sm">
              <span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-file-list-3-line" aria-hidden="true"></i></span>
            </span>
            <span>Innovation Submission Details</span>
          </h4>
        </div>
        @if ($disclosure->status === 'endorsed')
          <a class="btn btn-outline-primary" href="{{ route('evaluator-dashboard.disclosures.xml', $disclosure) }}" data-save-as-xml><i class="icon-base ri ri-file-download-line me-1" aria-hidden="true"></i>Save as XML</a>
        @endif
      </div>
      <hr class="page-content__divider">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="badge bg-label-{{ $statusColor }}" data-evaluator-review-status>{{ $statusLabel }}</span>
        <span class="small text-body-secondary">Submitted by {{ $disclosure->user->name }} &middot; {{ $disclosure->user->email }}</span>
      </div>
    </header>

    <div class="card-body">
      @include('content.dashboard._disclosure-metadata', ['disclosure' => $disclosure])

      @if ($disclosure->technologies->isNotEmpty())
      <section class="mb-5" aria-labelledby="evaluator-technologies-heading-{{ $disclosure->id }}">
        <h5 class="card-title mb-3" id="evaluator-technologies-heading-{{ $disclosure->id }}">Technical / Creative Descriptions</h5>
        @foreach ($disclosure->technologies as $technology)
        @php $technologyFiles = $technology->files->keyBy('narrative_key'); @endphp
        <article class="border rounded p-3 mb-3">
          <h6 class="fw-bold">{{ $technology->technology_title }}</h6>
          @foreach ([
            'abstract_summary' => 'Abstract / Summary',
            'technical_field' => 'Technical / Creative Field',
            'background_summary' => 'Background / Problem',
            'invention_summary' => 'Summary of the Invention / Innovation',
            'detailed_description' => 'Detailed Description',
            'examples_results' => 'Examples / Experimental Results / Validation',
          ] as $field => $label)
          @php $narrativeFile = $technologyFiles->get($field); @endphp
          @if (filled($technology->{$field}) || $narrativeFile)
          <div class="mb-3">
            <div class="fw-semibold">{{ $label }}</div>
            @if (filled($technology->{$field}))
            <p class="submission-narrative__text mb-1">{{ $technology->{$field} }}</p>
            @endif
            @if ($narrativeFile)
            <a href="{{ route('evaluator-dashboard.technology-files.preview', [$disclosure, $narrativeFile]) }}" target="_blank" rel="noopener noreferrer">
              <i class="icon-base ri ri-file-pdf-2-line me-1" aria-hidden="true"></i>View uploaded PDF: {{ $narrativeFile->original_name }}
            </a>
            @endif
          </div>
          @endif
          @endforeach
        </article>
        @endforeach
      </section>
      @else
      <div class="accordion accordion-flush mb-5" id="evaluator-narratives-{{ $disclosure->id }}">
        @foreach ([
        'background' => ['Background summary', $disclosure->background_summary],
        'description' => ['Description', $disclosure->detailed_description],
        'drawings' => ['Drawing description', $disclosure->drawings_description],
        ] as $key => [$label, $text])
        @if ($text)
        <div class="accordion-item">
          <h2 class="accordion-header" id="evaluator-{{ $key }}-heading-{{ $disclosure->id }}">
            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#evaluator-{{ $key }}-panel-{{ $disclosure->id }}" aria-expanded="false" aria-controls="evaluator-{{ $key }}-panel-{{ $disclosure->id }}">{{ $label }}</button>
          </h2>
          <div id="evaluator-{{ $key }}-panel-{{ $disclosure->id }}" class="accordion-collapse collapse" aria-labelledby="evaluator-{{ $key }}-heading-{{ $disclosure->id }}" data-bs-parent="#evaluator-narratives-{{ $disclosure->id }}">
            <div class="accordion-body">
              <p class="submission-narrative__text mb-0">{{ $text }}</p>
            </div>
          </div>
        </div>
        @endif
        @endforeach
      </div>
      @endif
    </div>
  </article>

  <article class="card mb-6" data-review-section="inventors">
    <header class="card-header border-0 pb-0">
      <h4 class="card-title mb-1 fw-semibold d-flex align-items-center gap-3">
        <span class="avatar avatar-sm">
          <span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-team-line" aria-hidden="true"></i></span>
        </span>
        <span>Inventors</span>
      </h4>
      <hr class="page-content__divider">
    </header>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 inventor-table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Citizenship</th>
              <th>Affiliation</th>
              <th>Primary</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($disclosure->inventors as $inventor)
            <tr>
              <td class="text-break">{{ trim(collect([$inventor->first_name, $inventor->middle_initial, $inventor->last_name, $inventor->suffix])->filter()->implode(' ')) }}</td>
              <td>{{ $inventor->country_of_citizenship }}</td>
              <td>{{ str_replace('_', ' ', ucfirst($inventor->affiliation)) }}</td>
              <td>{{ $inventor->is_primary ? 'Yes' : 'No' }}</td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </article>

  <article class="card mb-6" data-review-section="files">
    <header class="card-header border-0 pb-0">
      <h4 class="card-title mb-1 fw-semibold d-flex align-items-center gap-3">
        <span class="avatar avatar-sm">
          <span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-attachment-2" aria-hidden="true"></i></span>
        </span>
        <span>Supporting files</span>
      </h4>
      <hr class="page-content__divider">
    </header>
    <div class="card-body">
      <form class="evaluator-attachment-review-form" method="POST" action="{{ route('evaluator-dashboard.disclosures.attachments.review', $disclosure) }}" data-confirm-title="Save file reviews?">
        @csrf
        <input type="hidden" name="queue" value="{{ $queue }}">
        <input type="hidden" name="page" value="{{ $page }}">
      <div class="row g-4">
        @forelse ($disclosure->attachments as $attachment)
        @php
        $extension = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));
        $isImage = in_array($extension, ['jpg', 'jpeg', 'png'], true);
        $isPdf = $extension === 'pdf';
        $previewUrl = route('evaluator-dashboard.attachments.preview', [$disclosure, $attachment]);
        $previewModalId = 'attachment-preview-' . $attachment->id;
        @endphp
        <div class="col-md-4">
          <div class="border rounded p-3 evaluator-file-card {{ $attachment->is_latest_submission && $attachment->version > 1 ? 'evaluator-file-card--revised' : '' }}">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
              <span class="fw-medium evaluator-file-card__type">{{ ucfirst(str_replace('_', ' ', $attachment->type)) }}</span>
              <div class="d-flex flex-wrap gap-2 evaluator-file-card__actions" data-attachment-review-state>
              @if ($attachment->is_latest_submission)
              <span class="badge bg-label-success align-self-center" data-latest-submission-badge>Latest</span>
              @endif
              @if ($attachment->needs_revision)
              <span class="badge bg-label-warning align-self-center">Revision requested</span>
              @endif
              </div>
            </div>
            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
              <span class="small evaluator-file-card__name" title="{{ $attachment->original_name }}">{{ $attachment->original_name }}</span>
              <button class="btn btn-link btn-sm p-0 text-primary text-decoration-none flat-icon-button flex-shrink-0" type="button" data-bs-toggle="modal" data-bs-target="#{{ $previewModalId }}" aria-label="Preview {{ $attachment->original_name }}" title="Preview file">
                <i class="icon-base ri ri-eye-line icon-24px" aria-hidden="true"></i>
              </button>
            </div>
            <span class="small text-body-secondary evaluator-file-card__meta mb-3">{{ ($attachment->submitted_at ?? $attachment->created_at)->format('M j, Y') }} &middot; {{ number_format($attachment->size_bytes / 1048576, 2) }} MB</span>
            <div class="form-check mb-2">
              <input
                class="form-check-input evaluator-file-revision-toggle"
                id="attachment-revision-{{ $attachment->id }}"
                name="attachments[{{ $attachment->id }}][needs_revision]"
                type="checkbox"
                value="1"
                data-attachment-id="{{ $attachment->id }}"
                @checked($attachment->needs_revision)
                @disabled($attachment->needs_revision)>
              <label class="form-check-label" for="attachment-revision-{{ $attachment->id }}">Needs revision</label>
            </div>
            <div class="evaluator-file-revision-notes {{ $attachment->needs_revision ? '' : 'd-none' }}">
              <label class="form-label small" for="attachment-notes-{{ $attachment->id }}">What needs to change?</label>
              <textarea
                class="form-control form-control-sm mb-3"
                id="attachment-notes-{{ $attachment->id }}"
                name="attachments[{{ $attachment->id }}][revision_notes]"
                rows="2"
                maxlength="2000"
                @required($attachment->needs_revision)
                @disabled($attachment->needs_revision)>{{ $attachment->revision_notes }}</textarea>
            </div>
          </div>
        </div>
        <div class="modal fade" id="{{ $previewModalId }}" tabindex="-1" aria-labelledby="{{ $previewModalId }}-title" aria-hidden="true">
          <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="{{ $previewModalId }}-title">{{ $attachment->original_name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                @if ($isPdf)
                <iframe class="attachment-preview-frame" src="{{ $previewUrl }}" title="Preview of {{ $attachment->original_name }}"></iframe>
                @elseif ($isImage)
                <img class="img-fluid d-block mx-auto" src="{{ $previewUrl }}" alt="Preview of {{ $attachment->original_name }}">
                @else
                <div class="alert alert-info mb-0" role="status">
                  In-browser preview is not available for this file type. PDF and image files can be previewed here.
                </div>
                @endif
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        @empty
        <div class="col-12 text-body-secondary">No supporting files attached.</div>
        @endforelse
      </div>
      <div class="d-flex justify-content-end mt-5">
        <button class="btn btn-primary evaluator-file-card__save" type="submit" disabled title="Flag at least one file as needing revision to save file reviews.">
          <i class="icon-base ri ri-save-line me-1" aria-hidden="true"></i>Save File Review
        </button>
      </div>
      </form>
    </div>
  </article>

  <article class="card mb-6" data-review-section="decision">
    <header class="card-header border-0 pb-0">
      <h4 class="card-title mb-1 fw-semibold d-flex align-items-center gap-3">
        <span class="avatar avatar-sm">
          <span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-checkbox-circle-line" aria-hidden="true"></i></span>
        </span>
        <span>Review decision</span>
      </h4>
      <hr class="page-content__divider">
    </header>
    <div class="card-body">
      <form class="evaluator-review-form" method="POST" action="{{ route('evaluator-dashboard.review', $disclosure) }}" data-has-pending-file-revisions="{{ $disclosure->attachments->contains('needs_revision', true) ? 'true' : 'false' }}">
        @csrf
        <input type="hidden" name="queue" value="{{ $queue }}">
        <input type="hidden" name="page" value="{{ $page }}">
        <div class="row g-4 align-items-end">
          <div class="col-md-4">
            <label class="form-label" for="review-status-{{ $disclosure->id }}">Decision</label>
            <select class="form-select evaluator-review-status" id="review-status-{{ $disclosure->id }}" name="status" required>
              <option value="">Choose a decision</option>
              <option value="under_review" @selected($disclosure->status === 'under_review')>Under review</option>
              <option value="returned" @selected($disclosure->status === 'returned')>Request revision</option>
              <option value="endorsed" @selected($disclosure->status === 'endorsed')>Endorse</option>
            </select>
          </div>
          <div class="col-md-8 evaluator-review-notes-wrap {{ $disclosure->status === 'returned' ? '' : 'd-none' }}">
            <label class="form-label" for="review-notes-{{ $disclosure->id }}">Revision notes</label>
            <textarea class="form-control evaluator-review-notes" id="review-notes-{{ $disclosure->id }}" name="review_notes" rows="2" maxlength="3000" @required($disclosure->status === 'returned')>{{ $disclosure->review_notes }}</textarea>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button class="btn btn-primary" type="submit"><i class="icon-base ri ri-check-line me-1"></i>Save review</button>
          </div>
        </div>
      </form>
    </div>
  </article>
</section>
@endsection

@section('page-script')
@vite(['resources/assets/js/evaluator-dashboard.js'])
@endsection