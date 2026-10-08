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
      <table class="table table-hover align-middle mb-0 evaluator-submissions-table">
        <colgroup>
          <col style="width: 8%">
          <col style="width: 16%">
          <col style="width: 32%">
          <col style="width: 13%">
          <col style="width: 12%">
          <col style="width: 9%">
          <col style="width: 10%">
        </colgroup>
        <thead>
          <tr>
            <th class="text-center">Action</th>
            <th>Type</th>
            <th>Title</th>
            <th>Submitter</th>
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
              <a class="btn btn-link btn-sm p-0 text-primary text-decoration-none flat-icon-button" href="{{ route('dashboard.submissions.show', $disclosure) }}" aria-label="Review submission: {{ $disclosure->technology_title }}" title="Review submission">
                <i class="icon-base ri ri-eye-line icon-24px" aria-hidden="true"></i>
              </a>
            </td>
            <td title="{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}"><span class="submission-table__type">{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}</span></td>
            <td>
              <span class="d-block fw-medium evaluator-submissions-table__title" title="{{ $disclosure->technology_title }}">{{ $disclosure->technology_title }}</span>
            </td>
            <td class="submission-table__email" title="{{ auth()->user()->email }}">
              @php
              $submitterEmail = explode('@', auth()->user()->email, 2);
              @endphp
              @if (count($submitterEmail) === 2)
              <span>{{ $submitterEmail[0] }}</span><span>{{ '@' . $submitterEmail[1] }}</span>
              @else
              <span>{{ auth()->user()->email }}</span>
              @endif
            </td>
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
  $fileSlots = [
  'drawings' => 'Drawings',
  'methodology' => 'Methodology',
  'assistance_form' => 'Assistance form',
  'deed_assignment' => 'Deed of Assignment',
  'sample_photo' => 'Sample photo of the product',
  'additional_signed_sheet' => 'Additional signed creator sheet',
  ];
  @endphp
  <section class="submission-details-page" aria-labelledby="submission-review-title" data-submission-status-url="{{ route('dashboard.submissions.status-updates') }}">
    <article class="card mb-6" data-review-section="details">
      <header class="card-header border-0 pb-0">
      <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <h4 class="card-title mb-1 fw-semibold d-flex align-items-center gap-3" id="submission-review-title">
          <span class="avatar avatar-sm">
            <span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-file-list-3-line" aria-hidden="true"></i></span>
          </span>
          <span>Innovation Submission Details</span>
        </h4>
        <span class="badge bg-label-{{ match ($disclosure->status) { 'endorsed', 'filed' => 'success', 'returned' => 'danger', 'under_review', 'revised_submission' => 'warning', default => 'primary' } }}" data-disclosure-status="{{ $disclosure->id }}">{{ match ($disclosure->status) { 'submitted' => 'For Review', 'under_review' => 'Under review', 'revised_submission' => 'Revised Submission', 'returned' => 'Revision requested', default => str_replace('_', ' ', ucfirst($disclosure->status)) } }}</span>
      </div>
      <hr class="page-content__divider">
      </header>
      <div class="card-body">
      <div class="alert alert-warning mb-4 {{ $disclosure->review_notes ? '' : 'd-none' }}" data-modal-review-note="{{ $disclosure->id }}">
        <strong>Revision requested:</strong> <span>{{ $disclosure->review_notes }}</span>
      </div>
      @include('content.dashboard._disclosure-metadata', ['disclosure' => $disclosure])

      @if ($disclosure->technologies->isNotEmpty())
      <section class="mb-5" aria-labelledby="submitted-technologies-heading-{{ $disclosure->id }}">
        <h5 class="card-title mb-3" id="submitted-technologies-heading-{{ $disclosure->id }}">Technical / Creative Descriptions</h5>
        @foreach ($disclosure->technologies as $technology)
        @php
        $technologyFiles = $technology->files->keyBy('narrative_key');
        @endphp
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
            <a href="{{ route('dashboard.submissions.technology-files.preview', [$disclosure, $narrativeFile]) }}" target="_blank" rel="noopener noreferrer">
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
      <div class="accordion accordion-flush mb-5 submission-narratives" id="submission-narratives-{{ $disclosure->id }}">
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
      @endif
      </div>
    </article>

    <article class="card mb-6" data-review-section="inventors">
      <header class="card-header border-0 pb-0">
        <h4 class="card-title mb-1 fw-semibold d-flex align-items-center gap-3">
          <span class="avatar avatar-sm">
            <span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-team-line" aria-hidden="true"></i></span>
          </span>
          <span>Creators and Contributions</span>
        </h4>
        <hr class="page-content__divider">
      </header>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 inventor-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Position / Affiliation</th>
                <th>Contribution</th>
                <th>Share (%)</th>
                <th>Nature</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($disclosure->inventors as $inventor)
              <tr>
                <td>{{ $inventor->full_name ?: trim(collect([$inventor->first_name, $inventor->middle_initial, $inventor->last_name, $inventor->suffix])->filter()->implode(' ')) }} @if ($inventor->is_primary)<span class="badge bg-label-success ms-1">Lead creator</span>@endif</td>
                <td>{{ $inventor->position_affiliation ?? str_replace('_', ' ', ucfirst($inventor->affiliation)) }}</td>
                <td>{{ $inventor->technical_contribution }}</td>
                <td>{{ $inventor->contribution_percent !== null ? number_format((float) $inventor->contribution_percent, 2) . '%' : '' }}</td>
                <td>{{ collect($inventor->contribution_nature ?? [])->map(fn ($nature) => str_replace('_', ' ', ucfirst($nature)))->implode(', ') }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </article>

    @if ($disclosure->costItems->isNotEmpty())
    <article class="card mb-6" data-review-section="costing">
      <header class="card-header border-0 pb-0"><h4 class="card-title mb-1 fw-semibold">Costing of Materials and Resources</h4><hr class="page-content__divider"></header>
      <div class="card-body">
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
          <thead><tr><th>Item / Material</th><th>Description / Specification</th><th>Qty.</th><th>Unit cost</th><th>Total cost</th><th>Purpose / Remarks</th></tr></thead>
          <tbody>
            @foreach ($disclosure->costItems as $costItem)
            <tr><td>{{ $costItem->item }}</td><td>{{ $costItem->description }}</td><td>{{ $costItem->quantity }}</td><td>₱{{ number_format((float) $costItem->unit_cost, 2) }}</td><td>₱{{ number_format((float) $costItem->total_cost, 2) }}</td><td>{{ $costItem->purpose }}</td></tr>
            @endforeach
          </tbody>
          <tfoot><tr><th colspan="4" class="text-end">Total estimated development cost</th><th>₱{{ number_format((float) $disclosure->total_development_cost, 2) }}</th><td></td></tr></tfoot>
        </table></div>
      </div>
    </article>
    @endif

    <article class="card mb-6" data-review-section="files">
      <header class="card-header border-0 pb-0">
        <h4 class="card-title mb-1 fw-semibold d-flex align-items-center gap-3">
          <span class="avatar avatar-sm">
            <span class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri ri-attachment-2" aria-hidden="true"></i></span>
          </span>
          <span>Supporting files and revisions</span>
        </h4>
        <hr class="page-content__divider">
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
                <button class="btn btn-link btn-sm p-0 text-primary text-decoration-none user-submission-file-card__preview flat-icon-button flex-shrink-0" type="button" data-bs-toggle="modal" data-bs-target="#{{ $previewModalId }}" aria-label="Preview {{ $attachment->original_name }}" title="Preview file">
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
                @if ($errors->has('attachments.' . $type))
                <div class="text-danger small mt-1">{{ $errors->first('attachments.' . $type) }}</div>
                @endif
              </div>
              @endif
            </div>
          </div>
          @if ($attachment)
          <div class="modal fade" id="{{ $previewModalId }}" tabindex="-1" aria-labelledby="{{ $previewModalId }}-title" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
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
        {{ $disclosure->status === 'submitted' ? 'Files cannot be changed until the evaluator reviews this submission.' : 'No files have been marked for revision.' }}
      </span>
    </div>
    </article>
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
@vite(['resources/assets/js/innovation-disclosure.js'])
@vite(['resources/assets/js/user-submission-status.js'])
@endsection