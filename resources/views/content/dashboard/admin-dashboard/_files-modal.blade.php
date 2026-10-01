@php
$filesModalId = 'submission-files-' . str_replace('-', '', $queue) . '-' . $disclosure->id;
@endphp
<div class="modal fade" id="{{ $filesModalId }}" tabindex="-1" aria-labelledby="{{ $filesModalId }}-title" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <div>
          <h5 class="modal-title text-break" id="{{ $filesModalId }}-title">Submitted files</h5>
          <p class="small text-body-secondary mb-0">{{ $disclosure->technology_title }}</p>
        </div>
        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
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
                <input type="hidden" name="page" value="{{ $disclosuresPage }}">
                <input type="hidden" name="needs_revision" value="0">
                <div class="form-check mb-3">
                  <input class="form-check-input admin-file-revision-toggle" id="attachment-revision-{{ $queue }}-{{ $attachment->id }}" name="needs_revision" type="checkbox" value="1" @checked($attachment->needs_revision)>
                  <label class="form-check-label" for="attachment-revision-{{ $queue }}-{{ $attachment->id }}">Needs revision</label>
                </div>
                <div class="admin-file-revision-notes {{ $attachment->needs_revision ? '' : 'd-none' }}">
                  <label class="form-label small" for="attachment-notes-{{ $queue }}-{{ $attachment->id }}">What needs to change?</label>
                  <textarea class="form-control form-control-sm mb-3" id="attachment-notes-{{ $queue }}-{{ $attachment->id }}" name="revision_notes" rows="2" maxlength="2000" @required($attachment->needs_revision)>{{ $attachment->revision_notes }}</textarea>
                </div>
                <button class="btn btn-sm btn-primary" type="submit">Save file review</button>
              </form>
            </div>
          </div>
          @empty
          <div class="col-12 text-center text-body-secondary py-6">No supporting files were submitted.</div>
          @endforelse
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>