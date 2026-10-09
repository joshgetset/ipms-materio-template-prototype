@extends('layouts/contentNavbarLayout')
@section('title', 'Review Submission')

@section('page-style')
<link rel="stylesheet" href="{{ asset('user/css/innovation-disclosure.css') }}">
@endsection

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
  'revised_submission' => 'Revised submission',
  'returned' => 'Revision requested',
  'endorsed' => 'Endorsed',
  'filed' => 'Filed',
  default => ucfirst($disclosure->status),
};
$metadata = [
  'Lead creator' => $disclosure->lead_creator_name,
  'Technology category' => str_replace('_', ' ', ucfirst($disclosure->technology_type)),
  'Relationship to SLSU' => str_replace('_', ' ', ucfirst($disclosure->university_relationship)),
  'Other relationship' => $disclosure->other_relationship,
  'Email' => $disclosure->email,
  'Contact number' => $disclosure->mobile_no,
  'Research title' => $disclosure->research_title,
  'Funding source' => $disclosure->funding_source,
  'Date of creation' => $disclosure->date_of_creation?->format('M j, Y'),
  'Research approval date' => $disclosure->research_approval_date?->format('M j, Y'),
  'Disclosure date' => $disclosure->disclosure_date?->format('M j, Y'),
  'Ownership declaration' => str_replace('_', ' ', ucfirst($disclosure->ownership_declaration)),
  'Substantial SLSU support' => $disclosure->has_substantial_support === null
    ? 'Not recorded'
    : ($disclosure->has_substantial_support ? 'Yes' : 'No'),
  'Ownership consent' => $disclosure->ownership_consent ? 'Confirmed' : 'Not recorded',
  'Submitted' => $disclosure->created_at->format('M j, Y g:i A'),
];
$legacyNarratives = [
  'Background summary' => $disclosure->background_summary,
  'Description' => $disclosure->detailed_description,
  'Drawing description' => $disclosure->drawings_description,
];
@endphp

