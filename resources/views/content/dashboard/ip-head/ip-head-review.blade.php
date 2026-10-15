@extends('layouts/contentNavbarLayout')
@section('title', 'Review Submission')

@section('page-style')
<link rel="stylesheet" href="{{ asset('user/css/innovation-disclosure.css') }}?v={{ filemtime(public_path('user/css/innovation-disclosure.css')) }}">
@endsection

@section('content')
@php
$statusColor = match ($disclosure->status) {
  'endorsed', 'filed' => 'success',
  'submitted', 'under_review', 'returned', 'revised_submission' => 'warning',
  default => 'primary',
};
$statusLabel = match ($disclosure->status) {
  'submitted', 'under_review', 'revised_submission', 'returned' => 'For Review',
  'endorsed' => 'Endorsed',
  'filed' => 'Filed',
  default => ucfirst($disclosure->status),
};
$latestAttachments = $reviewableAttachments->where('is_latest_submission', true);
$technologyFileCount = $disclosure->technologies->sum(fn ($technology) => $technology->files->count());
$documentCount = $technologyFileCount + $reviewableAttachments->count();
$approvedFileCount = $disclosure->technologies->sum(fn ($technology) => $technology->files->where('review_status', 'accepted')->count())
  + $latestAttachments->where('review_status', 'accepted')->count();
$rejectedFileCount = $disclosure->technologies->sum(fn ($technology) => $technology->files->where('review_status', 'revision_requested')->count())
  + $reviewableAttachments->filter(fn ($attachment) => $attachment->needs_revision || $attachment->review_status === 'revision_requested')->count();
$pendingFileCount = max(0, $documentCount - $approvedFileCount - $rejectedFileCount);
$allFilesApproved = $documentCount > 0 && $approvedFileCount === $documentCount;
$primaryInventor = $disclosure->inventors->firstWhere('is_primary', true);
$leadCreatorName = $primaryInventor
  ? ($primaryInventor->full_name ?: trim(collect([
    $primaryInventor->first_name,
    $primaryInventor->middle_initial,
    $primaryInventor->last_name,
    $primaryInventor->suffix,
  ])->filter()->implode(' ')))
  : $disclosure->lead_creator_name;
