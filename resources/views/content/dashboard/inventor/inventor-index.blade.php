@extends('layouts/contentNavbarLayout')
@php
$detailView = $detailView ?? false;
@endphp
@section('title', $detailView ? 'Submission Review' : 'My Submission')

@section('page-style')
<link rel="stylesheet" href="{{ asset('user/css/innovation-disclosure.css') }}">
@endsection

@section('content')
<section class="page-content" @unless ($detailView) data-submission-status-url="{{ route('dashboard.submissions.status-updates') }}" @endunless>

  @if ($errors->any())
  @if ($detailView)
  <div class="d-none" data-submission-validation-errors data-messages="{{ json_encode($errors->all()) }}"></div>
  @else
  <div class="alert alert-danger" role="alert">
    <ul class="mb-0 ps-4">
      @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif
  @endif

  @if ($disclosures->count())
  @unless ($detailView)
  <div class="card mb-6">
    <div class="card-header border-0 pb-0">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <h4 class="mb-0">My Submission</h4>
        <a class="btn btn-primary" href="{{ route('dashboard.innovation-disclosure.create') }}"><i class="icon-base ri ri-add-line me-1"></i>New disclosure</a>
      </div>
      <hr class="page-content__divider">
    </div>
    <div class="table-responsive idf-data-table submission-table-inset">
      <table class="table table-hover align-middle mb-0 inventor-submissions-table">
        <colgroup>
          <col style="width: 6%">
          <col style="width: 15%">
          <col style="width: 29%">
          <col style="width: 18%">
          <col style="width: 14%">
          <col style="width: 10%">
          <col style="width: 8%">
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">Action</th>
            <th>Type</th>
            <th>Title</th>
            <th>Department</th>
            <th>Current Status</th>
            <th>Last Updated</th>
            <th>Created At</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($disclosures as $disclosure)
          @php
          $statusColor = match ($disclosure->status) {
          'endorsed', 'filed' => 'success',
          'returned' => 'danger',
          'under_review', 'revised_submission' => 'warning',
          default => 'primary',
          };
          $statusLabel = match ($disclosure->status) {
          'submitted' => 'For Review',
          'under_review' => 'Under review',
          'revised_submission' => 'Revised Submission',
          'returned' => 'Revision requested',
          default => str_replace('_', ' ', ucfirst($disclosure->status)),
          };
          @endphp
          <tr>
            <td class="text-center">
              <div class="dropdown">
                <button class="btn text-body-secondary p-0 submission-action-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $disclosure->technology_title }}">
                  <i class="icon-base ri ri-more-2-fill" aria-hidden="true"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end py-2">
                  <li>
                    <a class="dropdown-item" href="{{ route('dashboard.submissions.show', $disclosure) }}">
                      <i class="icon-base ri ri-eye-line me-2" aria-hidden="true"></i>View
                    </a>
                  </li>
                </ul>
              </div>
            </td>
            <td title="{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}"><span class="submission-table__type">{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}</span></td>
            <td>
              <span class="d-block fw-medium inventor-submissions-table__title" title="{{ $disclosure->technology_title }}">{{ $disclosure->technology_title }}</span>
            </td>
            <td title="{{ $disclosure->college_department }}">{{ $disclosure->college_department }}</td>
            <td>
              <span class="badge bg-label-{{ $statusColor }} text-nowrap" data-disclosure-status="{{ $disclosure->id }}">{{ $statusLabel }}</span>
            </td>
            <td class="submission-table__single-line" data-last-updated="{{ $disclosure->id }}" title="{{ $disclosure->updated_at->format('M j, Y g:i A') }}">{{ $disclosure->updated_at->diffForHumans() }}</td>
            <td class="submission-table__created-at" title="{{ $disclosure->created_at->format('M j, Y g:i A') }}"><span>{{ $disclosure->created_at->format('M j, Y') }}</span><span>{{ $disclosure->created_at->format('g:i A') }}</span></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endunless

  @if ($detailView)
  @foreach ($disclosures as $disclosure)
  @php
  $attachmentsByType = $disclosure->attachments->keyBy('type');
  $attachmentTimelineEvents = $disclosure->attachments
    ->flatMap(fn ($attachment) => $attachment->events->map(fn ($event) => [
      'event' => $event,
      'file_type' => $attachment->type,
    ]))
    ->sortBy(fn ($entry) => $entry['event']->occurred_at)
    ->values();
  $canEditRevisionDetails = $disclosure->status === 'returned';
  $fileSlots = [
  'drawings' => 'Drawings',
  'methodology' => 'Methodology',
  'assistance_form' => 'Assistance form',
  'deed_assignment' => 'Deed of Assignment',
  'sample_photo' => 'Sample photo of the product',
  'additional_signed_sheet' => 'Additional signed creator sheet',
  ];
  @endphp
  <section class="submission-details-page submission-review-page" aria-labelledby="submission-review-title" data-submission-status-url="{{ route('dashboard.submissions.status-updates') }}">
    <header class="card mb-4 submission-review-hero">
      <div class="card-body">
        <div class="d-flex align-items-start gap-3 min-w-0">
          <div class="d-flex align-items-start gap-3 min-w-0">
            <span class="avatar avatar-md flex-shrink-0"><span class="avatar-initial bg-label-secondary text-body-secondary rounded"><i class="icon-base ri ri-file-list-3-line" aria-hidden="true"></i></span></span>
            <div class="min-w-0">
              <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge bg-label-{{ match ($disclosure->status) { 'endorsed', 'filed' => 'success', 'returned' => 'danger', 'under_review', 'revised_submission' => 'warning', default => 'primary' } }}" data-disclosure-status="{{ $disclosure->id }}">{{ match ($disclosure->status) { 'submitted' => 'For Review', 'under_review' => 'Under review', 'revised_submission' => 'Revised Submission', 'returned' => 'Revision requested', default => str_replace('_', ' ', ucfirst($disclosure->status)) } }}</span>
                <span class="small text-body-secondary">Submitted {{ $disclosure->created_at->diffForHumans() }}</span>
              </div>
              <h3 class="mb-2 fw-semibold submission-review-title" id="submission-review-title">{{ $disclosure->research_title }}</h3>
              <p class="mb-0 text-body-secondary"><i class="icon-base ri ri-user-3-line me-1" aria-hidden="true"></i>{{ $disclosure->lead_creator_name }}</p>
            </div>
          </div>
        </div>
      </div>
    </header>

    <section class="submission-review-overview submission-review-section mb-4" id="submission-overview" data-review-section="details" aria-label="Submission overview">
      <div class="alert alert-warning mb-4 {{ $disclosure->review_notes ? '' : 'd-none' }}" data-modal-review-note="{{ $disclosure->id }}">
        <strong>Revision requested:</strong> <span>{{ $disclosure->review_notes }}</span>
      </div>

      @php
      $submissionOverviewIcons = [
        'Technology title' => 'ri-lightbulb-line',
        'Lead creator' => 'ri-user-3-line',
        'Email address' => 'ri-mail-line',
        'Contact number' => 'ri-phone-line',
        'Technology category' => 'ri-price-tag-3-line',
        'Relationship to SLSU' => 'ri-links-line',
        'Other relationship' => 'ri-links-line',
        'Funding source' => 'ri-money-dollar-circle-line',
        'Ownership declaration' => 'ri-shield-check-line',
        'Substantial SLSU support' => 'ri-hand-heart-line',
        'Ownership consent' => 'ri-shield-check-line',
        'College / department' => 'ri-building-4-line',
        'Campus' => 'ri-map-pin-line',
      ];
      @endphp
      <div class="row g-3 submission-review-groups">
        <div class="col-12 col-xl-4">
          <section class="submission-review-group h-100" aria-labelledby="submission-disclosure-group">
            <h5 class="submission-review-group__title" id="submission-disclosure-group">Disclosure and contact</h5>
            <div class="submission-review-group__fields">
              @foreach ([
                'Lead creator' => $disclosure->lead_creator_name,
                'Email address' => $disclosure->email,
                'Contact number' => $disclosure->mobile_no,
                'Technology category' => str_replace('_', ' ', ucfirst($disclosure->technology_type)),
                'Relationship to SLSU' => str_replace('_', ' ', ucfirst($disclosure->university_relationship)),
                'Other relationship' => $disclosure->other_relationship,
              ] as $label => $value)
              @if (filled($value))
              <div class="submission-review-meta"><span><i class="icon-base ri {{ $submissionOverviewIcons[$label] ?? 'ri-information-line' }}" aria-hidden="true"></i><span>{{ $label }}</span></span><strong>{{ $value }}</strong></div>
              @endif
              @endforeach
              @if ($disclosure->file_drive_link)
              <div class="submission-review-meta">
                <span><i class="icon-base ri ri-links-line" aria-hidden="true"></i><span>External file / Drive link</span></span>
                <a href="{{ $disclosure->file_drive_link }}" target="_blank" rel="noopener noreferrer" class="text-break">{{ $disclosure->file_drive_link }}</a>
              </div>
              @endif
            </div>
          </section>
        </div>
        <div class="col-12 col-xl-4">
          <section class="submission-review-group h-100" aria-labelledby="submission-research-group">
            <h5 class="submission-review-group__title" id="submission-research-group">Research and ownership</h5>
            <div class="submission-review-group__fields">
              @foreach ([
                'Funding source' => $disclosure->funding_source,
                'Ownership declaration' => str_replace('_', ' ', ucfirst($disclosure->ownership_declaration)),
                'Substantial SLSU support' => $disclosure->has_substantial_support === null ? 'Not recorded' : ($disclosure->has_substantial_support ? 'Yes' : 'No'),
                'Ownership consent' => $disclosure->ownership_consent ? 'Confirmed' : 'Not recorded',
                'College / department' => $disclosure->college_department ?: 'Not provided',
                'Campus' => $disclosure->campus ?: 'Not provided',
              ] as $label => $value)
              @if (filled($value))
              <div class="submission-review-meta"><span><i class="icon-base ri {{ $submissionOverviewIcons[$label] ?? 'ri-information-line' }}" aria-hidden="true"></i><span>{{ $label }}</span></span><strong>{{ $value }}</strong></div>
              @endif
              @endforeach
            </div>
          </section>
        </div>
        <div class="col-12 col-xl-4">
          <div class="submission-review-date-stack">
            <section class="submission-review-group submission-review-timeline" aria-labelledby="submission-timeline-title">
              <div class="submission-review-timeline__header">
                <h5 class="submission-review-group__title" id="submission-timeline-title">Timeline</h5>
                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#submission-detailed-timeline-{{ $disclosure->id }}">
                  <i class="icon-base ri ri-time-line me-1" aria-hidden="true"></i>Detailed timeline
                </button>
              </div>
              @php
              $timelineCurrentStep = match ($disclosure->status) {
                'endorsed' => 3,
                'filed' => 4,
                default => 2,
              };
              $reviewStepLabel = match ($disclosure->status) {
                'returned' => 'Revision Requested',
                'revised_submission' => 'Revision submitted',
                default => 'Review',
              };
              $timelineSteps = [
                ['key' => 'research-approved', 'label' => 'Research Approved', 'date' => $disclosure->research_approval_date, 'icon' => 'ri-calendar-check-line'],
                ['key' => 'idf-submitted', 'label' => 'IDF Submitted', 'date' => $disclosure->created_at, 'icon' => 'ri-file-list-3-line'],
                ['key' => 'review', 'label' => $reviewStepLabel, 'date' => in_array($disclosure->status, ['under_review', 'returned', 'revised_submission'], true) ? $disclosure->updated_at : null, 'icon' => 'ri-search-eye-line'],
                ['key' => 'approved', 'label' => 'IDF Approved', 'date' => $disclosure->endorsed_at, 'icon' => 'ri-checkbox-circle-line'],
                ['key' => 'ipophl-applied', 'label' => 'IDF Applied to IPOPHL', 'date' => $disclosure->status === 'filed' ? $disclosure->updated_at : null, 'icon' => 'ri-government-line'],
              ];
              @endphp
              <ol class="submission-review-stepper" aria-label="Submission timeline">
                @foreach ($timelineSteps as $index => $step)
                @php
                $stepState = $index < $timelineCurrentStep ? 'is-complete' : ($index === $timelineCurrentStep ? 'is-current' : 'is-upcoming');
                $stepDate = $step['date'];
                @endphp
                <li class="submission-review-stepper__step {{ $stepState }}" data-timeline-step="{{ $step['key'] }}">
                  <span class="submission-review-stepper__indicator"><i class="icon-base ri {{ $index < $timelineCurrentStep ? 'ri-check-line' : $step['icon'] }}" aria-hidden="true"></i></span>
                  <span class="submission-review-stepper__label">{{ $step['label'] }}</span>
                  @if ($stepDate)
                  <time class="submission-review-stepper__date" datetime="{{ $stepDate->toDateString() }}">{{ $stepDate->format('M j, Y') }}</time>
                  @else
                  <span class="submission-review-stepper__date">{{ $index === $timelineCurrentStep ? 'In progress' : ($index < $timelineCurrentStep ? 'Not recorded' : 'Pending') }}</span>
                  @endif
                </li>
                @endforeach
              </ol>
            </section>
            <section class="submission-review-group submission-review-important-dates" aria-labelledby="submission-dates-group">
              <h5 class="submission-review-group__title" id="submission-dates-group">Important dates</h5>
              <div class="submission-review-group__fields">
                <div class="submission-review-meta" data-review-date="creation"><span><i class="icon-base ri ri-calendar-line" aria-hidden="true"></i><span>Date of creation</span></span><strong>{{ $disclosure->date_of_creation?->format('M j, Y') ?: 'Not provided' }}</strong></div>
                <div class="submission-review-meta" data-review-date="approval"><span><i class="icon-base ri ri-calendar-check-line" aria-hidden="true"></i><span>Research approval date</span></span><strong>{{ $disclosure->research_approval_date?->format('M j, Y') ?: 'Not provided' }}</strong></div>
                <div class="submission-review-meta" data-review-date="disclosure"><span><i class="icon-base ri ri-calendar-event-line" aria-hidden="true"></i><span>Disclosure date</span></span><strong>{{ $disclosure->disclosure_date?->format('M j, Y') ?: 'Not provided' }}</strong></div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </section>

    <article class="card mb-4 submission-review-section" id="submission-technologies" data-review-section="technologies">
      <header class="card-header">
        <div class="d-flex align-items-center gap-3">
          <span class="avatar avatar-sm"><span class="avatar-initial bg-label-secondary text-body-secondary rounded"><i class="icon-base ri ri-lightbulb-line" aria-hidden="true"></i></span></span>
          <div>
            <h4 class="card-title mb-0 fw-semibold">Technical / creative descriptions</h4>
          </div>
        </div>
      </header>
      <div class="card-body">
      @if ($disclosure->technologies->isNotEmpty())
      @if ($canEditRevisionDetails)
      <form method="POST" action="{{ route('dashboard.submissions.revision-details', $disclosure) }}" class="submission-revision-details-form" novalidate>
        @csrf
        <input type="hidden" name="section" value="technologies">
      @endif
      <section aria-labelledby="submitted-technologies-heading-{{ $disclosure->id }}">
        <h5 class="visually-hidden" id="submitted-technologies-heading-{{ $disclosure->id }}">Submitted technologies</h5>
        @if ($canEditRevisionDetails)
        @foreach ($disclosure->technologies as $technology)
        @php
        $technologyFiles = $technology->files->keyBy('narrative_key');
        @endphp
        <div class="table-responsive submission-review-table-wrap mb-4">
          <table class="table table-hover align-middle mb-0 submission-review-data-table">
            <caption class="caption-top fw-semibold text-body">Technology {{ $loop->iteration }}: {{ $technology->technology_title }}</caption>
            <thead><tr><th scope="col">TECHNICAL / CREATIVE INFORMATION</th><th scope="col">SUBMITTED DETAILS</th></tr></thead>
            <tbody>
              <tr>
                <th scope="row">Technology title</th>
                <td>
                  @if ($canEditRevisionDetails)
                  <input class="form-control" name="technologies[{{ $technology->id }}][technology_title]" value="{{ old("technologies.{$technology->id}.technology_title", $technology->technology_title) }}" maxlength="500" required aria-label="Technology title">
                  @else
                  {{ $technology->technology_title }}
                  @endif
                </td>
              </tr>
          @foreach ([
            'abstract_summary' => 'Abstract',
            'invention_summary' => 'Invention Summary',
            'detailed_description' => 'Description',
            'technical_field' => 'Field',
            'background_summary' => 'Background',
            'examples_results' => 'Results',
          ] as $field => $label)
          @php $narrativeFile = $technologyFiles->get($field); @endphp
              <tr>
                <th scope="row">{{ $label }}</th>
                <td>
                  @if ($canEditRevisionDetails)
                  <textarea class="form-control" name="technologies[{{ $technology->id }}][{{ $field }}]" rows="4" aria-label="{{ $label }}">{{ old("technologies.{$technology->id}.{$field}", $technology->{$field}) }}</textarea>
                  @if (filled($technology->{$field}))
                  <button class="btn btn-sm btn-outline-secondary mt-2" type="button" data-bs-toggle="modal" data-bs-target="#technology-narrative-modal-{{ $disclosure->id }}" data-narrative-title="{{ $label }}" data-narrative-content="{{ $technology->{$field} }}">
                    <i class="icon-base ri ri-eye-line me-1" aria-hidden="true"></i>View
                  </button>
                  @endif
                  @elseif ($narrativeFile)
                  <a class="btn btn-icon btn-text-secondary" href="{{ route('dashboard.submissions.technology-files.preview', [$disclosure, $narrativeFile]) }}" target="_blank" rel="noopener noreferrer" aria-label="View PDF for {{ $label }}" title="View PDF">
                    <i class="icon-base ri ri-eye-line" aria-hidden="true"></i>
                  </a>
                  @elseif (filled($technology->{$field}))
                  <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#technology-narrative-modal-{{ $disclosure->id }}" data-narrative-title="{{ $label }}" data-narrative-content="{{ $technology->{$field} }}">
                    <i class="icon-base ri ri-eye-line me-1" aria-hidden="true"></i>View
                  </button>
                  @else
                  <span class="text-body-secondary">No narrative provided.</span>
                  @endif
                  @if ($canEditRevisionDetails && $narrativeFile)
                  <a class="btn btn-icon btn-text-secondary ms-2" href="{{ route('dashboard.submissions.technology-files.preview', [$disclosure, $narrativeFile]) }}" target="_blank" rel="noopener noreferrer" aria-label="View PDF for {{ $label }}" title="View PDF">
                    <i class="icon-base ri ri-eye-line" aria-hidden="true"></i>
                  </a>
                  @endif
                </td>
              </tr>
          @endforeach
            </tbody>
          </table>
        </div>
        @endforeach
        @else
        <div class="table-responsive submission-review-table-wrap">
          <table class="table table-hover align-middle technology-table mb-0">
            <thead>
              <tr>
                <th scope="col">Technology Title</th>
                @foreach ([
                  'abstract_summary' => 'Abstract',
                  'invention_summary' => 'Invention Summary',
                  'detailed_description' => 'Description',
                  'technical_field' => 'Field',
                  'background_summary' => 'Background',
                  'examples_results' => 'Results',
                ] as $label)
                <th scope="col" class="text-center">{{ $label }}</th>
                @endforeach
                <th scope="col" class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($disclosure->technologies as $technology)
              @php $technologyFiles = $technology->files->keyBy('narrative_key'); @endphp
              <tr>
                <td class="fw-semibold">{{ $technology->technology_title }}</td>
                @foreach ([
                  'abstract_summary' => 'Abstract',
                  'invention_summary' => 'Invention Summary',
                  'detailed_description' => 'Description',
                  'technical_field' => 'Field',
                  'background_summary' => 'Background',
                  'examples_results' => 'Results',
                ] as $field => $label)
                @php $isComplete = filled($technology->{$field}) || $technologyFiles->has($field); @endphp
                <td class="technology-status-cell text-center">
                  <span class="technology-status-icon {{ $isComplete ? 'is-complete' : 'is-incomplete' }}" aria-label="{{ $label }}: {{ $isComplete ? 'complete' : 'incomplete' }}" title="{{ $label }}: {{ $isComplete ? 'Complete' : 'Incomplete' }}">
                    <i class="icon-base ri {{ $isComplete ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill' }}" aria-hidden="true"></i>
                  </span>
                </td>
                @endforeach
                <td class="text-end text-nowrap">
                  <div class="dropdown">
                    <button class="btn text-body-secondary p-0 creator-action-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $technology->technology_title }}">
                      <i class="icon-base ri ri-more-2-fill" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end py-2">
                      <li>
                        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#submission-technology-view-{{ $disclosure->id }}-{{ $technology->id }}">
                          <i class="icon-base ri ri-eye-line me-2" aria-hidden="true"></i>View
                        </button>
                      </li>
                    </ul>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @foreach ($disclosure->technologies as $technology)
        @php $technologyFiles = $technology->files->keyBy('narrative_key'); @endphp
        <div class="modal fade" id="submission-technology-view-{{ $disclosure->id }}-{{ $technology->id }}" tabindex="-1" aria-labelledby="submission-technology-view-title-{{ $disclosure->id }}-{{ $technology->id }}" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable idf-modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title fw-bold" id="submission-technology-view-title-{{ $disclosure->id }}-{{ $technology->id }}">{{ $technology->technology_title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="accordion accordion-flush border rounded">
                  @foreach ([
                    'abstract_summary' => 'Abstract / Summary',
                    'technical_field' => 'Technical / Creative Field',
                    'background_summary' => 'Background / Problem',
                    'invention_summary' => 'Summary of the Invention / Innovation',
                    'detailed_description' => 'Detailed Description',
                    'examples_results' => 'Examples / Experimental Results / Validation',
                  ] as $field => $label)
                  @php
                    $narrativeFile = $technologyFiles->get($field);
                    $narrativeText = trim((string) $technology->{$field});
                    $collapseId = "submission-technology-{$technology->id}-{$field}";
                  @endphp
                  @if ($narrativeText || $narrativeFile)
                  <div class="accordion-item">
                    <h2 class="accordion-header">
                      <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">{{ $label }}</button>
                    </h2>
                    <div class="accordion-collapse collapse" id="{{ $collapseId }}">
                      <div class="accordion-body">
                        @if ($narrativeText)
                        <textarea class="form-control mb-3" rows="4" readonly aria-label="{{ $label }}">{{ $narrativeText }}</textarea>
                        @endif
                        @if ($narrativeFile)
                        <div class="d-flex align-items-center gap-3">
                          <i class="icon-base ri ri-file-pdf-2-line icon-24px text-danger flex-shrink-0" aria-hidden="true"></i>
                          <div class="flex-grow-1 min-w-0">
                            <span class="badge bg-label-danger mb-1">PDF DOCUMENT</span>
                            <div class="fw-medium text-break">{{ $narrativeFile->original_name }}</div>
                          </div>
                          <a class="btn btn-icon btn-text-secondary flex-shrink-0" href="{{ route('dashboard.submissions.technology-files.preview', [$disclosure, $narrativeFile]) }}" target="_blank" rel="noopener noreferrer" aria-label="View {{ $label }} PDF: {{ $narrativeFile->original_name }}" title="View PDF">
                            <i class="icon-base ri ri-eye-line" aria-hidden="true"></i>
                          </a>
                        </div>
                        @endif
                      </div>
                    </div>
                  </div>
                  @endif
                  @endforeach
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        @endforeach
        @endif
      </section>
      @if ($canEditRevisionDetails)
        <div class="d-flex justify-content-end">
          <button class="btn btn-primary" type="submit"><i class="icon-base ri ri-save-line me-1" aria-hidden="true"></i>Save technical / creative changes</button>
        </div>
      </form>
      @endif
      <div class="modal fade" id="technology-narrative-modal-{{ $disclosure->id }}" tabindex="-1" aria-labelledby="technology-narrative-modal-title-{{ $disclosure->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="technology-narrative-modal-title-{{ $disclosure->id }}">Narrative</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <label class="form-label text-uppercase small fw-semibold" for="technology-narrative-modal-content-{{ $disclosure->id }}" data-narrative-modal-title>Submitted details</label>
              <textarea class="form-control" id="technology-narrative-modal-content-{{ $disclosure->id }}" rows="10" readonly data-narrative-modal-content></textarea>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>
      @elseif (collect([
        $disclosure->abstract_summary,
        $disclosure->technical_field,
        $disclosure->background_summary,
        $disclosure->invention_summary,
        $disclosure->detailed_description,
        $disclosure->examples_results,
        $disclosure->drawings_description,
      ])->filter()->isNotEmpty())
      <section>
        <div class="accordion accordion-flush submission-narratives" id="submission-narratives-{{ $disclosure->id }}">
        @foreach ([
          'abstract_summary' => 'Abstract / Summary',
          'technical_field' => 'Technical / Creative Field',
          'background_summary' => 'Background / Problem',
          'invention_summary' => 'Summary of the Invention / Innovation',
          'detailed_description' => 'Detailed Description',
          'examples_results' => 'Examples / Experimental Results / Validation',
        ] as $field => $label)
        <div class="accordion-item">
          <h2 class="accordion-header" id="{{ $field }}-heading-{{ $disclosure->id }}">
            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $field }}-panel-{{ $disclosure->id }}" aria-expanded="false" aria-controls="{{ $field }}-panel-{{ $disclosure->id }}">{{ $label }}</button>
          </h2>
          <div id="{{ $field }}-panel-{{ $disclosure->id }}" class="accordion-collapse collapse" aria-labelledby="{{ $field }}-heading-{{ $disclosure->id }}" data-bs-parent="#submission-narratives-{{ $disclosure->id }}">
            <div class="accordion-body">
              <p class="submission-narrative__text mb-0">{{ $disclosure->{$field} ?: ($field === 'background_summary' ? $disclosure->background_summary : '') }}</p>
            </div>
          </div>
        </div>
        @endforeach
        @if ($disclosure->drawings_description)
        <div class="accordion-item">
          <h2 class="accordion-header" id="drawings-heading-{{ $disclosure->id }}">
            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#drawings-panel-{{ $disclosure->id }}" aria-expanded="false" aria-controls="drawings-panel-{{ $disclosure->id }}">Drawing description</button>
          </h2>
          <div id="drawings-panel-{{ $disclosure->id }}" class="accordion-collapse collapse" aria-labelledby="drawings-heading-{{ $disclosure->id }}" data-bs-parent="#submission-narratives-{{ $disclosure->id }}">
            <div class="accordion-body">
              <p class="submission-narrative__text mb-0">{{ $disclosure->drawings_description }}</p>
            </div>
          </div>
        </div>
        @endif
      </div>
      </section>
      @else
      <div class="submission-review-empty"><i class="icon-base ri ri-inbox-2-line" aria-hidden="true"></i><span>No technologies or technical narratives were provided.</span></div>
      @endif
      </div>
    </article>

    <article class="card mb-4 submission-review-section" id="submission-creators" data-review-section="inventors">
      <header class="card-header">
        <div class="d-flex align-items-center gap-3">
          <span class="avatar avatar-sm"><span class="avatar-initial bg-label-secondary text-body-secondary rounded"><i class="icon-base ri ri-team-line" aria-hidden="true"></i></span></span>
          <div>
            <h4 class="card-title mb-0 fw-semibold">Creators and contributions</h4>
          </div>
        </div>
      </header>
      <div class="card-body">
        @if ($canEditRevisionDetails)
        <form method="POST" action="{{ route('dashboard.submissions.revision-details', $disclosure) }}" class="submission-revision-details-form" novalidate>
          @csrf
          <input type="hidden" name="section" value="inventors">
        @endif
        @if ($canEditRevisionDetails)
        <div class="row g-3">
          @foreach ($disclosure->inventors as $inventor)
          @php
          $creatorName = $inventor->full_name ?: trim(collect([$inventor->first_name, $inventor->middle_initial, $inventor->last_name, $inventor->suffix])->filter()->implode(' '));
          $natureLabels = [
            'conception' => 'Conception',
            'identification_essential_element' => 'Identification of an essential element',
            'technical_feature' => 'Technical feature',
            'experimental_refinement' => 'Experimental refinement',
            'reduction_to_practice' => 'Reduction to practice',
            'testing_validation' => 'Testing / validation',
            'optimization' => 'Optimization',
            'other_substantive' => 'Other substantive contribution',
          ];
          @endphp
          <div class="col-12 col-xl-6">
            <section class="submission-review-creator">
              <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 submission-review-creator__header">
                <h5 class="mb-0 fw-semibold">{{ $creatorName }}</h5>
                @if ($inventor->is_primary)<span class="badge bg-label-secondary">Lead creator</span>@endif
              </div>
              <div class="table-responsive submission-review-table-wrap">
                <table class="table table-hover align-middle mb-0 inventors-table submission-review-data-table submission-review-creator-table">
                  <thead><tr><th scope="col">CREATOR INFORMATION</th><th scope="col">SUBMITTED DETAILS</th></tr></thead>
                  <tbody>
                    @foreach ([
                      'first_name' => 'First name',
                      'middle_initial' => 'Middle initial',
                      'last_name' => 'Last name',
                      'suffix' => 'Suffix',
                      'email' => 'Email address',
                      'country_of_citizenship' => 'Citizenship',
                      'position_affiliation' => 'Position / affiliation',
                      'contribution_percent' => 'Contribution share (%)',
                      'contribution_other' => 'Other contribution',
                      'technical_contribution' => 'Specific technical contribution',
                      'signature_name' => 'Typed signature',
                    ] as $field => $label)
                    <tr>
                      <th scope="row">{{ $label }}</th>
                      <td>
                        @if ($canEditRevisionDetails)
                        @if ($field === 'technical_contribution')
                        <textarea class="form-control" name="inventors[{{ $inventor->id }}][{{ $field }}]" rows="3" required aria-label="{{ $label }}">{{ $inventor->{$field} }}</textarea>
                        @else
                        <input
                          class="form-control"
                          name="inventors[{{ $inventor->id }}][{{ $field }}]"
                          value="{{ old("inventors.{$inventor->id}.{$field}", $inventor->{$field}) }}"
                          @if ($field === 'email') type="email" required maxlength="255"
                          @elseif ($field === 'contribution_percent') type="number" min="0.01" max="100" step="0.01" required
                          @elseif ($field === 'first_name' || $field === 'last_name') required maxlength="100"
                          @elseif ($field === 'middle_initial') maxlength="1"
                          @elseif ($field === 'suffix') maxlength="20"
                          @elseif ($field === 'position_affiliation') required maxlength="500"
                          @elseif ($field === 'country_of_citizenship') maxlength="255"
                          @elseif ($field === 'contribution_other') maxlength="500"
                          @elseif ($field === 'signature_name') required maxlength="255"
                          @endif
                          aria-label="{{ $label }}">
                        @endif
                        @else
                        @if ($field === 'contribution_percent')
                        {{ number_format((float) $inventor->{$field}, 2) }}%
                        @else
                        {{ $inventor->{$field} ?: 'Not provided' }}
                        @endif
                        @endif
                      </td>
                    </tr>
                    @endforeach
                    <tr>
                      <th scope="row">Nature of contribution</th>
                      <td>
                        @if ($canEditRevisionDetails)
                        @php $selectedNatures = old("inventors.{$inventor->id}.contribution_nature", $inventor->contribution_nature ?? []); @endphp
                        <select class="form-select" name="inventors[{{ $inventor->id }}][contribution_nature][]" multiple size="4" required aria-label="Nature of contribution">
                          @foreach ($natureLabels as $nature => $label)
                          <option value="{{ $nature }}" @selected(in_array($nature, $selectedNatures, true))>{{ $label }}</option>
                          @endforeach
                        </select>
                        <div class="form-text">Use Ctrl or Command to select more than one option.</div>
                        @else
                        {{ collect($inventor->contribution_nature ?? [])->map(fn ($nature) => $natureLabels[$nature] ?? $nature)->implode(', ') ?: 'Not provided' }}
                        @if ($inventor->contribution_other) — {{ $inventor->contribution_other }}@endif
                        @endif
                      </td>
                    </tr>
                    <tr>
                      <th scope="row">Declaration</th>
                      <td>
                        @if ($canEditRevisionDetails)
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="inventors[{{ $inventor->id }}][declaration_confirmed]" value="1" required @checked(old("inventors.{$inventor->id}.declaration_confirmed", $inventor->declaration_confirmed))>
                          <label class="form-check-label">I confirm this information is true and correct.</label>
                        </div>
                        @else
                        {{ $inventor->declaration_confirmed ? 'Confirmed' : 'Not confirmed' }}
                        @endif
                      </td>
                    </tr>
                    <tr>
                      <th scope="row">Date signed</th>
                      <td>
                        @if ($canEditRevisionDetails)
                        <input class="form-control" type="date" name="inventors[{{ $inventor->id }}][declaration_date]" value="{{ old("inventors.{$inventor->id}.declaration_date", $inventor->declaration_date?->format('Y-m-d')) }}" required aria-label="Date signed">
                        @else
                        {{ $inventor->declaration_date?->format('M j, Y') ?: 'Not provided' }}
                        @endif
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </section>
          </div>
          @endforeach
        </div>
        @else
        <div class="table-responsive submission-review-table-wrap">
          <table class="table table-hover align-middle inventors-table mb-0">
            <thead>
              <tr>
                <th scope="col">Name</th>
                <th scope="col">Position</th>
                <th scope="col">Declared Contribution</th>
                <th scope="col">Technical Contribution</th>
                <th scope="col" class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($disclosure->inventors as $inventor)
              @php
                $creatorName = $inventor->full_name ?: trim(collect([$inventor->first_name, $inventor->middle_initial, $inventor->last_name, $inventor->suffix])->filter()->implode(' '));
              @endphp
              <tr>
                <td class="fw-semibold">{{ $creatorName }}</td>
                <td>{{ $inventor->position_affiliation ?: '—' }}</td>
                <td>{{ number_format((float) $inventor->contribution_percent, 2) }}%</td>
                <td>{{ $inventor->technical_contribution }}</td>
                <td class="text-end text-nowrap">
                  <div class="dropdown">
                    <button class="btn text-body-secondary p-0 creator-action-trigger" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $creatorName }}">
                      <i class="icon-base ri ri-more-2-fill" aria-hidden="true"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end py-2">
                      <li>
                        <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#submission-creator-view-{{ $disclosure->id }}-{{ $inventor->id }}">
                          <i class="icon-base ri ri-eye-line me-2" aria-hidden="true"></i>View
                        </button>
                      </li>
                    </ul>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        @foreach ($disclosure->inventors as $inventor)
        @php
          $creatorName = $inventor->full_name ?: trim(collect([$inventor->first_name, $inventor->middle_initial, $inventor->last_name, $inventor->suffix])->filter()->implode(' '));
          $natureLabels = [
            'conception' => 'Conception',
            'identification_essential_element' => 'Identification of an essential element',
            'technical_feature' => 'Technical feature',
            'experimental_refinement' => 'Experimental refinement',
            'reduction_to_practice' => 'Reduction to practice',
            'testing_validation' => 'Testing / validation',
            'optimization' => 'Optimization',
            'other_substantive' => 'Other substantive contribution',
          ];
        @endphp
        <div class="modal fade" id="submission-creator-view-{{ $disclosure->id }}-{{ $inventor->id }}" tabindex="-1" aria-labelledby="submission-creator-view-title-{{ $disclosure->id }}-{{ $inventor->id }}" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable idf-modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title fw-bold" id="submission-creator-view-title-{{ $disclosure->id }}-{{ $inventor->id }}">{{ $creatorName }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="row g-3">
                  @foreach ([
                    'First name' => $inventor->first_name,
                    'Middle initial' => $inventor->middle_initial,
                    'Last name' => $inventor->last_name,
                    'Suffix' => $inventor->suffix,
                    'Email address' => $inventor->email,
                    'Citizenship' => $inventor->country_of_citizenship,
                    'Position / affiliation' => $inventor->position_affiliation,
                    'Declared contribution' => number_format((float) $inventor->contribution_percent, 2) . '%',
                    'Specific technical contribution' => $inventor->technical_contribution,
                    'Other contribution' => $inventor->contribution_other,
                    'Typed signature' => $inventor->signature_name,
                    'Date signed' => $inventor->declaration_date?->format('M j, Y'),
                    'Declaration' => $inventor->declaration_confirmed ? 'Confirmed' : 'Not confirmed',
                  ] as $label => $value)
                  @if (filled($value))
                  <div class="{{ in_array($label, ['Specific technical contribution', 'Other contribution'], true) ? 'col-12' : 'col-md-6' }}">
                    <label class="form-label text-uppercase small fw-semibold">{{ $label }}</label>
                    <div class="form-control bg-lighter creator-view-value" aria-readonly="true">{{ $value }}</div>
                  </div>
                  @endif
                  @endforeach
                  <div class="col-12">
                    <label class="form-label text-uppercase small fw-semibold">Nature of contribution</label>
                    <div class="form-control bg-lighter creator-view-value" aria-readonly="true">{{ collect($inventor->contribution_nature ?? [])->map(fn ($nature) => $natureLabels[$nature] ?? $nature)->implode(', ') ?: 'Not provided' }}</div>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        @endforeach
        @endif
        @if ($canEditRevisionDetails)
          <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-primary" type="submit"><i class="icon-base ri ri-save-line me-1" aria-hidden="true"></i>Save creator changes</button>
          </div>
        </form>
        @endif
      </div>
    </article>

    <article class="card mb-4 submission-review-section" id="submission-costing" data-review-section="costing">
      <header class="card-header">
        <div class="d-flex align-items-center gap-3">
          <span class="avatar avatar-sm"><span class="avatar-initial bg-label-secondary text-body-secondary rounded"><i class="icon-base ri ri-money-dollar-circle-line" aria-hidden="true"></i></span></span>
          <div>
            <h4 class="card-title mb-0 fw-semibold">Costing of materials and resources</h4>
          </div>
        </div>
      </header>
      <div class="card-body">
      @if ($disclosure->costItems->isNotEmpty())
      @if ($canEditRevisionDetails)
      <form method="POST" action="{{ route('dashboard.submissions.revision-details', $disclosure) }}" class="submission-revision-details-form" novalidate>
        @csrf
        <input type="hidden" name="section" value="cost_items">
      @endif
        <div class="table-responsive submission-review-table-wrap"><table class="table table-hover align-middle mb-0 materials-table submission-review-costing-table submission-review-data-table">
          <thead><tr><th scope="col">ITEM / MATERIAL</th><th scope="col">DESCRIPTION / SPECIFICATION</th><th scope="col">QTY.</th><th scope="col">UNIT COST</th><th scope="col">LINE TOTAL</th><th scope="col">PURPOSE / REMARKS</th></tr></thead>
          <tbody>
            @foreach ($disclosure->costItems as $costItem)
            <tr>
              <td>
                @if ($canEditRevisionDetails)
                <input class="form-control" name="cost_items[{{ $costItem->id }}][item]" value="{{ old("cost_items.{$costItem->id}.item", $costItem->item) }}" maxlength="255" required aria-label="Item or material">
                @else
                {{ $costItem->item }}
                @endif
              </td>
              <td>
                @if ($canEditRevisionDetails)
                <textarea class="form-control" name="cost_items[{{ $costItem->id }}][description]" rows="2" maxlength="2000" required aria-label="Description or specification">{{ old("cost_items.{$costItem->id}.description", $costItem->description) }}</textarea>
                @else
                {{ $costItem->description }}
                @endif
              </td>
              <td>
                @if ($canEditRevisionDetails)
                <input class="form-control submission-review-cost-input" type="number" name="cost_items[{{ $costItem->id }}][quantity]" value="{{ old("cost_items.{$costItem->id}.quantity", $costItem->quantity) }}" min="0.01" step="0.01" required aria-label="Quantity">
                @else
                {{ $costItem->quantity }}
                @endif
              </td>
              <td>
                @if ($canEditRevisionDetails)
                <input class="form-control submission-review-cost-input" type="number" name="cost_items[{{ $costItem->id }}][unit_cost]" value="{{ old("cost_items.{$costItem->id}.unit_cost", $costItem->unit_cost) }}" min="0" step="0.01" required aria-label="Unit cost">
                @else
                ₱{{ number_format((float) $costItem->unit_cost, 2) }}
                @endif
              </td>
              <td>₱{{ number_format((float) $costItem->total_cost, 2) }}</td>
              <td>
                @if ($canEditRevisionDetails)
                <textarea class="form-control" name="cost_items[{{ $costItem->id }}][purpose]" rows="2" maxlength="2000" aria-label="Purpose or remarks">{{ old("cost_items.{$costItem->id}.purpose", $costItem->purpose) }}</textarea>
                @else
                {{ $costItem->purpose ?: '—' }}
                @endif
              </td>
            </tr>
            @endforeach
          </tbody>
          <tfoot><tr><th colspan="4" class="text-end">Total estimated development cost</th><th>₱{{ number_format((float) $disclosure->total_development_cost, 2) }}</th><td></td></tr></tfoot>
        </table></div>
      @if ($canEditRevisionDetails)
        <div class="d-flex justify-content-end mt-3">
          <button class="btn btn-primary" type="submit"><i class="icon-base ri ri-save-line me-1" aria-hidden="true"></i>Save costing changes</button>
        </div>
      </form>
      @endif
      @else
        <div class="submission-review-empty"><i class="icon-base ri ri-inbox-2-line" aria-hidden="true"></i><span>No individual material or resource costs were listed.</span></div>
      @endif
      </div>
    </article>

    <article class="card mb-4 submission-review-section" id="submission-files" data-review-section="files">
      <header class="card-header">
        <div class="d-flex align-items-center gap-3">
          <span class="avatar avatar-sm"><span class="avatar-initial bg-label-secondary text-body-secondary rounded"><i class="icon-base ri ri-attachment-2" aria-hidden="true"></i></span></span>
          <div>
            <h4 class="card-title mb-0 fw-semibold">Supporting files and revisions</h4>
          </div>
        </div>
      </header>
      <div class="card-body">
        @php
        $canSubmitFileRevisions = in_array($disclosure->status, ['under_review', 'returned', 'revised_submission'], true);
        $hasRequestedFileRevisions = $disclosure->attachments->contains(fn ($attachment) => $attachment->needs_revision);
        @endphp
        <form
          class="attachment-update-form"
          id="attachment-update-form-{{ $disclosure->id }}"
          method="POST"
          enctype="multipart/form-data"
          action="{{ route('dashboard.submissions.attachments.store', $disclosure) }}"
          data-confirm-title="Upload revised files?"
          data-confirm-text="Only files marked for revision will be updated."
          data-disclosure-status="{{ $disclosure->status }}"
          data-disclosure-status-id="{{ $disclosure->id }}">
          @csrf
          <input type="hidden" name="return_to_review" value="1">
        <div class="row g-4">
          @foreach ($fileSlots as $type => $label)
          @php
          $attachment = $attachmentsByType->get($type);
          $previewModalId = $attachment ? 'submission-attachment-preview-' . $attachment->id : null;
          $previewUrl = $attachment
          ? route('dashboard.submissions.attachments.preview', [$disclosure, $attachment])
          : null;
          $extension = $attachment ? strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION)) : null;
          $isImage = in_array($extension, ['jpg', 'jpeg', 'png'], true);
          $isPdf = $extension === 'pdf';
          @endphp
          <div class="col-md-4">
            <div class="border rounded p-3 user-submission-file-card {{ $attachment?->needs_revision ? 'user-submission-file-card--revision-requested' : '' }} {{ $attachment?->is_latest_submission && $attachment?->version > 1 ? 'user-submission-file-card--revised' : '' }}" @if ($attachment) data-file-attachment="{{ $attachment->id }}" @endif>
              <div class="d-flex justify-content-between align-items-center gap-2 user-submission-file-card__header">
                <h6 class="mb-0 user-submission-file-card__type">{{ $label }}</h6>
                <div class="d-flex flex-wrap align-items-center gap-2">
                  @if ($attachment && $attachment->needs_revision)
                  <span class="badge bg-label-warning">Revision requested</span>
                  @endif
                  @if ($attachment && $attachment->version > 1)
                  <span class="badge bg-label-info flex-shrink-0">V{{ $attachment->version }}</span>
                  @endif
                </div>
              </div>
              @if ($attachment)
              <div class="d-flex align-items-start justify-content-between gap-2 mt-2 mb-1">
                <p class="small mb-0 user-submission-file-card__name" title="{{ $attachment->original_name }}">{{ $attachment->original_name }}</p>
                <button class="btn btn-link btn-sm p-0 text-body-secondary text-decoration-none user-submission-file-card__preview flat-icon-button flex-shrink-0" type="button" data-bs-toggle="modal" data-bs-target="#{{ $previewModalId }}" aria-label="Preview {{ $attachment->original_name }}" title="Preview file">
                  <i class="icon-base ri ri-eye-line icon-24px" aria-hidden="true"></i>
                </button>
              </div>
              <p class="small text-body-secondary mb-0 user-submission-file-card__meta">{{ number_format($attachment->size_bytes / 1048576, 2) }} MB &middot; Updated {{ ($attachment->submitted_at ?? $attachment->updated_at)->format('M j, Y') }}</p>
              @else
              <p class="small text-body-secondary mb-0 mt-2 user-submission-file-card__name">No file uploaded</p>
              @endif
              @if ($attachment)
              <div class="mt-3 {{ $attachment->needs_revision && $canSubmitFileRevisions ? '' : 'd-none' }}" data-revision-upload-control="{{ $attachment->id }}">
                <label class="form-label small" for="attachment-{{ $disclosure->id }}-{{ $type }}">Choose revised file</label>
                <input
                  class="form-control form-control-sm"
                  id="attachment-{{ $disclosure->id }}-{{ $type }}"
                  name="attachments[{{ $type }}]"
                  type="file"
                  accept="application/pdf,.pdf"
                  data-file-kind="document"
                  data-file-label="{{ $label }}"
                  data-file-attachment-input="{{ $attachment->id }}"
                  @disabled(! ($attachment->needs_revision && $canSubmitFileRevisions))>
              </div>
              @endif
            </div>
          </div>
          @if ($attachment)
          <div class="modal fade" id="{{ $previewModalId }}" tabindex="-1" aria-labelledby="{{ $previewModalId }}-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered idf-modal-dialog" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <div>
                    <h5 class="modal-title" id="{{ $previewModalId }}-title">{{ $attachment->original_name }}</h5>
                    <div class="alert alert-warning mt-3 mb-0 {{ $attachment->needs_revision ? '' : 'd-none' }}" role="status" data-file-revision-modal-notes="{{ $attachment->id }}">
                      <div class="d-flex align-items-center gap-2 fw-semibold mb-1">
                        <i class="icon-base ri ri-error-warning-line" aria-hidden="true"></i>
                        <span>Changes requested</span>
                      </div>
                      <p class="mb-0" data-file-revision-notes="{{ $attachment->id }}">{{ $attachment->revision_notes }}</p>
                    </div>
                  </div>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  @if ($isPdf)
                  <iframe class="attachment-preview-frame" src="{{ $previewUrl }}" title="Preview of {{ $attachment->original_name }}"></iframe>
                  @elseif ($isImage)
                  <img class="img-fluid d-block mx-auto" src="{{ $previewUrl }}" alt="Preview of {{ $attachment->original_name }}">
                  @else
                  <div class="alert alert-info mb-0" role="status">In-browser preview is only available for PDF and image files.</div>
                  @endif
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
              </div>
            </div>
          </div>
          @endif
          @endforeach
        </div>
        </form>
      </div>
    <div class="card-footer d-flex flex-wrap justify-content-between gap-3">
      <form class="disclosure-delete-form me-auto" method="POST" action="{{ route('dashboard.submissions.destroy', $disclosure) }}" data-confirm-title="Are you sure to delete this submission?" data-confirm-text="Its database data and files will be retained.">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger" type="submit"><i class="icon-base ri ri-delete-bin-line me-1"></i>Delete disclosure</button>
      </form>
      <button
        class="btn btn-primary {{ $hasRequestedFileRevisions && $canSubmitFileRevisions ? '' : 'd-none' }}"
        type="submit"
        form="attachment-update-form-{{ $disclosure->id }}"
        data-update-file-button>
        <i class="icon-base ri ri-upload-2-line me-1" aria-hidden="true"></i>Update File
      </button>
      <span class="small text-body-secondary align-self-center {{ $hasRequestedFileRevisions && $canSubmitFileRevisions ? 'd-none' : '' }}" data-file-update-notice>
        {{ $disclosure->status === 'submitted' ? 'Files cannot be changed until the IP Head reviews this submission.' : 'No files have been marked for revision.' }}
      </span>
    </div>
    </article>
    <div class="modal fade" id="submission-detailed-timeline-{{ $disclosure->id }}" tabindex="-1" aria-labelledby="submission-detailed-timeline-title-{{ $disclosure->id }}" aria-hidden="true">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <div>
              <h5 class="modal-title" id="submission-detailed-timeline-title-{{ $disclosure->id }}">Detailed timeline</h5>
              <p class="small text-body-secondary mb-0">File uploads and revision requests, in chronological order.</p>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="submission-file-timeline-legend mb-3">
              <span><i class="submission-file-timeline__legend-dot" aria-hidden="true"></i>File uploaded</span>
              <span><i class="submission-file-timeline__legend-dot is-revision" aria-hidden="true"></i>Revision requested</span>
            </div>
            @forelse ($attachmentTimelineEvents as $entry)
            @if ($loop->first)<ol class="submission-file-timeline" aria-label="File upload and revision history">@endif
              @php
              $timelineEvent = $entry['event'];
              $isRevisionEvent = $timelineEvent->event_type === 'revision_requested';
              @endphp
              <li class="submission-file-timeline__event {{ $isRevisionEvent ? 'is-revision' : '' }}">
                <span class="submission-file-timeline__dot" aria-hidden="true"></span>
                <time class="submission-file-timeline__date" datetime="{{ $timelineEvent->occurred_at->toIso8601String() }}">{{ $timelineEvent->occurred_at->format('M j, Y g:i A') }}</time>
                <h6 class="submission-file-timeline__title">{{ $isRevisionEvent ? 'Revision requested' : 'File uploaded' }}</h6>
                <p class="submission-file-timeline__file">{{ $fileSlots[$entry['file_type']] ?? $entry['file_type'] }} · V{{ $timelineEvent->version }}</p>
                <p class="submission-file-timeline__name">{{ $timelineEvent->original_name }}</p>
                @if ($isRevisionEvent && $timelineEvent->revision_notes)
                <p class="submission-file-timeline__notes">{{ $timelineEvent->revision_notes }}</p>
                @endif
              </li>
            @if ($loop->last)</ol>@endif
            @empty
            <div class="submission-review-empty">
              <i class="icon-base ri ri-time-line" aria-hidden="true"></i>
              <span>No file uploads or revision requests have been recorded yet.</span>
            </div>
            @endforelse
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
  </section>
  @endforeach
  @endif
  @else
  <div class="card">
    <div class="card-header border-0 pb-0">
      <h4 class="mb-0">My Submission</h4>
      <hr class="page-content__divider">
    </div>
    <div class="card-body text-center py-10">
      <div class="avatar avatar-xl mx-auto mb-4">
        <div class="avatar-initial bg-label-primary rounded"><i class="icon-base ri ri-file-list-3-line icon-32px"></i></div>
      </div>
      <h5>No submissions yet</h5>
      <p class="text-body-secondary mb-4">Your submitted disclosure forms will appear here.</p>
      <a class="btn btn-primary" href="{{ route('dashboard.innovation-disclosure.create') }}"><i class="icon-base ri ri-add-line me-1"></i>Create a disclosure</a>
    </div>
  </div>
  @endif

  @if (! $detailView && $deletedDisclosures->count())
  <div class="card mb-6">
    <div class="card-header">
      <h5 class="card-title mb-1">Deleted submissions</h5>
      <p class="mb-0 text-body-secondary">Deleted disclosures are retained in the database with their files.</p>
    </div>
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Technology</th>
            <th>Type</th>
            <th>Submitted</th>
            <th>Deleted</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($deletedDisclosures as $disclosure)
          <tr>
            <td class="text-wrap fw-medium">{{ $disclosure->technology_title }}</td>
            <td class="submission-table__single-line" title="{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}">{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}</td>
            <td class="submission-table__single-line">{{ $disclosure->created_at->format('M j, Y') }}</td>
            <td class="submission-table__single-line">{{ $disclosure->deleted_at->format('M j, Y') }}</td>
            <td><span class="badge bg-label-danger">Deleted</span></td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @if ($deletedDisclosures->hasPages())
  <div class="d-flex justify-content-center mb-6">{{ $deletedDisclosures->links() }}</div>
  @endif
  @endif

  @if (! $detailView && $disclosures->hasPages())
  <div class="d-flex justify-content-center">{{ $disclosures->links() }}</div>
  @endif
</section>
@endsection

@section('page-script')
@if (session('toast_type') === 'success' && session('toast_message') === 'Innovation disclosure submitted successfully.')
<script>
  sessionStorage.removeItem('ipms-idf-submit-recovery-user-{{ auth()->id() }}');
  const submittedIdfDraftRequest = indexedDB.open('ipms-idf-drafts', 1);
  submittedIdfDraftRequest.onupgradeneeded = () => {
    if (!submittedIdfDraftRequest.result.objectStoreNames.contains('drafts')) {
      submittedIdfDraftRequest.result.createObjectStore('drafts', { keyPath: 'id' });
    }
  };
  submittedIdfDraftRequest.onsuccess = () => {
    const draftTransaction = submittedIdfDraftRequest.result.transaction('drafts', 'readwrite');
    draftTransaction.objectStore('drafts').delete('idf-draft-user-{{ auth()->id() }}');
  };
  submittedIdfDraftRequest.onerror = () => console.error('Could not clear the saved IDF draft after submission.');
</script>
@endif
<script src="{{ asset('user/js/innovation-disclosure.js') }}" defer></script>
<script src="{{ asset('user/js/user-submission-status.js') }}" defer></script>
@endsection