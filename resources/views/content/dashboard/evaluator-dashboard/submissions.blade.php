@extends('layouts/contentNavbarLayout')
@section('title', 'Innovation Submission')

@section('content')
<section class="page-content">
  @include('content.dashboard.evaluator-dashboard._submissions-table', [
  'disclosures' => $reviewQueue,
  'queue' => 'for-review',
  'title' => 'For Review',
  'emptyMessage' => 'No submissions are waiting for review.',
  'showDashboardHeading' => true,
  'dashboardHeading' => 'Innovation Submission',
  ])

  @include('content.dashboard.evaluator-dashboard._submissions-table', [
  'disclosures' => $reviewedSubmissions,
  'queue' => 'reviewed',
  'title' => 'Reviewed Submissions',
  'emptyMessage' => 'No reviewed submissions yet.',
  'showDashboardHeading' => false,
  ])
</section>
@endsection

@section('page-script')
@vite(['resources/assets/js/evaluator-dashboard.js'])
@endsection