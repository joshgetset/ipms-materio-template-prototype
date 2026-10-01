@extends('layouts/contentNavbarLayout')
@php
$page = $page ?? 'analytics';
@endphp
@section('title', $page === 'form' ? 'Innovation Disclosure' : 'Dashboard Analytics')

@section('vendor-style')
@if ($page === 'analytics')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endif
@endsection
@section('vendor-script')
@if ($page === 'analytics')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endif
@endsection

@section('content')
<section class="page-content">
  @if ($page === 'analytics')
  <div class="row g-6 mb-6">
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar">
              <div class="avatar-initial bg-primary rounded"><i class="icon-base ri ri-file-list-3-line icon-24px"></i></div>
            </div>
            <div>
              <p class="mb-1">Total disclosures</p>
              <h4 class="mb-0" data-dashboard-status-count="total">{{ $totalDisclosures }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar">
              <div class="avatar-initial bg-info rounded"><i class="icon-base ri ri-time-line icon-24px"></i></div>
            </div>
            <div>
              <p class="mb-1">Under review</p>
              <h4 class="mb-0" data-dashboard-status-count="under_review">{{ $statusCounts['under_review'] ?? 0 }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar">
              <div class="avatar-initial bg-success rounded"><i class="icon-base ri ri-checkbox-circle-line icon-24px"></i></div>
            </div>
            <div>
              <p class="mb-1">Endorsed</p>
              <h4 class="mb-0" data-dashboard-status-count="endorsed">{{ $statusCounts['endorsed'] ?? 0 }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar">
              <div class="avatar-initial bg-warning rounded"><i class="icon-base ri ri-send-plane-line icon-24px"></i></div>
            </div>
            <div>
              <p class="mb-1">Submitted</p>
              <h4 class="mb-0" data-dashboard-status-count="submitted">{{ $statusCounts['submitted'] ?? 0 }}</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-6 mb-6">
    <div class="col-12">
      <div class="card h-100">
        <div class="card-header border-0 pb-0" data-submission-status-url="{{ route('dashboard.submissions.status-updates') }}">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <h4 class="mb-0">Dashboard analytics</h4>
            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-primary" href="{{ route('dashboard.innovation-disclosure.create') }}"><i class="icon-base ri ri-add-line me-1"></i>New disclosure</a>
              <a class="btn btn-outline-primary" href="{{ route('dashboard.submissions.index') }}"><i class="icon-base ri ri-file-list-3-line me-1"></i>My Submission</a>
            </div>
          </div>
          <hr class="page-content__divider">
          <h5 class="card-title mb-0">Disclosure status</h5>
        </div>
        <div class="card-body">
          <div id="disclosureStatusChart" data-chart-values="{{ json_encode($chartValues) }}" data-chart-labels="{{ json_encode($chartLabels) }}" data-chart-statuses="{{ json_encode(['submitted', 'under_review', 'endorsed', 'returned', 'filed']) }}"></div>
        </div>
      </div>
    </div>
  </div>
  @endif

  @if ($page === 'form')
  @php
  $isSlsuEmployee = old('university_relationship') === 'employee';
  $isSlsuFunded = old('funding_source') === 'slsu_funded';
  $supportAnswer = (string) old('has_substantial_support', '');
  $isSlsuOwned = $isSlsuEmployee && ($isSlsuFunded || $supportAnswer === '1');
  $ownershipEligibilityResolved = ! $isSlsuEmployee || $isSlsuFunded || in_array($supportAnswer, ['0', '1'], true);
  $showExternalOwnershipOptions = $ownershipEligibilityResolved && ! $isSlsuOwned;
  $inventorRows = old('inventors');
  if (! is_array($inventorRows) || $inventorRows === []) {
  $inventorRows = [[
  'last_name' => '',
  'first_name' => '',
  'suffix' => '',
  'middle_initial' => '',
  'country_of_citizenship' => '',
  'affiliation' => '',
  ]];
  }
  $primaryInventor = (string) old('primary_inventor', array_key_first($inventorRows));
  $nextInventorIndex = max(array_map('intval', array_keys($inventorRows))) + 1;
  @endphp

  <div class="disclosure-form" id="new-disclosure">
    @if ($errors->any())
    <div class="alert alert-danger" role="alert">
      <ul class="mb-0 ps-4">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ route('dashboard.innovation-disclosures.store') }}">
      @csrf
      <div class="row g-5">
        <div class="col-12">
          <section class="disclosure-form-section bg-white shadow-sm" aria-labelledby="innovation-information-heading">
            <div class="disclosure-form-section__header disclosure-form-section__header--plain">
              <h4 class="mb-3">Innovation Disclosure</h4>
              <hr class="page-content__divider">
              <h5 class="mb-0 fw-bold" id="innovation-information-heading">Innovation Information</h5>
            </div>
            <div class="row g-5">
              <div class="col-md-6">
                <label class="form-label" for="email">Contact email</label>
                <input class="form-control" id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required maxlength="255">
              </div>
              <div class="col-md-6">
                <label class="form-label" for="mobile_no">Mobile number</label>
                <input class="form-control" id="mobile_no" name="mobile_no" type="tel" value="{{ old('mobile_no') }}" required maxlength="20" pattern="(09|\+639)[0-9]{9}" title="Use 09 followed by 9 digits or +639 followed by 9 digits.">
              </div>
              <div class="col-12">
                <label class="form-label" for="technology_title">Technology title</label>
                <input class="form-control" id="technology_title" name="technology_title" type="text" value="{{ old('technology_title') }}" required maxlength="500">
              </div>

              <div class="col-12">
                <label class="form-label" for="technology_type">Technology type</label>
                <select class="form-select" id="technology_type" name="technology_type" required>
                  <option value="">Choose a technology type</option>
                  <option value="chemical" @selected(old('technology_type')==='chemical' )>Chemical (Food, Pharmaceutical, Structural Chemistry, etc.) Technology</option>
                  <option value="mechanical" @selected(old('technology_type')==='mechanical' )>Mechanical (IT and Engineering) Technology</option>
                  <option value="literary_creative" @selected(old('technology_type')==='literary_creative' )>Literary/Creative Works</option>
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label" for="university_relationship">Relationship to the university</label>
                <select class="form-select" id="university_relationship" name="university_relationship" required>
                  <option value="">Choose a relationship</option>
                  <option value="student" @selected(old('university_relationship')==='student' )>Student</option>
                  <option value="employee" @selected(old('university_relationship')==='employee' )>Employee</option>
                  <option value="outsider" @selected(old('university_relationship')==='outsider' )>Outsider of University</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="funding_source">Funding source</label>
                <select class="form-select" id="funding_source" name="funding_source" required>
                  <option value="">Choose a funding source</option>
                  <option value="personal" @selected(old('funding_source')==='personal' )>From a personally-funded study/research/endeavors</option>
                  <option value="slsu_funded" @selected(old('funding_source')==='slsu_funded' )>From a study/research funded by SLSU</option>
                  <option value="externally_funded" @selected(old('funding_source')==='externally_funded' )>From a study/research funded externally</option>
                </select>
              </div>

              <div class="col-12 {{ $isSlsuEmployee && ! $isSlsuFunded ? '' : 'd-none' }}" id="substantial-support-fields">
                <label class="form-label" for="has_substantial_support">Did SLSU provide substantial support for this research?</label>
                <select class="form-select" id="has_substantial_support" name="has_substantial_support" @required($isSlsuEmployee && ! $isSlsuFunded) @disabled(! $isSlsuEmployee || $isSlsuFunded)>
                  <option value="">Choose an answer</option>
                  <option value="1" @selected($supportAnswer==='1' )>Yes, substantial funding or research facilities</option>
                  <option value="0" @selected($supportAnswer==='0' )>No</option>
                </select>
                <p class="form-text mb-0">Office space, desks, internet access, reference materials, and computers do not count as substantial support.</p>
              </div>

              <div class="col-12">
                <div class="alert alert-info mb-0">
                  <h6 class="alert-heading">Ownership and ITSO services</h6>
                  <p>Students and other non-employees, and employees without substantial SLSU support, are External Clients by default. Employees whose research was SLSU-funded or received substantial SLSU research support are automatically covered by SLSU ownership; no assignment is needed.</p>
                  <details>
                    <summary>Review ownership options, costs, and royalties</summary>
                    <div class="mt-3">
                      <h6>Retain full ownership</h6>
                      <p>ITSO can still provide extension services, guidance, and facilitation. You control whether to pursue IP protection and retain your full share of any royalties. You pay the IP transaction fees (including filing, maintenance, and prosecution fees) and a minimal ITSO service fee. <a href="https://drive.google.com/file/d/138zRWPEofZJCc5TzJvJ4hwUx8UPM-cPX/view" target="_blank" rel="noopener noreferrer">View the typical IP transaction fees</a>.</p>
                      <h6>Assign ownership to SLSU</h6>
                      <p>The University and you co-own the IP, and the University takes control of its pursuit. You are acknowledged as the creator and may receive royalties under the University IP Policy. SLSU decides how to proceed and covers the IP transaction fees and ITSO service costs.</p>
                      <h6>Automatic SLSU ownership</h6>
                      <p>Technologies produced by employees with substantial SLSU research support, including SLSU-funded research, are owned by SLSU. Provide the research title and approval date below.</p>
                    </div>
                  </details>
                </div>
              </div>

              <div class="col-12 {{ $showExternalOwnershipOptions ? '' : 'd-none' }}" id="external-ownership-options">
                <fieldset>
                  <legend class="form-label">After reviewing the options, I/we choose to:</legend>
                  <div class="row g-3">
                    <div class="col-md-6">
                      <div class="form-check ownership-choice border rounded p-3 h-100">
                        <input class="form-check-input ownership-option" id="ownership-assign" name="ownership_declaration" type="radio" value="assign_to_slsu" @checked(old('ownership_declaration')==='assign_to_slsu' ) @required($showExternalOwnershipOptions) @disabled(! $showExternalOwnershipOptions)>
                        <label class="form-check-label fw-semibold" for="ownership-assign">Assign ownership rights to SLSU</label>
                        <p class="small text-body-secondary mb-0 mt-2">SLSU takes control and covers IP transaction fees and ITSO services. You are acknowledged as creator and may share in royalties under the University IP Policy.</p>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-check ownership-choice border rounded p-3 h-100">
                        <input class="form-check-input ownership-option" id="ownership-retain" name="ownership_declaration" type="radio" value="retain_ownership" @checked(old('ownership_declaration')==='retain_ownership' ) @disabled(! $showExternalOwnershipOptions)>
                        <label class="form-check-label fw-semibold" for="ownership-retain">Retain full ownership</label>
                        <p class="small text-body-secondary mb-0 mt-2">You control IP protection and retain any royalties, and are responsible for transaction fees and the minimal ITSO service fee.</p>
                      </div>
                    </div>
                  </div>
                </fieldset>
              </div>

              <div class="col-12 {{ $isSlsuOwned ? '' : 'd-none' }}" id="automatic-slsu-ownership">
                <input type="hidden" id="ownership-not-applicable" name="ownership_declaration" value="not_applicable" @disabled(! $isSlsuOwned)>
                <div class="alert alert-success mb-0">
                  <strong>Not Applicable. SLSU rightfully owns this technology.</strong>
                  <span>This is set automatically because this employee research is SLSU-funded or received substantial SLSU support.</span>
                </div>
              </div>

              <div class="col-12">
                <div class="form-check">
                  <input class="form-check-input" id="ownership-consent" name="ownership_consent" type="checkbox" value="1" required @checked(old('ownership_consent')==='1' )>
                  <label class="form-check-label" for="ownership-consent">I/We have reviewed these ownership options, understand the responsibilities and costs, and consent to the applicable ownership declaration.</label>
                </div>
              </div>

              <div class="col-12 {{ $isSlsuOwned ? '' : 'd-none' }}" id="research-fields">
                <p class="small text-body-secondary mb-3">Enter the title and approval date of the research that produced this technology.</p>
                <div class="row g-5">
                  <div class="col-md-8">
                    <label class="form-label" for="research_title">Research title</label>
                    <input class="form-control" id="research_title" name="research_title" type="text" value="{{ old('research_title') }}" maxlength="500" @required($isSlsuOwned) @disabled(! $isSlsuOwned)>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label" for="research_approval_date">Research approval date</label>
                    <input class="form-control" id="research_approval_date" name="research_approval_date" type="date" value="{{ old('research_approval_date') }}" @required($isSlsuOwned) @disabled(! $isSlsuOwned)>
                  </div>
                </div>
              </div>
              <div class="col-12 {{ $isSlsuOwned ? 'd-none' : '' }}" id="research-not-applicable">
                <p class="small text-body-secondary mb-0">Research title and approval date: N/A for this ownership declaration.</p>
              </div>

              <div class="col-12">
                <label class="form-label" for="background_summary">Background summary</label>
                <textarea class="form-control" id="background_summary" name="background_summary" rows="4" minlength="100" required>{{ old('background_summary') }}</textarea>
              </div>
              <div class="col-12">
                <label class="form-label" for="detailed_description">Detailed description</label>
                <textarea class="form-control" id="detailed_description" name="detailed_description" rows="6" minlength="100" required>{{ old('detailed_description') }}</textarea>
              </div>
              <div class="col-12">
                <label class="form-label" for="drawings_description">Drawings description <span class="text-body-secondary">(optional)</span></label>
                <textarea class="form-control" id="drawings_description" name="drawings_description" rows="4">{{ old('drawings_description') }}</textarea>
              </div>
            </div>
          </section>
        </div>

        <div class="col-12">
          <section class="disclosure-form-section bg-white shadow-sm" aria-labelledby="inventors-heading">
            <div class="disclosure-form-section__header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
              <h5 class="mb-0 fw-bold" id="inventors-heading">Inventors</h5>
              <button class="btn btn-outline-primary btn-sm" type="button" id="add-inventor"><i class="icon-base ri ri-user-add-line me-1"></i>Add another inventor</button>
            </div>
            <div id="inventors-list" data-next-index="{{ $nextInventorIndex }}">
              @foreach ($inventorRows as $index => $inventor)
              <fieldset class="inventor-row mb-4" data-inventor-index="{{ $index }}">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                  <legend class="h6 mb-0">Inventor {{ $loop->iteration }}</legend>
                  <div>
                    <button class="btn btn-sm btn-label-danger remove-inventor" type="button" aria-label="Remove inventor {{ $loop->iteration }}" title="Remove inventor" @disabled(count($inventorRows)===1)><i class="icon-base ri ri-delete-bin-line"></i></button>
                  </div>
                </div>
                <div class="row g-4">
                  <div class="col-md-4"><label class="form-label" for="inventor-{{ $index }}-last-name">Last name</label><input class="form-control" data-inventor-field="last_name" id="inventor-{{ $index }}-last-name" name="inventors[{{ $index }}][last_name]" value="{{ $inventor['last_name'] ?? '' }}" maxlength="100" required></div>
                  <div class="col-md-4"><label class="form-label" for="inventor-{{ $index }}-first-name">First name</label><input class="form-control" data-inventor-field="first_name" id="inventor-{{ $index }}-first-name" name="inventors[{{ $index }}][first_name]" value="{{ $inventor['first_name'] ?? '' }}" maxlength="100" required></div>
                  <div class="col-md-2"><label class="form-label" for="inventor-{{ $index }}-middle-initial">Middle initial</label><input class="form-control" id="inventor-{{ $index }}-middle-initial" name="inventors[{{ $index }}][middle_initial]" value="{{ $inventor['middle_initial'] ?? '' }}" maxlength="1"></div>
                  <div class="col-md-2"><label class="form-label" for="inventor-{{ $index }}-suffix">Suffix</label><input class="form-control" id="inventor-{{ $index }}-suffix" name="inventors[{{ $index }}][suffix]" value="{{ $inventor['suffix'] ?? '' }}" maxlength="20" placeholder="Jr., Sr., III"></div>
                  <div class="col-md-6">
                    <label class="form-label" for="inventor-{{ $index }}-country">Country of citizenship</label>
                    <select class="form-select" id="inventor-{{ $index }}-country" name="inventors[{{ $index }}][country_of_citizenship]" required>
                      <option value="">Choose a country</option>
                      @foreach (config('countries') as $country)
                      <option value="{{ $country }}" @selected(($inventor['country_of_citizenship'] ?? '' )===$country)>{{ $country }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label" for="inventor-{{ $index }}-affiliation">Affiliation</label>
                    <select class="form-select" id="inventor-{{ $index }}-affiliation" name="inventors[{{ $index }}][affiliation]" required>
                      <option value="">Choose an affiliation</option>
                      <option value="slsu_employee" @selected(($inventor['affiliation'] ?? '' )==='slsu_employee' )>SLSU employee</option>
                      <option value="external_inventor" @selected(($inventor['affiliation'] ?? '' )==='external_inventor' )>External inventor</option>
                      <option value="student" @selected(($inventor['affiliation'] ?? '' )==='student' )>Student</option>
                      <option value="faculty_adviser" @selected(($inventor['affiliation'] ?? '' )==='faculty_adviser' )>Faculty adviser</option>
                      <option value="external_collaborator" @selected(($inventor['affiliation'] ?? '' )==='external_collaborator' )>External collaborator</option>
                    </select>
                  </div>
                </div>
              </fieldset>
              @endforeach
            </div>
            <div class="col-md-6 px-0">
              <label class="form-label" for="primary_inventor">Primary inventor</label>
              <select class="form-select" id="primary_inventor" name="primary_inventor" required>
                @foreach ($inventorRows as $index => $inventor)
                @php
                $inventorName = trim(($inventor['first_name'] ?? '') . ' ' . ($inventor['last_name'] ?? ''));
                @endphp
                <option value="{{ $index }}" @selected((string) $index===$primaryInventor)>{{ $inventorName !== '' ? $inventorName : 'Inventor ' . $loop->iteration }}</option>
                @endforeach
              </select>
            </div>
          </section>
        </div>

        <div class="col-12">
          <section class="disclosure-form-section bg-white shadow-sm" aria-labelledby="supporting-document-heading">
            <div class="disclosure-form-section__header">
              <h5 class="mb-0 fw-bold" id="supporting-document-heading">Supporting document</h5>
            </div>
            <p class="small text-body-secondary">PDF, DOC, DOCX, JPG, or PNG. Maximum 15 MB per file.</p>
            <div class="row g-4">
              <div class="col-md-4"><label class="form-label" for="attachment-drawings">Drawings</label><input class="form-control" id="attachment-drawings" name="attachments[drawings]" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
              <div class="col-md-4"><label class="form-label" for="attachment-methodology">Methodology</label><input class="form-control" id="attachment-methodology" name="attachments[methodology]" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
              <div class="col-md-4"><label class="form-label" for="attachment-assistance">Assistance form</label><input class="form-control" id="attachment-assistance" name="attachments[assistance_form]" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></div>
            </div>
          </section>
        </div>
        <div class="col-12 d-flex justify-content-end">
          <button class="btn btn-primary" type="submit"><i class="icon-base ri ri-send-plane-line me-1"></i>Submit disclosure</button>
        </div>
      </div>
    </form>
  </div>

  <template id="inventor-template">
    <fieldset class="inventor-row mb-4">
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <legend class="h6 mb-0">Inventor <span data-inventor-number></span></legend>
        <button class="btn btn-sm btn-label-danger remove-inventor" type="button" aria-label="Remove inventor" title="Remove inventor"><i class="icon-base ri ri-delete-bin-line"></i></button>
      </div>
      <div class="row g-4">
        <div class="col-md-4"><label class="form-label" data-label-for="last_name">Last name</label><input class="form-control" data-field="last_name" data-inventor-field="last_name" maxlength="100" required></div>
        <div class="col-md-4"><label class="form-label" data-label-for="first_name">First name</label><input class="form-control" data-field="first_name" data-inventor-field="first_name" maxlength="100" required></div>
        <div class="col-md-2"><label class="form-label" data-label-for="middle_initial">Middle initial</label><input class="form-control" data-field="middle_initial" maxlength="1"></div>
        <div class="col-md-2"><label class="form-label" data-label-for="suffix">Suffix</label><input class="form-control" data-field="suffix" maxlength="20" placeholder="Jr., Sr., III"></div>
        <div class="col-md-6">
          <label class="form-label" data-label-for="country_of_citizenship">Country of citizenship</label>
          <select class="form-select" data-field="country_of_citizenship" required>
            <option value="">Choose a country</option>
            @foreach (config('countries') as $country)
            <option value="{{ $country }}">{{ $country }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label" data-label-for="affiliation">Affiliation</label>
          <select class="form-select" data-field="affiliation" required>
            <option value="">Choose an affiliation</option>
            <option value="slsu_employee">SLSU employee</option>
            <option value="external_inventor">External inventor</option>
            <option value="student">Student</option>
            <option value="faculty_adviser">Faculty adviser</option>
            <option value="external_collaborator">External collaborator</option>
          </select>
        </div>
      </div>
    </fieldset>
  </template>
  @endif
</section>
@endsection

@section('page-script')
@if ($page === 'analytics')
<script>
  const disclosureChart = document.querySelector('#disclosureStatusChart');
  if (disclosureChart && window.ApexCharts) {
    window.disclosureStatusChart = new ApexCharts(disclosureChart, {
      chart: {
        type: 'bar',
        height: 280,
        toolbar: {
          show: false
        }
      },
      series: [{
        name: 'Disclosures',
        data: JSON.parse(disclosureChart.dataset.chartValues)
      }],
      xaxis: {
        categories: JSON.parse(disclosureChart.dataset.chartLabels)
      },
      colors: ['#2563eb'],
      plotOptions: {
        bar: {
          borderRadius: 4,
          columnWidth: '45%',
          distributed: true
        }
      },
      dataLabels: {
        enabled: false
      },
      legend: {
        show: false
      },
      yaxis: {
        min: 0,
        forceNiceScale: true
      }
    });
    window.disclosureStatusChart.render();
  }
</script>
@vite(['resources/assets/js/user-submission-status.js'])
@endif

@if ($page === 'form')
<script>
  const disclosureForm = document.querySelector('#new-disclosure form');
  const researchFields = document.querySelector('#research-fields');
  const researchInputs = researchFields.querySelectorAll('input');
  const relationshipField = disclosureForm.querySelector('[name="university_relationship"]');
  const fundingField = disclosureForm.querySelector('[name="funding_source"]');
  const supportFields = document.querySelector('#substantial-support-fields');
  const supportField = disclosureForm.querySelector('[name="has_substantial_support"]');
  const externalOwnershipOptions = document.querySelector('#external-ownership-options');
  const externalOwnershipInputs = externalOwnershipOptions.querySelectorAll('[name="ownership_declaration"]');
  const automaticOwnership = document.querySelector('#automatic-slsu-ownership');
  const automaticOwnershipInput = document.querySelector('#ownership-not-applicable');
  const researchNotApplicable = document.querySelector('#research-not-applicable');

  function updateOwnershipFields() {
    const isSlsuEmployee = relationshipField.value === 'employee';
    const isSlsuFunded = fundingField.value === 'slsu_funded';
    const askAboutSupport = isSlsuEmployee && !isSlsuFunded;
    const hasSupportAnswer = supportField.value !== '';
    const ownershipResolved = !isSlsuEmployee || isSlsuFunded || hasSupportAnswer;
    const isSlsuOwned = isSlsuEmployee && (isSlsuFunded || supportField.value === '1');
    const showExternalOptions = ownershipResolved && !isSlsuOwned;

    supportFields.classList.toggle('d-none', !askAboutSupport);
    supportField.disabled = !askAboutSupport;
    supportField.required = askAboutSupport;
    externalOwnershipOptions.classList.toggle('d-none', !showExternalOptions);
    externalOwnershipInputs.forEach((input, index) => {
      input.disabled = !showExternalOptions;
      input.required = showExternalOptions && index === 0;
    });
    automaticOwnership.classList.toggle('d-none', !isSlsuOwned);
    automaticOwnershipInput.disabled = !isSlsuOwned;
    researchFields.classList.toggle('d-none', !isSlsuOwned);
    researchNotApplicable.classList.toggle('d-none', isSlsuOwned);
    researchInputs.forEach((input) => {
      input.disabled = !isSlsuOwned;
      input.required = isSlsuOwned;
      if (!isSlsuOwned) input.value = '';
    });
  }

  [relationshipField, fundingField, supportField].forEach((input) => input.addEventListener('change', updateOwnershipFields));
  updateOwnershipFields();

  const inventorsList = document.querySelector('#inventors-list');
  const inventorTemplate = document.querySelector('#inventor-template');
  const addInventorButton = document.querySelector('#add-inventor');
  const primaryInventorSelect = document.querySelector('#primary_inventor');

  function updateInventorRows() {
    const rows = [...inventorsList.querySelectorAll('.inventor-row')];
    const selectedPrimary = primaryInventorSelect.value;
    const options = rows.map((row, index) => {
      const firstName = row.querySelector('[data-inventor-field="first_name"]').value.trim();
      const lastName = row.querySelector('[data-inventor-field="last_name"]').value.trim();
      const name = [firstName, lastName].filter(Boolean).join(' ');
      return new Option(name || `Inventor ${index + 1}`, row.dataset.inventorIndex);
    });
    primaryInventorSelect.replaceChildren(...options);
    primaryInventorSelect.value = options.some((option) => option.value === selectedPrimary) ?
      selectedPrimary :
      options[0].value;

    rows.forEach((row, index) => {
      row.querySelector('.remove-inventor').disabled = rows.length === 1;
      const number = row.querySelector('[data-inventor-number]');
      if (number) number.textContent = index + 1;
    });
  }

  addInventorButton.addEventListener('click', () => {
    const index = Number(inventorsList.dataset.nextIndex);
    inventorsList.dataset.nextIndex = index + 1;
    const fragment = inventorTemplate.content.cloneNode(true);
    const row = fragment.querySelector('.inventor-row');
    row.dataset.inventorIndex = index;
    row.querySelectorAll('[data-field]').forEach((field) => {
      const key = field.dataset.field;
      field.name = `inventors[${index}][${key}]`;
      field.id = `inventor-${index}-${key.replaceAll('_', '-')}`;
      row.querySelector(`[data-label-for="${key}"]`).htmlFor = field.id;
    });
    inventorsList.append(fragment);
    updateInventorRows();
  });

  inventorsList.addEventListener('input', (event) => {
    if (event.target.matches('[data-inventor-field]')) updateInventorRows();
  });

  inventorsList.addEventListener('click', (event) => {
    const removeButton = event.target.closest('.remove-inventor');
    if (!removeButton || inventorsList.querySelectorAll('.inventor-row').length === 1) return;

    const row = removeButton.closest('.inventor-row');
    const removedPrimary = primaryInventorSelect.value === row.dataset.inventorIndex;
    row.remove();
    updateInventorRows();
  });
</script>
@vite(['resources/assets/js/innovation-disclosure.js'])
@endif
@endsection