<section class="page-content ip-head-review-page" data-file-review-saved="{{ session('file_review_saved') ? 'true' : 'false' }}">
  <header class="card mb-4 ip-head-review-hero">
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-4">
        <div class="d-flex align-items-start gap-3 min-w-0">
          <span class="avatar avatar-md flex-shrink-0">
            <span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-file-list-3-line" aria-hidden="true"></i></span>
          </span>
          <div class="min-w-0">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="badge bg-label-{{ $statusColor }}" data-ip-head-review-status>{{ $statusLabel }}</span>
              <span class="small text-body-secondary">Submitted {{ $disclosure->created_at->diffForHumans() }}</span>
            </div>
            <h3 class="mb-2 fw-semibold ip-head-review-title">{{ $disclosure->technology_title }}</h3>
            <p class="mb-0 text-body-secondary">
              <i class="icon-base ri ri-user-3-line me-1" aria-hidden="true"></i>
              {{ $disclosure->user->name }} <span class="mx-1">&middot;</span> {{ $disclosure->user->email }}
            </p>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-2 flex-shrink-0">
          <a class="btn btn-outline-secondary" href="{{ route('ip-head-dashboard.submissions', [$pageParameter => $page]) }}">
            <i class="icon-base ri ri-arrow-left-line me-1" aria-hidden="true"></i>Back to submissions
          </a>
          @if ($disclosure->status === 'endorsed')
          <a class="btn btn-outline-primary" href="{{ route('ip-head-dashboard.disclosures.xml', $disclosure) }}" data-save-as-xml>
            <i class="icon-base ri ri-file-download-line me-1" aria-hidden="true"></i>Save as XML
          </a>
          @endif
        </div>
      </div>
    </div>
  </header>

  <nav class="ip-head-review-nav mb-4" aria-label="Review submission sections">
    <a class="btn btn-sm btn-label-primary" href="#review-overview"><i class="icon-base ri ri-information-line me-1" aria-hidden="true"></i>Overview</a>
    <a class="btn btn-sm btn-label-primary" href="#review-technologies"><i class="icon-base ri ri-lightbulb-line me-1" aria-hidden="true"></i>Technologies</a>
    <a class="btn btn-sm btn-label-primary" href="#review-inventors"><i class="icon-base ri ri-team-line me-1" aria-hidden="true"></i>Creators</a>
    <a class="btn btn-sm btn-label-primary" href="#review-costing"><i class="icon-base ri ri-money-dollar-circle-line me-1" aria-hidden="true"></i>Costing</a>
    <a class="btn btn-sm btn-label-primary" href="#review-files"><i class="icon-base ri ri-attachment-2 me-1" aria-hidden="true"></i>Files</a>
    <a class="btn btn-sm btn-label-primary" href="#review-decision"><i class="icon-base ri ri-checkbox-circle-line me-1" aria-hidden="true"></i>Decision</a>
  </nav>

  <article class="card mb-4 ip-head-review-section" id="review-overview" data-review-section="details">
    <header class="card-header">
      <div class="d-flex align-items-center gap-3">
        <span class="avatar avatar-sm"><span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-information-line" aria-hidden="true"></i></span></span>
        <div>
          <h4 class="card-title mb-0 fw-semibold">Submission overview</h4>
        </div>
      </div>
    </header>
    <div class="card-body">
      <div class="ip-head-review-summary mb-4">
        <div class="ip-head-review-summary__item">
          <span class="ip-head-review-summary__label">Research title</span>
          <strong>{{ $disclosure->research_title ?: 'Not provided' }}</strong>
        </div>
        <div class="ip-head-review-summary__item">
          <span class="ip-head-review-summary__label">College / Department</span>
          <strong>{{ $disclosure->college_department ?: 'Not provided' }}</strong>
        </div>
        <div class="ip-head-review-summary__item">
          <span class="ip-head-review-summary__label">Campus</span>
          <strong>{{ $disclosure->campus ?: 'Not provided' }}</strong>
        </div>
      </div>
      <div class="row g-3 ip-head-review-metadata">
        @foreach ($metadata as $label => $value)
        <div class="col-sm-6 col-xl-4">
          <div class="ip-head-review-meta">
            <span>{{ $label }}</span>
            <strong>{{ filled($value) ? $value : 'Not provided' }}</strong>
          </div>
        </div>
        @endforeach
        <div class="col-sm-6 col-xl-4">
          <div class="ip-head-review-meta"><span>Estimated development cost</span><strong>₱{{ number_format((float) $disclosure->total_development_cost, 2) }}</strong></div>
        </div>
        @if ($disclosure->file_drive_link)
        <div class="col-12">
          <div class="ip-head-review-meta">
            <span>External file / Drive link</span>
            <a href="{{ $disclosure->file_drive_link }}" target="_blank" rel="noopener noreferrer" class="text-break">{{ $disclosure->file_drive_link }}</a>
          </div>
        </div>
        @endif
      </div>
    </div>
  </article>

  <article class="card mb-4 ip-head-review-section" id="review-technologies" data-review-section="technologies">
    <header class="card-header">
      <div class="d-flex align-items-center gap-3">
        <span class="avatar avatar-sm"><span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-lightbulb-line" aria-hidden="true"></i></span></span>
        <div>
          <h4 class="card-title mb-0 fw-semibold">Technical / creative descriptions</h4>
        </div>
      </div>
    </header>
    <div class="card-body">
      @if ($disclosure->technologies->isNotEmpty())
      <div class="accordion ip-head-technology-accordion" id="ip-head-technologies-{{ $disclosure->id }}">
        @foreach ($disclosure->technologies as $technology)
        @php
        $technologyFiles = $technology->files->keyBy('narrative_key');
        $technologyAccordionId = 'ip-head-technology-' . $technology->id;
        @endphp
        <div class="accordion-item mb-3 border rounded overflow-hidden">
          <h5 class="accordion-header" id="{{ $technologyAccordionId }}-heading">
            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $technologyAccordionId }}-body" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="{{ $technologyAccordionId }}-body">
              <span class="badge bg-label-primary me-3">Technology {{ $loop->iteration }}</span>{{ $technology->technology_title }}
            </button>
          </h5>
          <div id="{{ $technologyAccordionId }}-body" class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}" aria-labelledby="{{ $technologyAccordionId }}-heading" data-bs-parent="#ip-head-technologies-{{ $disclosure->id }}">
            <div class="accordion-body">
              <div class="row g-3">
                @foreach ([
                  'abstract_summary' => 'Abstract',
                  'invention_summary' => 'Invention Summary',
                  'detailed_description' => 'Description',
                  'technical_field' => 'Field',
                  'background_summary' => 'Background',
                  'examples_results' => 'Results',
                ] as $field => $label)
                @php
                $narrativeFile = $technologyFiles->get($field);
                $narrativeText = $technology->{$field};
                @endphp
                <div class="col-12">
                  <section class="ip-head-review-narrative">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                      <h6 class="mb-0 fw-semibold">{{ $label }}</h6>
                      @if ($narrativeFile)
                      <a class="btn btn-sm btn-outline-primary" href="{{ route('ip-head-dashboard.technology-files.preview', [$disclosure, $narrativeFile]) }}" target="_blank" rel="noopener noreferrer">
                        <i class="icon-base ri ri-file-pdf-2-line me-1" aria-hidden="true"></i>View PDF
                      </a>
                      @endif
                    </div>
                    <p class="mb-0 submission-narrative__text">{{ filled($narrativeText) ? $narrativeText : ($narrativeFile ? 'Narrative provided as an uploaded PDF.' : 'No narrative provided.') }}</p>
                    @if ($narrativeFile)
                    <span class="small text-body-secondary d-block mt-2 text-break">{{ $narrativeFile->original_name }}</span>
                    @endif
                  </section>
                </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
        @endforeach
      </div>
      @else
      <div class="row g-3">
        @foreach ($legacyNarratives as $label => $text)
        @if (filled($text))
        <div class="col-12">
          <section class="ip-head-review-narrative">
            <h6 class="mb-2 fw-semibold">{{ $label }}</h6>
            <p class="mb-0 submission-narrative__text">{{ $text }}</p>
          </section>
        </div>
        @endif
        @endforeach
        @if (collect($legacyNarratives)->filter()->isEmpty())
        <div class="col-12"><div class="alert alert-light mb-0">No technical narratives were provided.</div></div>
        @endif
      </div>
      @endif
    </div>
  </article>

  <article class="card mb-4 ip-head-review-section" id="review-inventors" data-review-section="inventors">
    <header class="card-header">
      <div class="d-flex align-items-center gap-3">
        <span class="avatar avatar-sm"><span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-team-line" aria-hidden="true"></i></span></span>
        <div>
          <h4 class="card-title mb-0 fw-semibold">Creators and contributions</h4>
        </div>
      </div>
    </header>
    <div class="card-body">
      <div class="row g-3">
        @foreach ($disclosure->inventors as $inventor)
        @php
        $creatorName = $inventor->full_name ?: trim(collect([$inventor->first_name, $inventor->middle_initial, $inventor->last_name, $inventor->suffix])->filter()->implode(' '));
        $creatorDetailsId = 'ip-head-creator-details-' . $inventor->id;
        $contributionNatures = collect($inventor->contribution_nature ?? [])
          ->map(fn ($nature) => str_replace('_', ' ', ucfirst($nature)));
        @endphp
        <div class="col-12 col-xl-6">
          <article class="ip-head-creator-card">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
              <div class="d-flex align-items-start gap-3 min-w-0">
                <span class="avatar avatar-sm flex-shrink-0"><span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-user-3-line" aria-hidden="true"></i></span></span>
                <div class="min-w-0">
                  <h5 class="mb-1 fw-semibold text-break">{{ $creatorName }}</h5>
                  <p class="mb-0 small text-body-secondary">{{ $inventor->position_affiliation ?: str_replace('_', ' ', ucfirst($inventor->affiliation)) }}</p>
                </div>
              </div>
              @if ($inventor->is_primary)
              <span class="badge bg-label-success">Lead creator</span>
              @endif
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
              <span class="small text-body-secondary">Contribution share</span>
              <span class="badge bg-label-primary">{{ $inventor->contribution_percent !== null ? number_format((float) $inventor->contribution_percent, 2) . '%' : 'Not recorded' }}</span>
            </div>
            <button class="btn btn-sm btn-outline-primary mt-3" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $creatorDetailsId }}" aria-expanded="false" aria-controls="{{ $creatorDetailsId }}">
              <i class="icon-base ri ri-arrow-down-s-line me-1" aria-hidden="true"></i>View contribution details
            </button>
            <div class="collapse mt-3" id="{{ $creatorDetailsId }}">
              <div class="ip-head-creator-details">
                <div class="ip-head-review-meta"><span>Email</span><strong>{{ $inventor->email ?: 'Not provided' }}</strong></div>
                <div class="ip-head-review-meta"><span>Citizenship</span><strong>{{ $inventor->country_of_citizenship ?: 'Not provided' }}</strong></div>
                <div class="ip-head-review-meta"><span>Nature of contribution</span><strong>{{ $contributionNatures->isNotEmpty() ? $contributionNatures->implode(', ') : 'Not provided' }}@if ($inventor->contribution_other) — {{ $inventor->contribution_other }}@endif</strong></div>
                <div class="ip-head-review-meta"><span>Specific technical contribution</span><strong class="ip-head-review-long-value">{{ $inventor->technical_contribution ?: 'Not provided' }}</strong></div>
                <div class="ip-head-review-meta"><span>Typed signature</span><strong>{{ $inventor->signature_name ?: 'Not provided' }}</strong></div>
                <div class="ip-head-review-meta"><span>Declaration</span><strong>{{ $inventor->declaration_confirmed ? 'Confirmed' : 'Not confirmed' }}@if ($inventor->declaration_date) · {{ $inventor->declaration_date->format('M j, Y') }}@endif</strong></div>
              </div>
            </div>
          </article>
        </div>
        @endforeach
      </div>
    </div>
  </article>

  <article class="card mb-4 ip-head-review-section" id="review-costing" data-review-section="costing">
    <header class="card-header">
      <div class="d-flex align-items-center gap-3">
        <span class="avatar avatar-sm"><span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-money-dollar-circle-line" aria-hidden="true"></i></span></span>
        <div>
          <h4 class="card-title mb-0 fw-semibold">Costing of materials and resources</h4>
        </div>
      </div>
    </header>
    <div class="card-body">
      @if ($disclosure->costItems->isNotEmpty())
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0 ip-head-costing-table">
          <thead>
            <tr><th>Item / material</th><th>Description / specification</th><th>Quantity</th><th>Unit cost</th><th>Total cost</th><th>Purpose / remarks</th></tr>
          </thead>
          <tbody>
            @foreach ($disclosure->costItems as $costItem)
            <tr>
              <td>{{ $costItem->item }}</td>
              <td>{{ $costItem->description }}</td>
              <td>{{ $costItem->quantity }}</td>
              <td>₱{{ number_format((float) $costItem->unit_cost, 2) }}</td>
              <td>₱{{ number_format((float) $costItem->total_cost, 2) }}</td>
              <td>{{ $costItem->purpose ?: '—' }}</td>
            </tr>
            @endforeach
          </tbody>
          <tfoot><tr><th colspan="4" class="text-end">Total estimated development cost</th><th>₱{{ number_format((float) $disclosure->total_development_cost, 2) }}</th><td></td></tr></tfoot>
        </table>
      </div>
      @else
      <div class="ip-head-review-empty"><i class="icon-base ri ri-inbox-2-line" aria-hidden="true"></i><span>No individual material or resource costs were listed.</span></div>
      @endif
    </div>
  </article>

  <article class="card mb-4 ip-head-review-section" id="review-files" data-review-section="files">
    <header class="card-header">
      <div class="d-flex align-items-center gap-3">
        <span class="avatar avatar-sm"><span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-attachment-2" aria-hidden="true"></i></span></span>
        <div>
          <h4 class="card-title mb-0 fw-semibold">Supporting files</h4>
        </div>
      </div>
    </header>
    <div class="card-body">
      <form class="ip-head-attachment-review-form" method="POST" action="{{ route('ip-head-dashboard.disclosures.attachments.review', $disclosure) }}" data-confirm-title="Save file reviews?">
        @csrf
        <input type="hidden" name="queue" value="{{ $queue }}">
        <input type="hidden" name="page" value="{{ $page }}">
        <div class="row g-3">
          @forelse ($disclosure->attachments as $attachment)
          @php
          $extension = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));
          $isImage = in_array($extension, ['jpg', 'jpeg', 'png'], true);
          $isPdf = $extension === 'pdf';
          $previewUrl = route('ip-head-dashboard.attachments.preview', [$disclosure, $attachment]);
          $previewModalId = 'attachment-preview-' . $attachment->id;
          @endphp
          <div class="col-md-6 col-xxl-4">
            <div class="ip-head-file-card {{ $attachment->is_latest_submission && $attachment->version > 1 ? 'ip-head-file-card--revised' : '' }}">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <span class="fw-semibold ip-head-file-card__type">{{ ucfirst(str_replace('_', ' ', $attachment->type)) }}</span>
                <div class="d-flex flex-wrap gap-2 ip-head-file-card__actions" data-attachment-review-state>
                  @if ($attachment->is_latest_submission)
                  <span class="badge bg-label-success align-self-center" data-latest-submission-badge>Latest</span>
                  @endif
                  @if ($attachment->needs_revision)
                  <span class="badge bg-label-warning align-self-center">Revision requested</span>
                  @endif
                </div>
              </div>
              <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                <span class="small ip-head-file-card__name" title="{{ $attachment->original_name }}">{{ $attachment->original_name }}</span>
                <button class="btn btn-icon btn-text-primary flex-shrink-0" type="button" data-bs-toggle="modal" data-bs-target="#{{ $previewModalId }}" aria-label="Preview {{ $attachment->original_name }}" title="Preview file">
                  <i class="icon-base ri ri-eye-line" aria-hidden="true"></i>
                </button>
              </div>
              <span class="small text-body-secondary ip-head-file-card__meta mb-3">{{ ($attachment->submitted_at ?? $attachment->created_at)->format('M j, Y') }} &middot; {{ number_format($attachment->size_bytes / 1048576, 2) }} MB</span>
              <div class="form-check mb-2">
                <input class="form-check-input ip-head-file-revision-toggle" id="attachment-revision-{{ $attachment->id }}" name="attachments[{{ $attachment->id }}][needs_revision]" type="checkbox" value="1" data-attachment-id="{{ $attachment->id }}" @checked($attachment->needs_revision) @disabled($attachment->needs_revision)>
                <label class="form-check-label" for="attachment-revision-{{ $attachment->id }}">Needs revision</label>
              </div>
              <div class="ip-head-file-revision-notes {{ $attachment->needs_revision ? '' : 'd-none' }}">
                <label class="form-label small" for="attachment-notes-{{ $attachment->id }}">What needs to change?</label>
                <textarea class="form-control form-control-sm mb-3" id="attachment-notes-{{ $attachment->id }}" name="attachments[{{ $attachment->id }}][revision_notes]" rows="2" maxlength="2000" @required($attachment->needs_revision) @disabled($attachment->needs_revision)>{{ $attachment->revision_notes }}</textarea>
              </div>
            </div>
          </div>
          <div class="modal fade" id="{{ $previewModalId }}" tabindex="-1" aria-labelledby="{{ $previewModalId }}-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered idf-modal-dialog" role="document">
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
                  <div class="alert alert-light mb-0" role="status">This file type cannot be previewed here.</div>
                  @endif
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button></div>
              </div>
            </div>
          </div>
          @empty
          <div class="col-12"><div class="ip-head-review-empty"><i class="icon-base ri ri-inbox-2-line" aria-hidden="true"></i><span>No supporting files were attached.</span></div></div>
          @endforelse
        </div>
        <div class="d-flex justify-content-end mt-4">
          <button class="btn btn-primary ip-head-file-card__save" type="submit" disabled title="Flag at least one file as needing revision to save file reviews.">
            <i class="icon-base ri ri-save-line me-1" aria-hidden="true"></i>Save file review
          </button>
        </div>
      </form>
    </div>
  </article>

  <article class="card mb-6 ip-head-review-section" id="review-decision" data-review-section="decision">
    <header class="card-header">
      <div class="d-flex align-items-center gap-3">
        <span class="avatar avatar-sm"><span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-checkbox-circle-line" aria-hidden="true"></i></span></span>
        <div>
          <h4 class="card-title mb-0 fw-semibold">Review decision</h4>
        </div>
      </div>
    </header>
    <div class="card-body">
      <form class="ip-head-review-form" method="POST" action="{{ route('ip-head-dashboard.review', $disclosure) }}" data-has-pending-file-revisions="{{ $disclosure->attachments->contains('needs_revision', true) ? 'true' : 'false' }}">
        @csrf
        <input type="hidden" name="queue" value="{{ $queue }}">
        <input type="hidden" name="page" value="{{ $page }}">
        <div class="row g-4 align-items-end">
          <div class="col-md-4">
            <label class="form-label" for="review-status-{{ $disclosure->id }}">Decision</label>
            <select class="form-select ip-head-review-status" id="review-status-{{ $disclosure->id }}" name="status" required>
              <option value="">Choose a decision</option>
              <option value="under_review" @selected($disclosure->status === 'under_review')>Under review</option>
              <option value="returned" @selected($disclosure->status === 'returned')>Request revision</option>
              <option value="endorsed" @selected($disclosure->status === 'endorsed')>Endorse</option>
            </select>
          </div>
          <div class="col-md-8 ip-head-review-notes-wrap {{ $disclosure->status === 'returned' ? '' : 'd-none' }}">
            <label class="form-label" for="review-notes-{{ $disclosure->id }}">Revision notes</label>
            <textarea class="form-control ip-head-review-notes" id="review-notes-{{ $disclosure->id }}" name="review_notes" rows="3" maxlength="3000" @required($disclosure->status === 'returned')>{{ $disclosure->review_notes }}</textarea>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button class="btn btn-primary" type="submit"><i class="icon-base ri ri-check-line me-1" aria-hidden="true"></i>Save review</button>
          </div>
        </div>
      </form>
    </div>
  </article>
</section>
@endsection

@section('page-script')
<script src="{{ asset('ip-head/js/ip-head-dashboard.js') }}" defer></script>
@endsection