$metadata = [
  'Lead creator' => $leadCreatorName,
  'Technology category' => $disclosure->technology_type_label,
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

<section class="page-content submission-review-page ip-head-review-page" data-file-review-saved="{{ session('file_review_saved') ? 'true' : 'false' }}">
  <header class="card mb-4 submission-review-hero">
    <div class="card-body">
      <div class="submission-review-hero__layout">
        <div class="d-flex align-items-start min-w-0">
          <div class="min-w-0">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
              <span class="badge bg-label-{{ $statusColor }}" data-disclosure-status="{{ $disclosure->id }}" data-status-color="{{ $statusColor }}" data-ip-head-review-status>{{ $statusLabel }}</span>
              <span class="small text-body-secondary">Submitted {{ $disclosure->created_at->diffForHumans() }}</span>
            </div>
            <h3 class="mb-2 fw-semibold submission-review-title" id="submission-review-title">{{ $disclosure->technology_title }}</h3>
            <p class="mb-0 text-body-secondary">{{ $disclosure->user->name }} <span class="mx-1">&middot;</span> {{ $disclosure->user->email }}</p>
          </div>
        </div>
        <div class="submission-review-hero__timeline">
          <div class="submission-review-hero__actions">
            <a class="btn btn-sm" href="{{ route('ip-head-dashboard.submissions', [$pageParameter => $page]) }}">
              <i class="icon-base ri ri-arrow-left-line me-1" aria-hidden="true"></i>Back to submissions
            </a>
            @if ($disclosure->status === 'endorsed')
            <a class="btn btn-sm ms-2" href="{{ route('ip-head-dashboard.disclosures.xml', $disclosure) }}" data-save-as-xml>
              <i class="icon-base ri ri-file-download-line me-1" aria-hidden="true"></i>Save as XML
            </a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </header>

  <section class="ip-head-review-stats" aria-label="Submission summary">
    <article class="ip-head-review-stat">
      <span class="ip-head-review-stat__icon ip-head-review-stat__icon--teal"><i class="icon-base ri ri-money-dollar-circle-line" aria-hidden="true"></i></span>
      <div><span class="ip-head-review-stat__label">Est. dev't cost</span><strong>₱{{ number_format((float) $disclosure->total_development_cost, 2) }}</strong></div>
    </article>
    <article class="ip-head-review-stat">
      <span class="ip-head-review-stat__icon ip-head-review-stat__icon--blue"><i class="icon-base ri ri-lightbulb-line" aria-hidden="true"></i></span>
      <div><span class="ip-head-review-stat__label">Technologies</span><strong>{{ $disclosure->technologies->count() }}</strong></div>
    </article>
    <article class="ip-head-review-stat">
      <span class="ip-head-review-stat__icon ip-head-review-stat__icon--gray"><i class="icon-base ri ri-file-list-3-line" aria-hidden="true"></i></span>
      <div><span class="ip-head-review-stat__label">Documents</span><strong>{{ $documentCount }}</strong></div>
    </article>
    <article class="ip-head-review-stat ip-head-review-stat--pending">
      <span class="ip-head-review-stat__icon ip-head-review-stat__icon--amber"><i class="icon-base ri ri-hourglass-line" aria-hidden="true"></i></span>
      <div><span class="ip-head-review-stat__label">Pending review</span><strong>{{ $pendingFileCount }}</strong></div>
    </article>
  </section>

  <section class="submission-review-overview submission-review-section mb-4" id="review-overview" data-review-section="details" aria-label="Submission overview">
    @if (filled($disclosure->review_notes))
    <div class="alert alert-warning mb-4" data-modal-review-note="{{ $disclosure->id }}">
      <strong>Revision requested:</strong> <span>{{ $disclosure->review_notes }}</span>
    </div>
    @endif
    <div class="card submission-review-overview-card">
      <div class="card-body p-0">
        <div class="row g-0 submission-review-groups">
          <div class="col-12 col-xl-4">
            <section class="submission-review-group h-100" aria-labelledby="ip-head-disclosure-group">
              <h5 class="submission-review-group__title" id="ip-head-disclosure-group">Disclosure and contact</h5>
              <div class="submission-review-group__fields">
                @foreach ([
                  'Lead creator' => $leadCreatorName ?: 'Not provided',
                  'Email address' => $disclosure->email ?: 'Not provided',
                  'Contact number' => $disclosure->mobile_no ?: 'Not provided',
                  'Technology category' => $disclosure->technology_type_label,
                  'Relationship to SLSU' => str_replace('_', ' ', ucfirst($disclosure->university_relationship)),
                ] as $label => $value)
                <div class="submission-review-meta"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
                @endforeach
              </div>
            </section>
          </div>
          <div class="col-12 col-xl-4">
            <section class="submission-review-group h-100" aria-labelledby="ip-head-research-group">
              <h5 class="submission-review-group__title" id="ip-head-research-group">Research and ownership</h5>
              <div class="submission-review-group__fields">
                @foreach ([
                  'Research title' => $disclosure->research_title ?: 'Not provided',
                  'Funding source' => $disclosure->funding_source ? str_replace('_', ' ', ucfirst($disclosure->funding_source)) : 'Not recorded',
                  'Ownership declaration' => $disclosure->ownership_declaration ? str_replace('_', ' ', ucfirst($disclosure->ownership_declaration)) : 'Not recorded',
                  'Substantial SLSU support' => $disclosure->has_substantial_support === null ? 'Not recorded' : ($disclosure->has_substantial_support ? 'Yes' : 'No'),
                  'Ownership consent' => $disclosure->ownership_consent ? 'Confirmed' : 'Not confirmed',
                ] as $label => $value)
                <div class="submission-review-meta"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
                @endforeach
              </div>
            </section>
          </div>
          <div class="col-12 col-xl-4">
            <section class="submission-review-group submission-review-important-dates h-100" aria-labelledby="ip-head-dates-group">
              <h5 class="submission-review-group__title" id="ip-head-dates-group">Important dates</h5>
              <div class="submission-review-group__fields">
                <div class="submission-review-meta"><span>Date of creation</span><strong>{{ $disclosure->date_of_creation?->format('M j, Y') ?: 'Not provided' }}</strong></div>
                <div class="submission-review-meta"><span>Research approval date</span><strong>{{ $disclosure->research_approval_date?->format('M j, Y') ?: 'Not provided' }}</strong></div>
                <div class="submission-review-meta"><span>Disclosure date</span><strong>{{ $disclosure->disclosure_date?->format('M j, Y') ?: 'Not provided' }}</strong></div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </div>
  </section>

  <article class="card mb-4 ip-head-review-section" id="review-inventors" data-review-section="inventors">
    @php
    $totalCreatorAllocation = $disclosure->inventors->sum(fn ($inventor) => (float) $inventor->contribution_percent);
    @endphp
    <header class="card-header">
      <div class="d-flex align-items-center gap-3">
        <h4 class="card-title mb-0 fw-semibold">Creators and contributions</h4>
        <span class="badge rounded-pill bg-label-{{ abs($totalCreatorAllocation - 100) < 0.01 ? 'success' : 'warning' }} ms-auto">{{ number_format($totalCreatorAllocation, 2) }}% allocated</span>
      </div>
    </header>
    <div class="card-body">
      @if ($disclosure->inventors->isNotEmpty())
      <div class="creator-allocation mb-3" role="progressbar" aria-label="Creator contribution allocation" aria-valuenow="{{ min(100, $totalCreatorAllocation) }}" aria-valuemin="0" aria-valuemax="100">
        <div class="creator-allocation__fill" style="width: {{ min(100, $totalCreatorAllocation) }}%">
          @foreach ($disclosure->inventors as $inventor)
          @php $creatorShare = (float) $inventor->contribution_percent; @endphp
          @if ($creatorShare > 0 && $totalCreatorAllocation > 0)
          <span class="creator-allocation__segment" style="width: {{ $creatorShare / $totalCreatorAllocation * 100 }}%" title="{{ $inventor->full_name ?: $inventor->first_name }}: {{ number_format($creatorShare, 2) }}%"></span>
          @endif
          @endforeach
        </div>
      </div>
      <div class="creator-allocation__legend">
        @foreach ($disclosure->inventors as $inventor)
        @php
        $creatorName = $inventor->full_name ?: trim(collect([$inventor->first_name, $inventor->middle_initial, $inventor->last_name, $inventor->suffix])->filter()->implode(' '));
        @endphp
        <span><i aria-hidden="true"></i>{{ $creatorName }} {{ number_format((float) $inventor->contribution_percent, 2) }}%</span>
        @endforeach
      </div>
      <div class="ip-head-creator-list">
        @foreach ($disclosure->inventors as $inventor)
        @php
        $creatorName = $inventor->full_name ?: trim(collect([$inventor->first_name, $inventor->middle_initial, $inventor->last_name, $inventor->suffix])->filter()->implode(' '));
        $creatorDetailsId = 'ip-head-creator-details-' . $inventor->id;
        $contributionNatures = collect($inventor->contribution_nature ?? [])
          ->map(fn ($nature) => str_replace('_', ' ', ucfirst($nature)));
        @endphp
        <article class="ip-head-creator-row">
        <div class="ip-head-creator-row__main">
          <div class="min-w-0">
              <h5 class="mb-1 fw-semibold text-break">{{ $creatorName }}</h5>
              <p class="mb-0 small text-body-secondary">{{ $inventor->position_affiliation ?: str_replace('_', ' ', ucfirst($inventor->affiliation)) }}</p>
            </div>
            <div class="ip-head-creator-row__badges">
              @if ($inventor->is_primary)<span class="badge rounded-pill bg-label-success">★ Lead creator</span>@else<span class="badge rounded-pill bg-label-primary">Co-creator</span>@endif
              <span class="badge rounded-pill bg-label-primary">{{ number_format((float) $inventor->contribution_percent, 2) }}%</span>
            </div>
            <button class="btn btn-sm btn-icon text-body-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $creatorDetailsId }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="{{ $creatorDetailsId }}" aria-label="Toggle contribution details for {{ $creatorName }}">
              <i class="icon-base ri ri-arrow-down-s-line" aria-hidden="true"></i>
            </button>
          </div>
          <div class="collapse {{ $loop->first ? 'show' : '' }}" id="{{ $creatorDetailsId }}">
            <div class="ip-head-creator-row__details">
              @if ($inventor->email)
              <span><i class="icon-base ri ri-mail-line" aria-hidden="true"></i>{{ $inventor->email }}</span>
              @endif
              @if ($inventor->technical_contribution)
              <span><i class="icon-base ri ri-lightbulb-line" aria-hidden="true"></i>Contribution: {{ $inventor->technical_contribution }}</span>
              @elseif ($contributionNatures->isNotEmpty())
              <span><i class="icon-base ri ri-lightbulb-line" aria-hidden="true"></i>Contribution: {{ $contributionNatures->implode(', ') }}@if ($inventor->contribution_other) — {{ $inventor->contribution_other }}@endif</span>
              @endif
              <span><i class="icon-base ri ri-global-line" aria-hidden="true"></i>{{ $inventor->country_of_citizenship ?: 'Citizenship not provided' }}</span>
              <span><i class="icon-base ri ri-check-double-line" aria-hidden="true"></i>{{ $inventor->declaration_confirmed ? 'Declaration confirmed' : 'Declaration not confirmed' }}@if ($inventor->declaration_date) · {{ $inventor->declaration_date->format('M j, Y') }}@endif</span>
            </div>
          </div>
        </article>
        @endforeach
      </div>
      @else
      <div class="ip-head-review-empty"><i class="icon-base ri ri-user-search-line" aria-hidden="true"></i><span>No creators were listed for this submission.</span></div>
      @endif
    </div>
  </article>

  <article class="card mb-4 ip-head-review-section" id="review-costing" data-review-section="costing">
    <header class="card-header">
      <div class="d-flex align-items-center gap-3">
        <h4 class="card-title mb-0 fw-semibold">Costing of materials and resources</h4>
        <span class="badge rounded-pill bg-label-success ms-auto">Total: ₱{{ number_format((float) $disclosure->total_development_cost, 2) }}</span>
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

  <article class="card mb-4 ip-head-review-section" id="review-technologies" data-review-section="technologies">
    <header class="card-header">
      <div class="d-flex align-items-center gap-3">
        <h4 class="card-title mb-0 fw-semibold">Technologies in this submission</h4>
      </div>
    </header>
    <div class="card-body" id="review-documents">
      @if ($disclosure->technologies->isNotEmpty())
      <div class="technology-review-list" data-review-table data-page-size="4" data-disable-inactive>
        @foreach ($disclosure->technologies as $technology)
        @php
        $technologyAttachments = $loop->first ? $reviewableAttachments : collect();
        $latestTechnologyAttachments = $technologyAttachments->where('is_latest_submission', true);
        $approvedTechnologyFileCount = $technology->files->where('review_status', 'accepted')->count();
        $revisionTechnologyFileCount = $technology->files->where('review_status', 'revision_requested')->count()
          + $technologyAttachments->filter(fn ($attachment) => $attachment->needs_revision || $attachment->review_status === 'revision_requested')->count();
        $pendingTechnologyFileCount = $technology->files->where('review_status', 'review_needed')->count()
          + $latestTechnologyAttachments->where('review_status', 'review_needed')->count();
        $approvedTechnologyFileCount += $latestTechnologyAttachments->where('review_status', 'accepted')->count();
        $technologyFileCount = $approvedTechnologyFileCount + $revisionTechnologyFileCount + $pendingTechnologyFileCount;
        $technologyReviewedFileCount = $approvedTechnologyFileCount + $revisionTechnologyFileCount;
        $revisedTechnologyFileCount = $technology->files
          ->filter(fn ($file) => $file->version > 1 && $file->review_status === 'review_needed')
          ->count();
        $technologyFilesPanelId = 'technology-files-' . $technology->id;
        @endphp
        <section class="technology-review-item" data-review-table-page data-technology-card>
          <div class="technology-review-card">
            <h5 class="technology-review-card__title">{{ $technology->technology_title }}</h5>
            <div class="technology-review-card__count">{{ $technologyReviewedFileCount }}/{{ $technologyFileCount }}</div>
            <div class="progress technology-review-card__progress" role="progressbar" aria-label="Files reviewed for {{ $technology->technology_title }}" aria-valuenow="{{ $technologyReviewedFileCount }}" aria-valuemin="0" aria-valuemax="{{ $technologyFileCount }}">
              <div class="progress-bar" style="width: {{ $technologyFileCount ? round($technologyReviewedFileCount / $technologyFileCount * 100) : 0 }}%"></div>
            </div>
            <div class="technology-review-card__counts" aria-label="File review summary">
              <span class="technology-review-count technology-review-count--pending"><i aria-hidden="true"></i>{{ $pendingTechnologyFileCount }} review</span>
              <span class="technology-review-count technology-review-count--approved"><i aria-hidden="true"></i>{{ $approvedTechnologyFileCount }} approved</span>
              <span class="technology-review-count technology-review-count--revision"><i aria-hidden="true"></i>{{ $revisionTechnologyFileCount }} rejected</span>
              @if ($revisedTechnologyFileCount > 0)
              <span class="technology-review-count technology-review-count--updated"><i class="icon-base ri ri-refresh-line" aria-hidden="true"></i>{{ $revisedTechnologyFileCount }} updated file{{ $revisedTechnologyFileCount === 1 ? '' : 's' }}</span>
              @endif
            </div>
            <button class="btn btn-primary btn-sm" type="button" data-open-technology-files="{{ $technologyFilesPanelId }}" aria-expanded="false">
              <i class="icon-base ri ri-folder-open-line me-1" aria-hidden="true"></i>Open files
            </button>
            <p class="small text-body-secondary mb-0 mt-2">Technology {{ $loop->iteration }} of {{ $loop->count }} · {{ $disclosure->technology_type_label }}</p>
          </div>

          <form class="ip-head-attachment-review-form technology-review-files-panel" id="{{ $technologyFilesPanelId }}" method="POST" action="{{ route('ip-head-dashboard.disclosures.attachments.review', $disclosure) }}" data-confirm-title="Save file reviews?" hidden>
            @csrf
            <input type="hidden" name="queue" value="{{ $queue }}">
            <input type="hidden" name="page" value="{{ $page }}">
            <header class="technology-review-files-panel__header">
              <button class="btn btn-sm btn-outline-light technology-review-files-panel__back" type="button" data-close-technology-files="{{ $technologyFilesPanelId }}">
                <i class="icon-base ri ri-arrow-left-line me-1" aria-hidden="true"></i>Back to technologies
              </button>
              <strong>{{ $technology->technology_title }}</strong>
              <span>{{ $technologyReviewedFileCount }}/{{ $technologyFileCount }} reviewed</span>
            </header>
            <div class="table-responsive technology-review-files-wrap">
              <table class="table align-middle review-files-table technology-review-files mb-0">
                <tbody>
                  @php $previousFileGroup = null; @endphp
                  @foreach ($technology->files as $technologyFile)
                  @php
                  $fileGroup = in_array($technologyFile->narrative_key, ['abstract_summary', 'technical_field', 'background_summary', 'invention_summary', 'detailed_description'], true)
                    ? 'Description Documents'
                    : 'Supporting Evidence';
                  $initialRevisionNote = $technologyFile->versions
                    ->firstWhere('version', $technologyFile->version - 1)
                    ?->revision_notes;
                  $fileReviewId = 'technology-file-review-' . $technologyFile->id;
                  $fileNotesId = 'technology-file-notes-' . $technologyFile->id;
                  @endphp
                  @if ($fileGroup !== $previousFileGroup)
                  <tr class="technology-review-files__group"><th colspan="3">{{ $fileGroup }} · {{ $technology->files->filter(fn ($file) => (in_array($file->narrative_key, ['abstract_summary', 'technical_field', 'background_summary', 'invention_summary', 'detailed_description'], true) ? 'Description Documents' : 'Supporting Evidence') === $fileGroup)->count() }}</th></tr>
                  @php $previousFileGroup = $fileGroup; @endphp
                  @endif
                  <tr class="technology-review-file-row">
                    <td>
                      @include('content.dashboard._review-file-identity', [
                        'fileName' => $technologyFile->original_name,
                        'sizeBytes' => $technologyFile->size_bytes,
                        'fileVersionFlag' => $technologyFile->version > 1 ? 'Revised' : null,
                        'dateLabel' => ($technologyFile->submitted_at ?? $technologyFile->updated_at ?? $technologyFile->created_at)->format('M j, Y'),
                        'wrapperClass' => 'ip-head-file-card ' . ($technologyFile->version > 1 ? 'ip-head-file-card--revised' : ''),
                      ])
                      <div class="ip-head-technology-file-notes d-none">
                        <textarea class="ip-head-technology-file-notes-input" id="{{ $fileNotesId }}" name="technology_files[{{ $technologyFile->id }}][revision_notes]" rows="2" maxlength="2000" @disabled($technologyFile->review_status !== 'revision_requested')>{{ $technologyFile->revision_notes }}</textarea>
                      </div>
                    </td>
                    <td>
                      <label class="visually-hidden" for="{{ $fileReviewId }}">Review status for {{ $technologyFile->original_name }}</label>
                      <span class="badge bg-label-{{ $technologyFile->review_status === 'accepted' ? 'success' : ($technologyFile->review_status === 'revision_requested' ? 'danger' : 'warning') }}" data-technology-review-status-label="{{ $technologyFile->id }}">{{ match ($technologyFile->review_status) { 'accepted' => 'Approved', 'revision_requested' => 'Requested Revision', default => 'Review Needed' } }}</span>
                      <select class="form-select form-select-sm ip-head-technology-file-status d-none" id="{{ $fileReviewId }}" name="technology_files[{{ $technologyFile->id }}][status]" data-initial-status="{{ $technologyFile->review_status }}" data-review-status="{{ $technologyFile->review_status }}" @disabled($technologyFile->review_status === 'accepted')>
                          <option value="review_needed" @selected($technologyFile->review_status === 'review_needed')>Review Needed</option>
                          <option value="revision_requested" @selected($technologyFile->review_status === 'revision_requested')>Requested Revision</option>
                          <option value="accepted" @selected($technologyFile->review_status === 'accepted')>Approved</option>
                      </select>
                      @if ($technologyFile->review_status === 'revision_requested' && filled($technologyFile->revision_notes))
                      <div class="ip-head-file-revision-note ip-head-file-revision-note--inline" role="note">
                        <i class="icon-base ri ri-information-line" aria-hidden="true"></i>
                        <span><strong>Revision requested</strong>{{ $technologyFile->revision_notes }}</span>
                      </div>
                      @endif
                    </td>
                    <td>
                      @php
                      $technologyPreviewUrl = route('ip-head-dashboard.technology-files.preview', [
                        $disclosure,
                        $technologyFile,
                        'v' => $technologyFile->version,
                      ]);
                      @endphp
                      <div class="dropdown ip-head-technology-review-actions">
                        <button class="btn text-body-secondary p-0 ip-head-technology-review-actions__trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $technologyFile->original_name }}">
                          <i class="icon-base ri ri-more-2-fill" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end py-2">
                          <li>
                            <button
                              class="dropdown-item"
                              type="button"
                              data-ip-head-technology-review
                              data-file-name="{{ $technologyFile->original_name }}"
                              data-file-preview-url="{{ $technologyPreviewUrl }}"
                              data-file-size="{{ $technologyFile->size_bytes }}"
                              data-file-version="{{ $technologyFile->version }}"
                              data-file-date="{{ ($technologyFile->submitted_at ?? $technologyFile->updated_at ?? $technologyFile->created_at)->format('M j, Y') }}"
                              data-file-status="{{ $technologyFile->review_status }}"
                              data-file-revision-note="{{ $technologyFile->revision_notes }}"
                              data-file-initial-revision-note="{{ $initialRevisionNote }}"
                              data-file-guidance-url="{{ $technologyFile->revision_guidance_path ? route('ip-head-dashboard.technology-files.preview', [$disclosure, $technologyFile]) . '?guidance=1' : '' }}"
                              data-file-guidance-name="{{ $technologyFile->revision_guidance_name }}"
                              data-technology-title="{{ $technology->technology_title }}"
                              data-technology-category="{{ $disclosure->technology_type_label }}"
                              data-file-category="{{ $technologyFile->narrative_key === 'abstract_summary' ? 'Abstract / Summary' : ucwords(str_replace('_', ' ', $technologyFile->narrative_key)) }}"
                              data-file-status-input-id="{{ $fileReviewId }}"
                              data-file-notes-input-id="{{ $fileNotesId }}"
                              data-file-form-id="{{ $technologyFilesPanelId }}">
                              <i class="icon-base ri ri-eye-line me-2" aria-hidden="true"></i>Review file
                            </button>
                          </li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                  @endforeach
                  @foreach ($technologyAttachments->groupBy(fn ($attachment) => str_contains(strtolower($attachment->type), 'claim') ? 'Claims' : 'Supporting Evidence') as $attachmentGroup => $groupedAttachments)
                  <tr class="technology-review-files__group"><th colspan="4">{{ $attachmentGroup }} · {{ $groupedAttachments->count() }}</th></tr>
                  @foreach ($groupedAttachments as $attachment)
                  @php
                  $extension = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));
                  $isImage = in_array($extension, ['jpg', 'jpeg', 'png'], true);
                  $isPdf = $extension === 'pdf';
                  $previewUrl = route('ip-head-dashboard.attachments.preview', [$disclosure, $attachment]);
                  $previewModalId = 'attachment-preview-' . $technology->id . '-' . $attachment->id;
                  $attachmentStatusId = 'attachment-status-' . $technology->id . '-' . $attachment->id;
                  $notesInputId = 'attachment-notes-' . $technology->id . '-' . $attachment->id;
                  @endphp
                  <tr class="technology-review-attachment-row {{ $attachment->needs_revision ? 'review-files-row--revision' : '' }}">
                    <td>
                      @include('content.dashboard._review-file-identity', [
                        'fileName' => $attachment->original_name,
                        'sizeBytes' => $attachment->size_bytes,
                        'versionLabel' => 'v' . $attachment->version,
                        'dateLabel' => ($attachment->submitted_at ?? $attachment->created_at)->format('M j, Y'),
                        'wrapperClass' => 'ip-head-file-card ' . ($attachment->is_latest_submission && $attachment->version > 1 ? 'ip-head-file-card--revised' : ''),
                      ])
                    </td>
                    <td>
                      @if ($attachment->is_latest_submission && ! $attachment->needs_revision && $attachment->review_status !== 'accepted')
                      <label class="visually-hidden" for="{{ $attachmentStatusId }}">Review status for {{ $attachment->original_name }}</label>
                      <span class="ip-head-file-status-control" data-review-status="{{ $attachment->review_status }}">
                        <i class="icon-base ri ri-checkbox-blank-circle-fill ip-head-file-status-control__dot" aria-hidden="true"></i>
                        <select class="form-select form-select-sm ip-head-attachment-file-status" id="{{ $attachmentStatusId }}" name="attachments[{{ $attachment->id }}][status]" data-initial-status="{{ $attachment->review_status }}">
                          <option value="review_needed" @selected($attachment->review_status === 'review_needed')>Review Needed</option>
                          <option value="revision_requested" @selected($attachment->review_status === 'revision_requested')>Requested Revision</option>
                          <option value="accepted" @selected($attachment->review_status === 'accepted')>Approved</option>
                        </select>
                        <i class="icon-base ri ri-arrow-down-s-line ip-head-file-status-control__arrow" aria-hidden="true"></i>
                      </span>
                      @elseif ($attachment->review_status === 'accepted')
                      <span class="badge rounded-pill bg-label-success">Approved</span>
                      @elseif ($attachment->needs_revision)
                      <span class="badge rounded-pill bg-label-danger">Rejected</span>
                      @else
                      <span class="badge rounded-pill bg-label-secondary">Superseded</span>
                      @endif
                    </td>
                    <td class="technology-review-file-notes-cell">
                      @if ($attachment->needs_revision && filled($attachment->revision_notes))
                      @include('content.dashboard._review-file-revision-note', ['noteText' => $attachment->revision_notes])
                      @endif
                      @if ($attachment->review_status === 'revision_requested' && ! $attachment->needs_revision)
                      @include('content.dashboard._review-file-revision-note', ['noteText' => $attachment->revision_notes])
                      <input type="hidden" name="attachments[{{ $attachment->id }}][revision_notes]" value="{{ $attachment->revision_notes }}">
                      @else
                      <div class="ip-head-attachment-file-notes d-none">
                        <label class="form-label small mb-1" for="{{ $notesInputId }}">What needs to change?</label>
                        <textarea class="form-control form-control-sm ip-head-attachment-file-notes-input" id="{{ $notesInputId }}" name="attachments[{{ $attachment->id }}][revision_notes]" rows="2" maxlength="2000" @disabled(! $attachment->is_latest_submission || $attachment->needs_revision)></textarea>
                      </div>
                      @endif
                    </td>
                    <td class="text-end">
                      @include('content.dashboard._review-file-actions', [
                        'fileName' => $attachment->original_name,
                        'previewModalId' => $previewModalId,
                      ])
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
                    </td>
                  </tr>
                  @endforeach
                  @endforeach
                  @if ($technology->files->isEmpty() && $technologyAttachments->isEmpty())
                  <tr><td colspan="4"><span class="text-body-secondary">No files were attached to this technology.</span></td></tr>
                  @endif
                </tbody>
              </table>
            </div>
            @if ($technology->files->isNotEmpty() || $technologyAttachments->isNotEmpty())
            <div class="d-flex justify-content-end mt-3">
              <button class="btn btn-primary ip-head-file-card__save" type="submit" disabled>
                <i class="icon-base ri ri-save-line me-1" aria-hidden="true"></i>Save file review
              </button>
            </div>
            @endif
          </form>
        </section>
        @endforeach
        <nav class="d-flex justify-content-end mt-3" aria-label="Technology card pagination" data-review-table-pagination></nav>
      </div>
      @else
      <div data-review-table data-page-size="1" data-disable-inactive>
        <section data-review-table-page>
          <form class="ip-head-attachment-review-form" method="POST" action="{{ route('ip-head-dashboard.disclosures.attachments.review', $disclosure) }}" data-confirm-title="Save file reviews?">
            @csrf
            <input type="hidden" name="queue" value="{{ $queue }}">
            <input type="hidden" name="page" value="{{ $page }}">
            <div class="table-responsive">
              <table class="table table-hover align-middle review-files-table technology-review-files mb-0">
                <thead><tr><th scope="col">Files</th><th scope="col">Status</th><th scope="col">Revision notes</th><th scope="col" class="text-end">Action</th></tr></thead>
                <tbody>
                  @foreach ($disclosure->attachments as $attachment)
                  @php
                  $extension = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));
                  $isImage = in_array($extension, ['jpg', 'jpeg', 'png'], true);
                  $isPdf = $extension === 'pdf';
                  $previewUrl = route('ip-head-dashboard.attachments.preview', [$disclosure, $attachment]);
                  $previewModalId = 'attachment-preview-fallback-' . $attachment->id;
                  $attachmentStatusId = 'attachment-status-fallback-' . $attachment->id;
                  $notesInputId = 'attachment-notes-fallback-' . $attachment->id;
                  @endphp
                  <tr>
                    <td>
                      @include('content.dashboard._review-file-identity', [
                        'fileName' => $attachment->original_name,
                        'sizeBytes' => $attachment->size_bytes,
                        'versionLabel' => 'v' . $attachment->version,
                        'dateLabel' => ($attachment->submitted_at ?? $attachment->created_at)->format('M j, Y'),
                        'wrapperClass' => 'ip-head-file-card ' . ($attachment->is_latest_submission && $attachment->version > 1 ? 'ip-head-file-card--revised' : ''),
                      ])
                    </td>
                    <td>
                      @if ($attachment->is_latest_submission && ! $attachment->needs_revision)
                      <label class="visually-hidden" for="{{ $attachmentStatusId }}">Review status for {{ $attachment->original_name }}</label>
                      <span class="ip-head-file-status-control" data-review-status="{{ $attachment->review_status }}">
                        <i class="icon-base ri ri-checkbox-blank-circle-fill ip-head-file-status-control__dot" aria-hidden="true"></i>
                        <select class="form-select form-select-sm ip-head-attachment-file-status" id="{{ $attachmentStatusId }}" name="attachments[{{ $attachment->id }}][status]" data-initial-status="{{ $attachment->review_status }}">
                          <option value="review_needed" @selected($attachment->review_status === 'review_needed')>Review Needed</option>
                          <option value="revision_requested" @selected($attachment->review_status === 'revision_requested')>Requested Revision</option>
                          <option value="accepted" @selected($attachment->review_status === 'accepted')>Approved</option>
                        </select>
                        <i class="icon-base ri ri-arrow-down-s-line ip-head-file-status-control__arrow" aria-hidden="true"></i>
                      </span>
                      @elseif ($attachment->needs_revision)
                      <span class="badge bg-label-danger">Rejected</span>
                      @else
                      <span class="badge bg-label-secondary">Superseded</span>
                      @endif
                    </td>
                    <td class="technology-review-file-notes-cell">
                      @if ($attachment->needs_revision && filled($attachment->revision_notes))
                      @include('content.dashboard._review-file-revision-note', ['noteText' => $attachment->revision_notes])
                      @endif
                      @if ($attachment->review_status === 'revision_requested' && ! $attachment->needs_revision)
                      @include('content.dashboard._review-file-revision-note', ['noteText' => $attachment->revision_notes])
                      <input type="hidden" name="attachments[{{ $attachment->id }}][revision_notes]" value="{{ $attachment->revision_notes }}">
                      @else
                      <div class="ip-head-attachment-file-notes d-none">
                        <label class="form-label small mb-1" for="{{ $notesInputId }}">What needs to change?</label>
                        <textarea class="form-control form-control-sm ip-head-attachment-file-notes-input" id="{{ $notesInputId }}" name="attachments[{{ $attachment->id }}][revision_notes]" rows="2" maxlength="2000" @disabled(! $attachment->is_latest_submission || $attachment->needs_revision)></textarea>
                      </div>
                      @endif
                    </td>
                    <td class="text-center">
                      @include('content.dashboard._review-file-actions', [
                        'fileName' => $attachment->original_name,
                        'previewModalId' => $previewModalId,
                      ])
                      <div class="modal fade" id="{{ $previewModalId }}" tabindex="-1" aria-labelledby="{{ $previewModalId }}-title" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-centered idf-modal-dialog">
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
                    </td>
                  </tr>
                  @endforeach
                  @if ($disclosure->attachments->isEmpty())
                  <tr><td colspan="4"><span class="text-body-secondary">No files were attached to this submission.</span></td></tr>
                  @endif
                </tbody>
              </table>
            </div>
            @if ($disclosure->attachments->isNotEmpty())
            <div class="d-flex justify-content-end mt-3">
              <button class="btn btn-primary ip-head-file-card__save" type="submit" disabled>
                <i class="icon-base ri ri-save-line me-1" aria-hidden="true"></i>Save file review
              </button>
            </div>
            @endif
          </form>
        </section>
        <nav class="d-flex justify-content-end mt-3" aria-label="Technology table pagination" data-review-table-pagination></nav>
      </div>
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

  <article class="card mb-6 ip-head-review-section" id="review-approval" data-review-section="approval">
    <header class="card-header">
      <div class="d-flex align-items-center gap-3">
        <h4 class="card-title mb-0 fw-semibold">Submission approval</h4>
      </div>
    </header>
    <div class="card-body">
      <form class="ip-head-approval-form" method="POST" action="{{ route('ip-head-dashboard.review', $disclosure) }}">
        @csrf
        <input type="hidden" name="queue" value="{{ $queue }}">
        <input type="hidden" name="page" value="{{ $page }}">
        <p class="mb-3 text-body-secondary">
          @if ($disclosure->status === 'endorsed')
          Every submitted file has been approved.
          @elseif ($allFilesApproved)
          Every current submitted file is approved and ready for final approval.
          @else
          Approve each current submitted file before approving this IDF.
          @endif
        </p>
        <div class="d-flex justify-content-end">
          <button class="btn btn-primary" type="submit" @disabled(! $allFilesApproved || $disclosure->status === 'endorsed')>
            <i class="icon-base ri ri-check-line me-1" aria-hidden="true"></i>{{ $disclosure->status === 'endorsed' ? 'Submitted IDF Approved' : 'Approve submitted IDF' }}
          </button>
        </div>
      </form>
    </div>
  </article>
</section>
@endsection

@section('page-script')
<script src="{{ asset('ip-head/js/ip-head-dashboard.js') }}?v={{ filemtime(public_path('ip-head/js/ip-head-dashboard.js')) }}" defer></script>
<script src="{{ asset('user/js/review-table-pagination.js') }}" defer></script>
@endsection
