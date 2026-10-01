@php
$statusLabels = [
'submitted' => 'For Review',
'under_review' => 'Under review',
'returned' => 'Revision requested',
'endorsed' => 'Endorsed',
'filed' => 'Filed',
];
@endphp

<div class="card mb-6">
  <div class="card-header border-0 pb-0">
    @if ($showDashboardHeading)
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <h4 class="mb-0">Admin Dashboard</h4>
      <a class="btn btn-outline-primary" href="{{ route('dashboard-analytics') }}"><i class="icon-base ri ri-dashboard-line me-1"></i>User dashboard</a>
    </div>
    <hr class="page-content__divider">
    @endif
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
      <h5 class="card-title mb-0">{{ $title }}</h5>
      <span class="text-body-secondary small">{{ $disclosures->total() }} total</span>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 admin-submissions-table">
      <colgroup>
        <col style="width: 28%">
        <col style="width: 20%">
        <col style="width: 17%">
        <col style="width: 13%">
        <col style="width: 9%">
        <col style="width: 13%">
      </colgroup>
      <thead>
        <tr>
          <th>Innovation</th>
          <th>Submitter</th>
          <th>Status</th>
          <th>Submitted</th>
          <th class="text-center">Files</th>
          <th class="text-end">Review</th>
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
        $filesModalId = 'submission-files-' . str_replace('-', '', $queue) . '-' . $disclosure->id;
        @endphp
        <tr>
          <td>
            <span class="d-block fw-medium text-break">{{ $disclosure->technology_title }}</span>
            <span class="small text-body-secondary">{{ str_replace('_', ' ', ucfirst($disclosure->technology_type)) }}</span>
          </td>
          <td>
            <span class="d-block">{{ $disclosure->user->name }}</span>
            <span class="small text-body-secondary text-break">{{ $disclosure->user->email }}</span>
          </td>
          <td><span class="badge bg-label-{{ $statusColor }}">{{ $statusLabels[$disclosure->status] ?? ucfirst($disclosure->status) }}</span></td>
          <td>{{ $disclosure->created_at->format('M j, Y') }}</td>
          <td class="text-center">
            <button class="btn btn-sm btn-icon btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#{{ $filesModalId }}" aria-label="Review files for {{ $disclosure->technology_title }}" title="Review submitted files">
              <i class="icon-base ri ri-more-2-line" aria-hidden="true"></i>
            </button>
          </td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary" href="{{ route('admin-dashboard.disclosures.show', ['disclosure' => $disclosure, 'queue' => $queue, 'page' => $disclosures->currentPage()]) }}">
              <i class="icon-base ri ri-eye-line me-1"></i>Review
            </a>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="6" class="text-center text-body-secondary py-8">{{ $emptyMessage }}</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@foreach ($disclosures as $disclosure)
@include('content.dashboard.admin-dashboard._files-modal', [
'disclosure' => $disclosure,
'queue' => $queue,
'disclosuresPage' => $disclosures->currentPage(),
])
@endforeach

@if ($disclosures->hasPages())
<div class="d-flex justify-content-center mt-5">{{ $disclosures->links() }}</div>
@endif