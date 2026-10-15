@php
  $bytes = (int) ($sizeBytes ?? 0);
  $sizeLabel = $bytes >= 1048576
    ? number_format($bytes / 1048576, 1) . ' MB'
    : ($bytes >= 1024 ? number_format($bytes / 1024, 0) . ' KB' : $bytes . ' B');
  $extension = strtoupper((string) pathinfo((string) $fileName, PATHINFO_EXTENSION) ?: 'FILE');
  $metaParts = array_values(array_filter([
    $extension,
    $bytes > 0 ? $sizeLabel : null,
    $versionLabel ?? null,
    $dateLabel ?? null,
  ]));
@endphp
<div class="{{ $wrapperClass ?? '' }}" @if (! empty($attachmentId)) data-file-attachment="{{ $attachmentId }}" @endif>
  @if (! empty($fileLabel))
  <small class="review-files-label">{{ $fileLabel }}</small>
  @endif
  @if (! empty($fileVersionFlag))
  <div class="review-files-identity-line">
    <span class="review-files-name text-break">{{ $fileName }}</span>
    <span class="ip-head-file-version-flag ip-head-file-version-flag--{{ strtolower($fileVersionFlag) }}">
      @if ($fileVersionFlag === 'Revised')
      <i class="icon-base ri ri-refresh-line" aria-hidden="true"></i>
      @else
      <i class="icon-base ri ri-flag-line" aria-hidden="true"></i>
      @endif
      {{ $fileVersionFlag }}
    </span>
  </div>
  @else
  <span class="review-files-name text-break">{{ $fileName }}</span>
  @endif
  @if ($metaParts !== [])
  <small class="review-files-meta">{{ implode(' · ', $metaParts) }}</small>
  @elseif (! empty($emptyMeta))
  <small class="review-files-meta">{{ $emptyMeta }}</small>
  @endif
</div>
