@php
$statusLabels = [
'submitted' => 'For Review',
'under_review' => 'Under review',
'revised_submission' => 'Revised Submission',
'returned' => 'Revision requested',
'endorsed' => 'Endorsed',
'filed' => 'Filed',
];
@endphp

<div class="card mb-6">
  <div class="card-header border-0 pb-0">
    @if ($showDashboardHeading)
    <h4 class="mb-3">{{ $dashboardHeading ?? 'IP Head Dashboard' }}</h4>
    <hr class="page-content__divider">
    @endif
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <h5 class="card-title mb-0">{{ $title }}</h5>
      <span class="text-body-secondary small">{{ $disclosures->total() }} total</span>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 inventor-submissions-table">
      <colgroup>
        <col style="width: 8%">
        <col style="width: 14%">
        <col style="width: 26%">
        <col style="width: 17%">
        <col style="width: 12%">
        <col style="width: 11%">
        <col style="width: 12%">
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
        @forelse ($disclosures as $disclosure)
        @php
        $statusColor = match ($disclosure->status) {
        'endorsed', 'filed' => 'success',
        'returned' => 'danger',
        'under_review' => 'warning',
        default => 'primary',
        };
        @endphp
        <tr>
          <td class="text-center">
            <a class="btn btn-link btn-sm p-0 text-primary text-decoration-none flat-icon-button" href="{{ route('ip-head-dashboard.disclosures.show', ['disclosure' => $disclosure, 'queue' => $queue, 'page' => $disclosures->currentPage()]) }}" aria-label="Review submission: {{ $disclosure->technology_title }}" title="Review submission">
              <i class="icon-base ri ri-eye-line icon-24px" aria-hidden="true"></i>
            </a>
          </td>
          <td title="{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}"><span class="submission-table__type">{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}</span></td>
          <td>
            <span class="d-block fw-medium inventor-submissions-table__title" title="{{ $disclosure->technology_title }}">{{ $disclosure->technology_title }}</span>
          </td>
          <td>
            <span class="d-block">{{ $disclosure->user->name }}</span>
            <span class="small text-body-secondary text-break">{{ $disclosure->user->email }}</span>
          </td>
          <td><span class="badge bg-label-{{ $statusColor }}">{{ $statusLabels[$disclosure->status] ?? ucfirst($disclosure->status) }}</span></td>
          <td data-last-updated="{{ $disclosure->id }}" title="{{ $disclosure->updated_at->format('M j, Y g:i A') }}">{{ $disclosure->updated_at->diffForHumans() }}</td>
          <td class="submission-table__created-at" title="{{ $disclosure->created_at->format('M j, Y g:i A') }}"><span>{{ $disclosure->created_at->format('M j, Y') }}</span><span>{{ $disclosure->created_at->format('g:i A') }}</span></td>
        </tr>
        @empty
        <tr>
          <td colspan="7" class="text-center text-body-secondary py-8">{{ $emptyMessage }}</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@if ($disclosures->hasPages())
<div class="d-flex justify-content-center mt-5">{{ $disclosures->links() }}</div>
@endif