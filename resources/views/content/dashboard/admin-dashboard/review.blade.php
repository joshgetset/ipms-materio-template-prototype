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

<section class="page-content">
  <article class="card mb-6">
    <header class="card-header border-0 pb-0">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="min-w-0">
          <h4 class="mb-1">Review Submission</h4>
          <p class="small text-body-secondary mb-0">{{ $disclosure->technology_title }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('admin-dashboard.index', [$pageParameter => $page]) }}"><i class="icon-base ri ri-arrow-left-line me-1"></i>Back to submissions</a>
      </div>
      <hr class="page-content__divider">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <span class="badge bg-label-{{ $statusColor }}">{{ $statusLabel }}</span>
        <span class="small text-body-secondary">Submitted by {{ $disclosure->user->name }} &middot; {{ $disclosure->user->email }}</span>
      </div>
    </header>

    <div class="card-body">
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

      <div class="accordion accordion-flush mb-5" id="admin-narratives-{{ $disclosure->id }}">
        @foreach ([
        'background' => ['Background summary', $disclosure->background_summary],
        'description' => ['Description', $disclosure->detailed_description],
        'drawings' => ['Drawing description', $disclosure->drawings_description],
        ] as $key => [$label, $text])
        @if ($text)
        <div class="accordion-item">
          <h2 class="accordion-header" id="admin-{{ $key }}-heading-{{ $disclosure->id }}">
            <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#admin-{{ $key }}-panel-{{ $disclosure->id }}" aria-expanded="false" aria-controls="admin-{{ $key }}-panel-{{ $disclosure->id }}">{{ $label }}</button>
          </h2>
          <div id="admin-{{ $key }}-panel-{{ $disclosure->id }}" class="accordion-collapse collapse" aria-labelledby="admin-{{ $key }}-heading-{{ $disclosure->id }}" data-bs-parent="#admin-narratives-{{ $disclosure->id }}">
            <div class="accordion-body">
              <p class="submission-narrative__text mb-0">{{ $text }}</p>
            </div>
          </div>
        </div>
        @endif
        @endforeach
      </div>

      <h5>Inventors</h5>
      <div class="table-responsive mb-5">
        <table class="table table-sm mb-0">
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

      <h5>Supporting files</h5>
      <div class="row g-3 mb-5">
        @forelse ($disclosure->attachments as $attachment)
        <div class="col-md-4">
          <div class="border rounded p-3 h-100">
            <span class="d-block fw-medium">{{ ucfirst(str_replace('_', ' ', $attachment->type)) }}</span>
            <span class="small text-body-secondary text-break d-block mb-3">{{ $attachment->original_name }} @if ($attachment->version > 1)<span class="badge bg-label-info ms-1">V{{ $attachment->version }}</span>@endif &middot; {{ number_format($attachment->size_bytes / 1048576, 2) }} MB</span>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <a class="btn btn-sm btn-outline-primary" href="{{ route('dashboard.submissions.attachments.download', [$disclosure, $attachment]) }}"><i class="icon-base ri ri-download-line me-1"></i>Download</a>
              @if ($attachment->needs_revision)
              <span class="badge bg-label-warning align-self-center">Revision requested</span>
              @endif
            </div>
            <form class="admin-attachment-review-form" method="POST" action="{{ route('admin-dashboard.attachments.review', $attachment) }}" data-confirm-title="Save file review?" data-confirm-text="The submitter will see this revision request on their submissions page.">
              @csrf
              <input type="hidden" name="queue" value="{{ $queue }}">
              <input type="hidden" name="page" value="{{ $page }}">
              <input type="hidden" name="needs_revision" value="0">
              <div class="form-check mb-3">
                <input class="form-check-input admin-file-revision-toggle" id="attachment-revision-{{ $attachment->id }}" name="needs_revision" type="checkbox" value="1" @checked($attachment->needs_revision)>
                <label class="form-check-label" for="attachment-revision-{{ $attachment->id }}">Needs revision</label>
              </div>
              <div class="admin-file-revision-notes {{ $attachment->needs_revision ? '' : 'd-none' }}">
                <label class="form-label small" for="attachment-notes-{{ $attachment->id }}">What needs to change?</label>
                <textarea class="form-control form-control-sm mb-3" id="attachment-notes-{{ $attachment->id }}" name="revision_notes" rows="2" maxlength="2000" @required($attachment->needs_revision)>{{ $attachment->revision_notes }}</textarea>
              </div>
              <button class="btn btn-sm btn-primary" type="submit">Save file review</button>
            </form>
          </div>
        </div>
        @empty
        <div class="col-12 text-body-secondary">No supporting files attached.</div>
        @endforelse
      </div>

      <form class="admin-review-form" method="POST" action="{{ route('admin-dashboard.review', $disclosure) }}">
        @csrf
        <input type="hidden" name="queue" value="{{ $queue }}">
        <input type="hidden" name="page" value="{{ $page }}">
        <div class="row g-4 align-items-end">
          <div class="col-md-4">
            <label class="form-label" for="review-status-{{ $disclosure->id }}">Review decision</label>
            <select class="form-select admin-review-status" id="review-status-{{ $disclosure->id }}" name="status" required>
              <option value="">Choose a decision</option>
              <option value="under_review" @selected($disclosure->status === 'under_review')>Under review</option>
              <option value="returned" @selected($disclosure->status === 'returned')>Request revision</option>
              <option value="endorsed" @selected($disclosure->status === 'endorsed')>Endorse</option>
            </select>
          </div>
          <div class="col-md-8 admin-review-notes-wrap {{ $disclosure->status === 'returned' ? '' : 'd-none' }}">
            <label class="form-label" for="review-notes-{{ $disclosure->id }}">Revision notes</label>
            <textarea class="form-control admin-review-notes" id="review-notes-{{ $disclosure->id }}" name="review_notes" rows="2" maxlength="3000" @required($disclosure->status === 'returned')>{{ $disclosure->review_notes }}</textarea>
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
@vite(['resources/assets/js/admin-dashboard.js'])
@endsection