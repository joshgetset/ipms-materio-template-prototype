@extends('layouts/contentNavbarLayout')
@section('title', 'IP Head Dashboard')

@section('content')
@php
$statusLabels = [
'submitted' => 'For Review',
'under_review' => 'Under review',
'revised_submission' => 'Revised Submission',
'returned' => 'Revision requested',
'endorsed' => 'Endorsed',
'filed' => 'Filed',
];
$statusIcons = [
'submitted' => 'ri-file-list-3-line',
'under_review' => 'ri-time-line',
'revised_submission' => 'ri-file-edit-line',
'returned' => 'ri-arrow-go-back-line',
'endorsed' => 'ri-checkbox-circle-line',
'filed' => 'ri-archive-line',
];
@endphp

<section class="page-content">
  <div class="row g-4 mb-6">
    @foreach ($statusLabels as $status => $label)
    <div class="col-6 col-md-4 col-xl">
      <div class="card h-100">
        <div class="card-body py-4">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar">
              <div class="avatar-initial bg-label-primary text-primary rounded"><i class="icon-base ri {{ $statusIcons[$status] }} icon-24px" aria-hidden="true"></i></div>
            </div>
            <div class="min-w-0">
              <p class="mb-1">{{ $label }}</p>
              <h4 class="mb-0">{{ $statusCounts[$status] ?? 0 }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endforeach
  </div>
</section>
@endsection

@section('page-script')
<script src="{{ asset('ip-head/js/ip-head-dashboard.js') }}" defer></script>
@endsection