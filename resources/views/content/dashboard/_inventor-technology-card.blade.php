@php
  $technologyFiles = $technology->files->sortBy(fn ($file) => array_search($file->narrative_key, array_keys($technologyFileLabels), true));
  $revisionCount = $technologyFiles->where('review_status', 'revision_requested')->count();
  $approvedCount = $technologyFiles->where('review_status', 'accepted')->count();
  $fileCount = $technologyFiles->count();
@endphp
<article class="inventor-technology-card {{ $revisionCount > 0 ? 'has-revisions' : ($fileCount > 0 && $approvedCount === $fileCount ? 'is-approved' : '') }}">
  <header class="inventor-technology-card__header">
    <div class="min-w-0">
      <h5 class="inventor-technology-card__title">{{ $technology->technology_title }}</h5>
      <p class="inventor-technology-card__meta">{{ $technologyCategoryLabel }} · {{ $fileCount }} {{ \Illuminate\Support\Str::plural('file', $fileCount) }}</p>
    </div>
    <span class="inventor-technology-card__progress" aria-label="{{ $approvedCount }} of {{ $fileCount }} files approved">{{ $approvedCount }}/{{ $fileCount }}</span>
  </header>
  <div class="inventor-technology-card__summary" aria-label="Technology file status summary">
    <span class="inventor-technology-card__summary-item is-revision"><i aria-hidden="true"></i>{{ $revisionCount }} revision</span>
    <span class="inventor-technology-card__summary-item is-review"><i aria-hidden="true"></i>{{ $technologyFiles->where('review_status', 'review_needed')->count() }} in review</span>
    <span class="inventor-technology-card__summary-item is-approved"><i aria-hidden="true"></i>{{ $approvedCount }} approved</span>
  </div>
  <div class="inventor-technology-card__files">
    @foreach ($technologyFiles as $technologyFile)
    @php
      $fileStatus = $technologyFile->review_status;
      $fileStatusLabel = match ($fileStatus) {
        'accepted' => 'Approved',
        'revision_requested' => 'Revision requested',
        'review_needed' => $technologyFile->version > 1 ? 'Revised · In review' : 'In review',
        default => 'In review',
      };
      $fileInputId = 'technology-file-revision-' . $technologyFile->id;
      $submissionNoteInputId = 'technology-file-note-' . $technologyFile->id;
      $revisionFormId = 'technology-file-revision-form-' . $technologyFile->id;
      $previewUrl = route('dashboard.submissions.technology-files.preview', [
        $disclosure,
        $technologyFile,
        'v' => $technologyFile->version,
      ]);
      $versionHistory = $technologyFile->versions->map(fn ($version) => [
        'version' => $version->version,
        'name' => $version->original_name,
        'size' => $version->size_bytes,
        'date' => $version->submitted_at?->format('M j, Y') ?? $version->created_at?->format('M j, Y'),
        'url' => route('dashboard.submissions.technology-files.versions.preview', [$disclosure, $technologyFile, $version]),
        'guidanceUrl' => $version->revision_guidance_path
          ? route('dashboard.submissions.technology-files.versions.preview', [$disclosure, $technologyFile, $version]) . '?guidance=1'
          : null,
        'status' => $version->review_status,
      ])->values();
      $canReplaceTechnologyFile = $canSubmitFileRevisions && $fileStatus === 'revision_requested';
    @endphp
    <button
      class="inventor-technology-file-chip is-{{ $fileStatus }}"
      type="button"
      data-technology-file-open
      data-technology-file-id="{{ $technologyFile->id }}"
      data-file-name="{{ $technologyFile->original_name }}"
      data-file-label="{{ $technologyFileLabels[$technologyFile->narrative_key] ?? 'Technology file' }}"
      data-file-size="{{ $technologyFile->size_bytes }}"
      data-file-version="{{ $technologyFile->version }}"
      data-file-date="{{ $technologyFile->updated_at?->format('M j, Y') }}"
      data-file-status="{{ $fileStatus }}"
      data-file-status-label="{{ $fileStatusLabel }}"
      data-file-revision-note="{{ $technologyFile->revision_notes }}"
      data-file-guidance-url="{{ $technologyFile->revision_guidance_path ? route('dashboard.submissions.technology-files.preview', [$disclosure, $technologyFile]) . '?guidance=1' : '' }}"
      data-file-guidance-name="{{ $technologyFile->revision_guidance_name }}"
      data-file-submission-note="{{ $technologyFile->submission_note }}"
      data-file-preview-url="{{ $previewUrl }}"
      data-technology-title="{{ $technology->technology_title }}"
      data-technology-category="{{ $technologyCategoryLabel }}"
      data-technology-file-versions="{{ $versionHistory->toJson() }}"
      @if ($canReplaceTechnologyFile)
      data-file-input-id="{{ $fileInputId }}"
      data-submission-note-input-id="{{ $submissionNoteInputId }}"
      data-revision-form-id="{{ $revisionFormId }}"
      @endif
      aria-label="Open {{ $technologyFile->original_name }}, {{ $fileStatusLabel }}">
      <i class="inventor-technology-file-chip__dot" aria-hidden="true"></i>
      <span>{{ $technologyFile->original_name }}</span>
      @if ($technologyFile->version > 1)
      <span class="inventor-technology-file-chip__version">Revised v{{ $technologyFile->version }}</span>
      @endif
    </button>
    @endforeach
  </div>
  @if ($revisionCount > 0)
  <footer class="inventor-technology-card__notice is-revision">
    <i class="icon-base ri ri-alert-line" aria-hidden="true"></i>
    <span>{{ $revisionCount }} {{ \Illuminate\Support\Str::plural('file', $revisionCount) }} need your revision</span>
    @if ($canSubmitFileRevisions)
    <button class="inventor-technology-card__fix-first" type="button" data-technology-fix-first>Fix first <span aria-hidden="true">→</span></button>
    @endif
  </footer>
  @elseif ($fileCount > 0 && $approvedCount === $fileCount)
  <footer class="inventor-technology-card__notice is-approved">
    <i class="icon-base ri ri-checkbox-circle-line" aria-hidden="true"></i>
    <span>All files approved — no action needed</span>
  </footer>
  @endif
</article>
