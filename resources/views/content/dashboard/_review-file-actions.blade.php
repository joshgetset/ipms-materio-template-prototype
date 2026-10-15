<div class="dropdown review-files-actions">
  <button class="btn btn-icon btn-text-secondary dropdown-toggle hide-arrow" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Actions for {{ $fileName }}">
    <i class="icon-base ri ri-more-2-fill" aria-hidden="true"></i>
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
  @if (! empty($previewModalId))
  <li><button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#{{ $previewModalId }}"><i class="icon-base ri ri-eye-line me-2" aria-hidden="true"></i>Preview</button></li>
  @elseif (! empty($viewUrl))
  <li><a class="dropdown-item" href="{{ $viewUrl }}" target="_blank" rel="noopener noreferrer"><i class="icon-base ri ri-eye-line me-2" aria-hidden="true"></i>View</a></li>
  @endif
  @if (! empty($downloadUrl))
  <li><a class="dropdown-item" href="{{ $downloadUrl }}"><i class="icon-base ri ri-download-line me-2" aria-hidden="true"></i>Download</a></li>
  @endif
  @if (! empty($replaceInputId))
  <li><button class="dropdown-item" type="button" data-review-file-replace="{{ $replaceInputId }}" data-revision-upload-control><i class="icon-base ri ri-refresh-line me-2" aria-hidden="true"></i>Replace</button></li>
  @endif
  </ul>
</div>
