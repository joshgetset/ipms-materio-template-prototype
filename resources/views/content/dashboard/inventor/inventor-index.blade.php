@extends('layouts/contentNavbarLayout')
@php
$detailView = $detailView ?? false;
$technologyFileLabels = [
  'abstract_summary' => 'Abstract / Summary',
  'technical_field' => 'Technical / Creative Field',
  'background_summary' => 'Background / Problem',
  'invention_summary' => 'Invention Summary',
  'detailed_description' => 'Detailed Description',
  'examples_results' => 'Examples / Experimental Results / Validation',
  'sample_photo' => 'Sample picture',
  'technical_drawing' => 'Technical drawing',
];
@endphp
@section('title', $detailView ? 'Submission Review' : 'My Submission')

@section('page-style')
<link rel="stylesheet" href="{{ asset('user/css/innovation-disclosure.css') }}?v={{ filemtime(public_path('user/css/innovation-disclosure.css')) }}">
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
          'endorsed' => 'Approved',
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
            <td title="{{ $disclosure->technology_type_label }}"><span class="submission-table__type">{{ $disclosure->technology_type_label }}</span></td>
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
  $canEditRevisionDetails = false;
  $canSubmitFileRevisions = in_array($disclosure->status, ['under_review', 'returned', 'revised_submission'], true);
  $canSubmitTechnologyFileRevisions = in_array($disclosure->status, ['submitted', 'under_review', 'returned', 'revised_submission'], true);
  $fileSlots = [
  'drawings' => 'Drawings',
  'methodology' => 'Methodology',
  'assistance_form' => 'Assistance form',
  'deed_assignment' => 'Deed of Assignment',
  'sample_photo' => 'Sample photo of the product',
  'additional_signed_sheet' => 'Additional signed creator sheet',
  ];
  $technologyCategoryLabel = $disclosure->technology_type_label;
  $primaryInventor = $disclosure->inventors->firstWhere('is_primary', true);
  $leadCreatorName = $primaryInventor
    ? ($primaryInventor->full_name ?: trim(collect([
      $primaryInventor->first_name,
      $primaryInventor->middle_initial,
      $primaryInventor->last_name,
      $primaryInventor->suffix,
    ])->filter()->implode(' ')))
    : $disclosure->lead_creator_name;
  $timelineCurrentStep = match ($disclosure->status) {
    'endorsed' => 3,
    'filed' => 4,
    default => 1,
  };
  $reviewStepLabel = match ($disclosure->status) {
    'returned' => 'Revision Requested',
    'revised_submission' => 'Revision submitted',
    default => 'Review',
  };
  $timelineSteps = [
    ['key' => 'idf-submitted', 'label' => 'IDF Submitted', 'date' => $disclosure->created_at, 'icon' => 'ri-file-list-3-line'],
    ['key' => 'review', 'label' => $reviewStepLabel, 'date' => in_array($disclosure->status, ['under_review', 'returned', 'revised_submission'], true) ? $disclosure->updated_at : null, 'icon' => 'ri-search-eye-line'],
    ['key' => 'approved', 'label' => 'IDF Approved', 'date' => $disclosure->endorsed_at, 'icon' => 'ri-checkbox-circle-line'],
    ['key' => 'prior-art-search', 'label' => 'Submitted for Prior Art Search', 'date' => $disclosure->endorsed_at, 'icon' => 'ri-search-line'],
    ['key' => 'ipophl-applied', 'label' => 'IDF Applied to IPOPHL', 'date' => $disclosure->status === 'filed' ? $disclosure->updated_at : null, 'icon' => 'ri-government-line'],
  ];
  $timelineNarratives = [
    [
      'title' => 'IDF submitted',
      'message' => 'Your disclosure was submitted to the IP Office on ' . $disclosure->created_at->format('M j, Y') . '.',
      'icon' => 'ri-file-list-3-line',
      'color' => 'success',
      'date' => $disclosure->created_at,
    ],
    [
      'title' => match ($disclosure->status) {
        'returned' => 'Revision requested.',
        'revised_submission' => 'Revision submitted.',
        'endorsed', 'filed' => 'Review complete.',
        'submitted' => 'Submission received.',
        default => 'Under review.',
      },
      'message' => match ($disclosure->status) {
        'returned' => 'Please review the requested changes and update the flagged files in your submission. ' . ($disclosure->review_notes ?: ''),
        'revised_submission' => 'Your revised files have been submitted and are being reviewed by the IP Office.',
        'endorsed', 'filed' => 'The IP Office has completed its review of your disclosure.',
        'submitted' => 'Your submission has been received and is waiting for review.',
        default => "The IP Office is currently evaluating your documents. You'll be notified by email once a decision is made — no action is needed from you right now.",
      },
      'icon' => match ($disclosure->status) {
        'returned' => 'ri-alert-line',
        'endorsed', 'filed' => 'ri-checkbox-circle-line',
        default => 'ri-information-line',
      },
      'color' => $disclosure->status === 'returned' ? 'warning' : (in_array($disclosure->status, ['endorsed', 'filed'], true) ? 'success' : 'primary'),
      'date' => in_array($disclosure->status, ['under_review', 'returned', 'revised_submission'], true) ? $disclosure->updated_at : null,
    ],
    [
      'title' => 'IDF approved',
      'message' => $disclosure->endorsed_at
        ? 'Your disclosure was approved by the IP Office on ' . $disclosure->endorsed_at->format('M j, Y') . '.'
        : 'This milestone will be updated when the IP Office approves your disclosure.',
      'icon' => 'ri-checkbox-circle-line',
      'color' => $disclosure->endorsed_at ? 'success' : 'primary',
      'date' => $disclosure->endorsed_at,
    ],
    [
      'title' => 'Submitted for prior art search',
      'message' => $disclosure->endorsed_at
        ? 'Your approved disclosure was submitted for prior art search on ' . $disclosure->endorsed_at->format('M j, Y') . '.'
        : 'This milestone will be updated after the IP Office approves your disclosure.',
      'icon' => 'ri-search-line',
      'color' => $disclosure->endorsed_at ? 'success' : 'primary',
      'date' => $disclosure->endorsed_at,
    ],
    [
      'title' => 'IPOPHL filing',
      'message' => $disclosure->status === 'filed'
        ? 'Your approved disclosure has been filed with the Intellectual Property Office of the Philippines.'
        : 'This milestone will be updated once your approved disclosure is filed with IPOPHL.',
      'icon' => 'ri-government-line',
      'color' => $disclosure->status === 'filed' ? 'success' : 'primary',
      'date' => $disclosure->status === 'filed' ? $disclosure->updated_at : null,
    ],
  ];
  @endphp
  <section class="submission-details-page submission-review-page" aria-labelledby="submission-review-title" data-submission-status-url="{{ route('dashboard.submissions.status-updates') }}">
    <header class="card submission-review-hero">
      <div class="card-body">
        <div class="submission-review-hero__layout">
          <div class="min-w-0">
              <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <span class="badge bg-label-{{ match ($disclosure->status) { 'endorsed', 'filed' => 'success', 'returned' => 'danger', 'under_review', 'revised_submission' => 'warning', default => 'primary' } }}" data-disclosure-status="{{ $disclosure->id }}">{{ match ($disclosure->status) { 'submitted' => 'For Review', 'under_review' => 'Under review', 'revised_submission' => 'Revised Submission', 'returned' => 'Revision requested', 'endorsed' => 'Approved', default => str_replace('_', ' ', ucfirst($disclosure->status)) } }}</span>
              </div>
              <h3 class="mb-2 fw-semibold submission-review-title" id="submission-review-title">{{ $disclosure->research_title }}</h3>
              <div class="submission-review-hero__subtitle" aria-label="Campus and department">
                @if (filled($disclosure->campus))
                <span class="submission-review-hero__subtitle-item"><i class="icon-base ri ri-map-pin-2-line" aria-hidden="true"></i>{{ $disclosure->campus }}</span>
                @endif
                @if (filled($disclosure->college_department))
                <span class="submission-review-hero__subtitle-item"><i class="icon-base ri ri-building-4-line" aria-hidden="true"></i>{{ $disclosure->college_department }}</span>
                @endif
                @if (blank($disclosure->campus) && blank($disclosure->college_department))
                <span class="submission-review-hero__subtitle-item">Campus and department not provided</span>
                @endif
            </div>
          </div>
          <div class="submission-review-hero__actions">
            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#submission-detailed-timeline-{{ $disclosure->id }}">
              <i class="icon-base ri ri-time-line me-1" aria-hidden="true"></i>View history
            </button>
          </div>
        </div>
      </div>
    </header>

    <section class="card submission-review-tracker" aria-label="Submission progress">
      <div class="card-body">
        <div class="submission-review-hero__timeline">
            <ol class="submission-review-stepper" aria-label="Submission timeline">
              @foreach ($timelineSteps as $index => $step)
              @php
              $stepState = $index < $timelineCurrentStep ? 'is-complete' : ($index === $timelineCurrentStep ? 'is-current' : 'is-upcoming');
              $stepDate = $step['date'];
              @endphp
              <li class="submission-review-stepper__step {{ $stepState }} {{ $index === $timelineCurrentStep ? 'is-selected' : '' }}" data-timeline-step="{{ $step['key'] }}">
                <button class="submission-review-stepper__indicator" type="button" data-timeline-narrative-trigger="{{ $index }}" aria-label="Show {{ $step['label'] }} details" aria-controls="submission-stage-narrative-{{ $disclosure->id }}" aria-pressed="{{ $index === $timelineCurrentStep ? 'true' : 'false' }}">
                  <i class="icon-base ri {{ $index < $timelineCurrentStep ? 'ri-check-line' : $step['icon'] }}" aria-hidden="true"></i>
                </button>
                <span class="submission-review-stepper__label">{{ $step['label'] }}</span>
                @if ($stepDate)
                <time class="submission-review-stepper__date is-complete" datetime="{{ $stepDate->toDateString() }}">{{ $stepDate->format('M j, Y') }}</time>
                @else
                <span class="submission-review-stepper__date {{ $index === $timelineCurrentStep ? 'is-current' : ($index < $timelineCurrentStep ? 'is-complete' : 'is-upcoming') }}">{{ $index === $timelineCurrentStep ? 'In progress' : ($index < $timelineCurrentStep ? 'Not recorded' : 'Pending') }}</span>
                @endif
              </li>
              @endforeach
            </ol>
          <div class="submission-review-stage-narratives" id="submission-stage-narrative-{{ $disclosure->id }}" aria-live="polite">
            @foreach ($timelineNarratives as $index => $narrative)
            <div class="submission-review-stage-banner submission-review-stage-banner--{{ $narrative['color'] }}" data-timeline-narrative="{{ $index }}" role="status" @if ($index !== $timelineCurrentStep) hidden @endif>
              <i class="icon-base ri {{ $narrative['icon'] }}" aria-hidden="true"></i>
              <p class="mb-0"><strong>{{ $narrative['title'] }}</strong> {{ $narrative['message'] }}</p>
              @if ($narrative['date'])
              <time class="submission-review-stage-banner__age" datetime="{{ $narrative['date']->toDateString() }}">{{ $narrative['date']->format('M j, Y') }}</time>
              @else
              <span class="submission-review-stage-banner__age">Pending</span>
              @endif
            </div>
            @endforeach
          </div>
        </div>
      </div>
    </section>

    <section class="submission-review-overview submission-review-section mb-4" id="submission-overview" data-review-section="details" aria-label="Submission overview">
      <div class="alert alert-warning mb-4 {{ $disclosure->review_notes ? '' : 'd-none' }}" data-modal-review-note="{{ $disclosure->id }}">
        <strong>Revision requested:</strong> <span>{{ $disclosure->review_notes }}</span>
      </div>

      <div class="card submission-review-overview-card">
        <div class="card-body p-0">
      <div class="row g-0 submission-review-groups">
        <div class="col-12 col-xl-4">
          <section class="submission-review-group h-100" aria-labelledby="submission-disclosure-group">
            <h5 class="submission-review-group__title" id="submission-disclosure-group">Disclosure and contact</h5>
            <div class="submission-review-group__fields">
              @foreach ([
                'Lead creator' => $leadCreatorName,
                'Email address' => $disclosure->email,
                'Contact number' => $disclosure->mobile_no,
                'Technology category' => $disclosure->technology_type_label,
                'Relationship to SLSU' => str_replace('_', ' ', ucfirst($disclosure->university_relationship)),
                'Other relationship' => $disclosure->other_relationship,
              ] as $label => $value)
              @if (filled($value))
              <div class="submission-review-meta"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
              @endif
              @endforeach
              @if ($disclosure->file_drive_link)
              <div class="submission-review-meta">
                <span>External file / Drive link</span>
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
              ] as $label => $value)
              @if (filled($value))
              <div class="submission-review-meta"><span>{{ $label }}</span><strong>{{ $value }}</strong></div>
              @endif
              @endforeach
            </div>
          </section>
        </div>
        <div class="col-12 col-xl-4">
          <section class="submission-review-group submission-review-important-dates" aria-labelledby="submission-dates-group">
            <h5 class="submission-review-group__title" id="submission-dates-group">Important dates</h5>
            <div class="submission-review-group__fields">
              <div class="submission-review-meta" data-review-date="creation"><span>Date of creation</span><strong>{{ $disclosure->date_of_creation?->format('M j, Y') ?: 'Not provided' }}</strong></div>
              <div class="submission-review-meta" data-review-date="approval"><span>Research approval date</span><strong>{{ $disclosure->research_approval_date?->format('M j, Y') ?: 'Not provided' }}</strong></div>
              <div class="submission-review-meta" data-review-date="disclosure"><span>Disclosure date</span><strong>{{ $disclosure->disclosure_date?->format('M j, Y') ?: 'Not provided' }}</strong></div>
            </div>
          </section>
        </div>
      </div>
        </div>
      </div>
    </section>

    <article class="card mb-4 submission-review-section" id="submission-creators" data-review-section="inventors">
      <header class="card-header">
        <h4 class="card-title mb-0 fw-semibold">Creators and contributions</h4>
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
        <h4 class="card-title mb-0 fw-semibold">Costing of materials and resources</h4>
      </header>
      <div class="card-body">
      @if ($disclosure->costItems->isNotEmpty())
      @if ($canEditRevisionDetails)
      <form method="POST" enctype="multipart/form-data" action="{{ route('dashboard.submissions.revision-details', $disclosure) }}" class="submission-revision-details-form" novalidate>
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

    <article class="card mb-4 submission-review-section" id="submission-technologies" data-review-section="technologies">
      <header class="card-header">
        <h4 class="card-title mb-0 fw-semibold">Submitted technologies</h4>
      </header>
      <div class="card-body">
      @if ($disclosure->technologies->isNotEmpty())
      @if ($canSubmitTechnologyFileRevisions)
      @foreach ($disclosure->technologies as $technology)
        @foreach ($technology->files->where('review_status', 'revision_requested') as $technologyFile)
        <form
          id="technology-file-revision-form-{{ $technologyFile->id }}"
          method="POST"
          enctype="multipart/form-data"
          action="{{ route('dashboard.submissions.technology-files.revision', [$disclosure, $technology, $technologyFile]) }}">
          @csrf
          <input
            class="d-none"
            id="technology-file-revision-{{ $technologyFile->id }}"
            name="file"
            type="file"
            accept="application/pdf,.pdf"
            aria-label="Select a replacement PDF for {{ $technologyFile->original_name }}">
          <input
            id="technology-file-note-{{ $technologyFile->id }}"
            name="submission_note"
            type="hidden">
        </form>
        @endforeach
      @endforeach
      @endif
      @if ($canEditRevisionDetails)
      <form method="POST" enctype="multipart/form-data" id="technology-revision-details-form-{{ $disclosure->id }}" action="{{ route('dashboard.submissions.revision-details', $disclosure) }}" class="submission-revision-details-form" novalidate>
        @csrf
        <input type="hidden" name="section" value="technologies">
      @endif
      <section aria-labelledby="submitted-technologies-heading-{{ $disclosure->id }}">
        <h5 class="visually-hidden" id="submitted-technologies-heading-{{ $disclosure->id }}">Submitted technologies</h5>
        @if ($canEditRevisionDetails)
        <div class="inventor-technology-grid">
        @foreach ($disclosure->technologies->take(3) as $technology)
        <section class="inventor-technology-grid__item">
        <div class="inventor-technology-grid__editor">
          <div class="flex-grow-1">
            <label class="form-label small mb-1" for="technology-title-{{ $technology->id }}">Technology title</label>
            <input class="form-control" id="technology-title-{{ $technology->id }}" name="technologies[{{ $technology->id }}][technology_title]" value="{{ old("technologies.{$technology->id}.technology_title", $technology->technology_title) }}" maxlength="500" required>
          </div>
          <span class="text-body-secondary small">Technology {{ $loop->iteration }} of {{ $loop->count }} · {{ $technologyCategoryLabel }}</span>
        </div>
        @include('content.dashboard._inventor-technology-card', [
          'technology' => $technology,
          'technologyFileLabels' => $technologyFileLabels,
          'technologyCategoryLabel' => $technologyCategoryLabel,
          'disclosure' => $disclosure,
          'canEditRevisionDetails' => $canEditRevisionDetails,
          'canSubmitFileRevisions' => $canSubmitTechnologyFileRevisions,
        ])
        </section>
        @endforeach
        </div>
        @else
        <div class="inventor-technology-grid">
          @foreach ($disclosure->technologies->take(3) as $technology)
          <section class="inventor-technology-grid__item">
            @include('content.dashboard._inventor-technology-card', [
              'technology' => $technology,
              'technologyFileLabels' => $technologyFileLabels,
              'technologyCategoryLabel' => $technologyCategoryLabel,
              'disclosure' => $disclosure,
              'canEditRevisionDetails' => false,
              'canSubmitFileRevisions' => $canSubmitTechnologyFileRevisions,
            ])
          </section>
          @endforeach
          </div>
          @endif
      </section>
      @if ($canEditRevisionDetails)
        <div class="d-flex justify-content-end">
          <button class="btn btn-primary" type="submit"><i class="icon-base ri ri-save-line me-1" aria-hidden="true"></i>Save technical / creative changes</button>
        </div>
      </form>
      @endif
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
<script src="{{ asset('user/js/innovation-disclosure.js') }}?v={{ filemtime(public_path('user/js/innovation-disclosure.js')) }}" defer></script>
<script src="{{ asset('user/js/review-table-pagination.js') }}" defer></script>
@endsection