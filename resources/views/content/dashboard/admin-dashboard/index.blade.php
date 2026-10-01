@extends('layouts/contentNavbarLayout')
@section('title', 'Admin Dashboard')

@section('content')
@php
$statusLabels = [
'submitted' => 'For Review',
'under_review' => 'Under review',
'returned' => 'Revision requested',
'endorsed' => 'Endorsed',
'filed' => 'Filed',
];
@endphp

<section class="page-content">
  <div class="row g-4 mb-6">
    @foreach ($statusLabels as $status => $label)
    <div class="col-6 col-md-4 col-xl">
      <div class="card h-100">
        <div class="card-body py-4">
          <p class="mb-1">{{ $label }}</p>
          <h4 class="mb-0">{{ $statusCounts[$status] ?? 0 }}</h4>
        </div>
      </div>
    </div>
    @endforeach
  </div>

  @include('content.dashboard.admin-dashboard._submissions-table', [
  'disclosures' => $reviewQueue,
  'queue' => 'for-review',
  'title' => 'For Review',
  'emptyMessage' => 'No submissions are waiting for review.',
  'showDashboardHeading' => true,
  ])

  @include('content.dashboard.admin-dashboard._submissions-table', [
  'disclosures' => $reviewedSubmissions,
  'queue' => 'reviewed',
  'title' => 'Reviewed Submissions',
  'emptyMessage' => 'No reviewed submissions yet.',
  'showDashboardHeading' => false,
  ])
</section>
@endsection

@section('page-script')
@vite(['resources/assets/js/admin-dashboard.js'])
@endsection