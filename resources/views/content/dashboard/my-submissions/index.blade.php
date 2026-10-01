@extends('layouts/contentNavbarLayout')
@php
$detailView = $detailView ?? false;
@endphp
@section('title', $detailView ? 'Submission Review' : 'My Submission')

@section('content')
<section class="page-content" @unless ($detailView) data-submission-status-url="{{ route('dashboard.submissions.status-updates') }}" @endunless>

  @if ($errors->any())
  <div class="alert alert-danger" role="alert">
    <ul class="mb-0 ps-4">
      @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
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
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead>
          <tr>
            <th>Technology</th>
            <th>Type</th>
            <th>Status</th>
            <th>Submitted</th>
            <th class="text-end">Details</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($disclosures as $disclosure)
          @php
          $statusColor = match ($disclosure->status) {
          'endorsed', 'filed' => 'success',
          'returned' => 'danger',
          'under_review' => 'warning',
          default => 'primary',
          };
          $statusLabel = match ($disclosure->status) {
          'under_review' => 'Under review',
          'returned' => 'Revision requested',
          default => str_replace('_', ' ', ucfirst($disclosure->status)),
          };
          @endphp
          <tr>
            <td class="text-wrap fw-medium">{{ $disclosure->technology_title }}</td>
            <td>{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}</td>
            <td>
              <span class="badge bg-label-{{ $statusColor }}" data-disclosure-status="{{ $disclosure->id }}">{{ $statusLabel }}</span>
              <p class="small text-body-secondary mt-1 mb-0 {{ $disclosure->review_notes ? '' : 'd-none' }}" data-disclosure-review-note="{{ $disclosure->id }}">{{ $disclosure->review_notes }}</p>
            </td>
            <td>{{ $disclosure->created_at->format('M j, Y') }}</td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="{{ route('dashboard.submissions.show', $disclosure) }}" aria-label="View {{ $disclosure->technology_title }}">
                <i class="icon-base ri ri-eye-line me-1"></i>View
              </a>
            </td>
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
  $statusColor = match ($disclosure->status) {
  'endorsed', 'filed' => 'success',
  'returned' => 'danger',
  'under_review' => 'warning',
  default => 'primary',
  };
  $statusLabel = match ($disclosure->status) {
  'under_review' => 'Under review',
  'returned' => 'Revision requested',
  default => str_replace('_', ' ', ucfirst($disclosure->status)),
  };
  $fileSlots = [
  'drawings' => 'Drawings',
  'methodology' => 'Methodology',
  'assistance_form' => 'Assistance form',
  ];
  @endphp
  <section class="card mb-6 submission-details-page" aria-labelledby="submission-review-title" data-submission-status-url="{{ route('dashboard.submissions.status-updates') }}">
    <div class="card-header pb-0">
      <h4 class="card-title mb-0" id="submission-review-title">Submission Review</h4>
      <hr class="page-content__divider">
    </div>
    <div class="card-body">
      <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
        <h5 class="mb-0 text-break">{{ $disclosure->technology_title }}</h5>
        <span class="badge bg-label-{{ $statusColor }}" data-disclosure-status="{{ $disclosure->id }}">{{ $statusLabel }}</span>
      </div>
      <div class="alert alert-warning mb-4 {{ $disclosure->review_notes ? '' : 'd-none' }}" data-modal-review-note="{{ $disclosure->id }}">
        <strong>Revision requested:</strong> <span>{{ $disclosure->review_notes }}</span>
      </div>
      <dl class="row g-4 mb-5 submission-metadata">
        <div class="col-md-6">
          <dt class="submission-metadata__label">Contact email</dt>
          <dd class="submission-metadata__value">{{ $disclosure->email }}</dd>
        </div>
        <div class="col-md-6">
          <dt class="submission-metadata__label">Mobile number</dt>
          <dd class="submission-metadata__value">{{ $disclosure->mobile_no }}</dd>
        </div>
        <div class="col-md-6">
          <dt class="submission-metadata__label">Technology type</dt>
          <dd class="submission-metadata__value">{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}</dd>
        </div>
        <div class="col-md-6">
          <dt class="submission-metadata__label">University relationship</dt>
          <dd class="submission-metadata__value">{{ str_replace('_', ' ', ucfirst($disclosure->university_relationship)) }}</dd>
        </div>
        <div class="col-md-6">
          <dt class="submission-metadata__label">Funding source</dt>
          <dd class="submission-metadata__value">{{ str_replace('_', ' ', ucfirst($disclosure->funding_source)) }}</dd>
        </div>
        <div class="col-md-6">
          <dt class="submission-metadata__label">Ownership declaration</dt>
          <dd class="submission-metadata__value">{{ str_replace('_', ' ', ucfirst($disclosure->ownership_declaration)) }}</dd>
        </div>
        <div class="col-md-6">
          <dt class="submission-metadata__label">Substantial SLSU support</dt>
          <dd class="submission-metadata__value">{{ $disclosure->has_substantial_support === null ? 'Not recorded' : ($disclosure->has_substantial_support ? 'Yes' : 'No') }}</dd>
        </div>
        <div class="col-md-6">
          <dt class="submission-metadata__label">Ownership consent</dt>
          <dd class="submission-metadata__value">{{ $disclosure->ownership_consent ? 'Confirmed' : 'Not recorded' }}</dd>
        </div>
        <div class="col-md-6">
          <dt class="submission-metadata__label">Submitted</dt>
          <dd class="submission-metadata__value">{{ $disclosure->created_at->format('M j, Y') }}</dd>
        </div>
        @if ($disclosure->research_title)
        <div class="col-md-8">
          <dt class="submission-metadata__label">Research title</dt>
          <dd class="submission-metadata__value">{{ $disclosure->research_title }}</dd>
        </div>
        <div class="col-md-4">
          <dt class="submission-metadata__label">Research approval date</dt>
          <dd class="submission-metadata__value">{{ $disclosure->research_approval_date?->format('M j, Y') ?? '—' }}</dd>
        </div>
        @endif
      </dl>

      <div class="accordion accordion-flush mb-5 submission-narratives" id="submission-narratives-{{ $disclosure->id }}">
        <div class="accordion-item">
          <h2 class="accordion-header" id="background-heading-{{ $disclosure->id }}">
            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#background-panel-{{ $disclosure->id }}" aria-expanded="false" aria-controls="background-panel-{{ $disclosure->id }}">Background summary</button>
          </h2>
          <div id="background-panel-{{ $disclosure->id }}" class="accordion-collapse collapse" aria-labelledby="background-heading-{{ $disclosure->id }}" data-bs-parent="#submission-narratives-{{ $disclosure->id }}">
            <div class="accordion-body">
              <p class="submission-narrative__text mb-0">{{ $disclosure->background_summary }}</p>
            </div>
          </div>
        </div>
        <div class="accordion-item">
          <h2 class="accordion-header" id="description-heading-{{ $disclosure->id }}">
            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#description-panel-{{ $disclosure->id }}" aria-expanded="false" aria-controls="description-panel-{{ $disclosure->id }}">Description</button>
          </h2>
          <div id="description-panel-{{ $disclosure->id }}" class="accordion-collapse collapse" aria-labelledby="description-heading-{{ $disclosure->id }}" data-bs-parent="#submission-narratives-{{ $disclosure->id }}">
            <div class="accordion-body">
              <p class="submission-narrative__text mb-0">{{ $disclosure->detailed_description }}</p>
            </div>
          </div>
        </div>
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

      <div class="mb-5">
        <h6>Inventors</h6>
        <div class="table-responsive">
          <table class="table table-sm mb-0">
            <thead>
              <tr>
                <th>Name</th>
                <th>Citizenship</th>
                <th>Affiliation</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($disclosure->inventors as $inventor)
              <tr>
                <td>{{ trim(collect([$inventor->first_name, $inventor->middle_initial, $inventor->last_name, $inventor->suffix])->filter()->implode(' ')) }} @if ($inventor->is_primary)<span class="badge bg-label-success ms-1">Primary</span>@endif</td>
                <td>{{ $inventor->country_of_citizenship }}</td>
                <td>{{ str_replace('_', ' ', ucfirst($inventor->affiliation)) }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div>
        <h6 class="mb-3">Supporting files and revisions</h6>
        <div class="row g-4">
          @foreach ($fileSlots as $type => $label)
          @php
          $attachment = $attachmentsByType->get($type);
          @endphp
          <div class="col-lg-4">
            <div class="border rounded p-4 h-100">
              <h6 class="mb-2">{{ $label }}</h6>
              @if ($attachment)
              <p class="small text-body-secondary mb-2 text-break">{{ $attachment->original_name }} @if ($attachment->version > 1)<span class="badge bg-label-info ms-1">V{{ $attachment->version }}</span>@endif<br>{{ number_format($attachment->size_bytes / 1048576, 2) }} MB · Updated {{ $attachment->updated_at->format('M j, Y') }}</p>
              <div class="mb-3 {{ $attachment->needs_revision ? '' : 'd-none' }}" data-file-revision-state="{{ $attachment->id }}">
                <span class="badge bg-label-warning mb-2">Revision requested</span>
                <p class="small text-body mb-0" data-file-revision-notes="{{ $attachment->id }}">{{ $attachment->revision_notes }}</p>
              </div>
              <a class="btn btn-sm btn-outline-primary mb-3" href="{{ route('dashboard.submissions.attachments.download', [$disclosure, $attachment]) }}"><i class="icon-base ri ri-download-line me-1"></i>Download</a>
              @else
              <p class="small text-body-secondary mb-3">No file uploaded</p>
              @endif
              <form class="attachment-update-form" method="POST" enctype="multipart/form-data" action="{{ route('dashboard.submissions.attachments.store', $disclosure) }}" data-confirm-title="{{ $attachment ? 'Upload file revision?' : 'Upload this file?' }}" data-confirm-text="{{ $attachment ? 'The current file will be replaced.' : 'This file will be added to the disclosure.' }}">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">
                <label class="form-label small" for="attachment-{{ $disclosure->id }}-{{ $type }}">{{ $attachment ? 'Choose revised file' : 'Choose file' }}</label>
                <input class="form-control form-control-sm mb-3" id="attachment-{{ $disclosure->id }}-{{ $type }}" name="file" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                <button class="btn btn-sm btn-primary" type="submit"><i class="icon-base ri ri-upload-2-line me-1"></i>{{ $attachment ? 'Update file' : 'Upload file' }}</button>
              </form>
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>
    <div class="card-footer d-flex flex-wrap justify-content-between gap-3">
      <form class="disclosure-delete-form me-auto" method="POST" action="{{ route('dashboard.submissions.destroy', $disclosure) }}" data-confirm-title="Are you sure to delete this submission?" data-confirm-text="Its database data and files will be retained.">
        @csrf
        @method('DELETE')
        <button class="btn btn-outline-danger" type="submit"><i class="icon-base ri ri-delete-bin-line me-1"></i>Delete disclosure</button>
      </form>
      <a class="btn btn-outline-secondary" href="{{ route('dashboard.submissions.index') }}">Back to submissions</a>
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
            <td>{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}</td>
            <td>{{ $disclosure->created_at->format('M j, Y') }}</td>
            <td>{{ $disclosure->deleted_at->format('M j, Y') }}</td>
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
@vite(['resources/assets/js/innovation-disclosure.js'])
@vite(['resources/assets/js/user-submission-status.js'])
@endsection