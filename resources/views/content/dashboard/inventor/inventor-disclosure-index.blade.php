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
@section('page-style')
<link rel="stylesheet" href="{{ asset('user/css/innovation-disclosure.css') }}">
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
          <div id="disclosureStatusChart" data-chart-values="{{ json_encode($chartValues) }}" data-chart-labels="{{ json_encode($chartLabels) }}" data-chart-statuses="{{ json_encode(['submitted', 'under_review', 'revised_submission', 'endorsed', 'returned', 'filed']) }}"></div>
        </div>
      </div>
    </div>
  </div>
  @endif

  @if ($page === 'form')
  @php
  $inventorRows = old('inventors', []);
  if (! is_array($inventorRows)) $inventorRows = [];
  $materialRows = old('materials', []);
  if (! is_array($materialRows)) $materialRows = [];
  $materialRows = array_filter($materialRows, static function ($material) {
  if (! is_array($material)) return true;
  foreach (['item', 'description', 'quantity', 'unit_cost', 'purpose'] as $field) {
  if (filled($material[$field] ?? null)) return true;
  }
  return false;
  });
  $materialRows = array_values($materialRows);
  $narrativeFields = ['abstract_summary', 'technical_field', 'background_summary', 'invention_summary', 'detailed_description', 'examples_results'];
  $narrativeLabels = [
  'abstract_summary' => 'Abstract / Summary',
  'technical_field' => 'Technical / Creative Field',
  'background_summary' => 'Background / Problem',
  'invention_summary' => 'Summary of the Invention / Innovation',
  'detailed_description' => 'Detailed Description',
  'examples_results' => 'Examples / Experimental Results / Validation',
  ];
  $technologyTableColumns = [
  'abstract_summary' => 'Abstract',
  'invention_summary' => 'Invention Summary',
  'detailed_description' => 'Description',
  'technical_field' => 'Field',
  'background_summary' => 'Background',
  'examples_results' => 'Results',
  ];
  $technologyRows = old('technologies');
  if (! is_array($technologyRows)) $technologyRows = [];
  $technologyRows = array_map(static function ($technology) use ($narrativeFields) {
  $technology = is_array($technology) ? $technology : [];
  return [
  'technology_title' => $technology['technology_title'] ?? '',
  ...array_fill_keys($narrativeFields, ''),
  ...array_intersect_key($technology, array_flip($narrativeFields)),
  ];
  }, $technologyRows);
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
          <section class="disclosure-form-section bg-white shadow-sm" aria-labelledby="creator-information-heading">
            <div class="disclosure-form-section__header">
              <h4 class="mb-0 fw-bold" id="creator-information-heading">Creator Information</h4>
            </div>
            <div class="idf-creator-information-grid">
              <div><label class="form-label" for="lead_creator_name">Name of lead creator</label><input class="form-control" id="lead_creator_name" name="lead_creator_name" value="{{ old('lead_creator_name') }}" required maxlength="255"></div>
              <div><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required maxlength="255"></div>
              <div>
                <label class="form-label" for="university_relationship">Relationship to SLSU</label>
                <select class="form-select" id="university_relationship" name="university_relationship" required>
                  <option value="">Choose relationship</option>
                  <option value="faculty_academic" @selected(old('university_relationship')==='faculty_academic')>Faculty / Academic</option>
                  <option value="admin_staff" @selected(old('university_relationship')==='admin_staff')>Administrative staff</option>
                  <option value="contractual_job_order" @selected(old('university_relationship')==='contractual_job_order')>Contractual / Job Order</option>
                  <option value="external_partner" @selected(old('university_relationship')==='external_partner')>External partner</option>
                  <option value="student" @selected(old('university_relationship')==='student')>Student</option>
                  <option value="alumni" @selected(old('university_relationship')==='alumni')>Alumni</option>
                  <option value="other_specify" @selected(old('university_relationship')==='other_specify')>Other (specify)</option>
                </select>
              </div>
              <div class="{{ old('university_relationship') === 'other_specify' ? '' : 'd-none' }}" id="other-relationship-field">
                <label class="form-label" for="other_relationship">Please specify your relationship</label>
                <input class="form-control" id="other_relationship" name="other_relationship" value="{{ old('other_relationship') }}" maxlength="255" @required(old('university_relationship') === 'other_specify')>
              </div>
              <div><label class="form-label" for="college_department">College / Department</label><input class="form-control" id="college_department" name="college_department" value="{{ old('college_department') }}" required maxlength="255"></div>
              <div><label class="form-label" for="campus">Campus</label><input class="form-control" id="campus" name="campus" value="{{ old('campus') }}" required maxlength="255"></div>
              <div><label class="form-label" for="mobile_no">Contact number</label><input class="form-control" id="mobile_no" name="mobile_no" type="tel" value="{{ old('mobile_no') }}" required maxlength="20" pattern="(09|\+639)[0-9]{9}" title="Use 09 followed by 9 digits or +639 followed by 9 digits."></div>
              <div><label class="form-label" for="research_title">Research project title</label><input class="form-control" id="research_title" name="research_title" value="{{ old('research_title') }}" required maxlength="500"></div>
              <div><label class="form-label" for="funding_source">Funding source</label><input class="form-control" id="funding_source" name="funding_source" value="{{ old('funding_source') }}" required maxlength="500"></div>
              <div>
                <label class="form-label" for="technology_type">Technology / Invention Category</label>
                <select class="form-select" id="technology_type" name="technology_type" required>
                  <option value="">Choose a category</option>
                  <option value="mechanical_engineering" @selected(old('technology_type')==='mechanical_engineering')>IT, IoT &amp; Engineering</option>
                  <option value="chemical" @selected(old('technology_type')==='chemical')>Food, Pharmaceutical, Structural Chemistry</option>
                </select>
              </div>
              <div><label class="form-label" for="date_of_creation">Date of creation</label><input class="form-control" id="date_of_creation" name="date_of_creation" type="date" value="{{ old('date_of_creation') }}" required></div>
              <div><label class="form-label" for="research_approval_date">Research proposal approval date</label><input class="form-control" id="research_approval_date" name="research_approval_date" type="date" value="{{ old('research_approval_date') }}" required></div>
              <div><label class="form-label" for="disclosure-date-display">Date of this disclosure</label><input class="form-control" id="disclosure-date-display" value="{{ now()->toDateString() }}" readonly aria-describedby="disclosure-date-help"><div class="form-text" id="disclosure-date-help">Automatically set to today when submitted.</div></div>
            </div>
          </section>
        </div>

        <div class="col-12">
          <section class="disclosure-form-section bg-white shadow-sm" aria-labelledby="inventors-heading">
            <div class="disclosure-form-section__header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
              <h4 class="mb-0 fw-bold" id="inventors-heading">Creators and Contributions</h4>
              <button class="btn btn-outline-primary" type="button" id="add-inventor" data-bs-toggle="modal" data-bs-target="#creator-modal"><i class="icon-base ri ri-user-add-line me-1"></i>Add creator</button>
            </div>
            <div id="inventors-list" aria-live="polite">
              <div class="table-responsive">
                <table class="table table-hover align-middle inventors-table mb-0">
                  <thead><tr><th scope="col">Name</th><th scope="col">Position</th><th scope="col">Declared Contribution</th><th scope="col">Technical Contribution</th><th scope="col" class="text-end">Action</th></tr></thead>
                  <tbody id="inventors-table-body"></tbody>
                </table>
              </div>
              <div id="inventor-hidden-fields" class="d-none"></div>
            </div>
            <div class="alert alert-info mb-0 mt-3" role="status">Declared contributions currently total <strong id="contribution-total">0.00%</strong>; the final total must be exactly 100%. For more than six creators, attach an additional signed creator sheet under Supporting Materials.</div>
          </section>
        </div>

        <div class="col-12">
          <section class="disclosure-form-section bg-white shadow-sm" aria-labelledby="ownership-heading">
            <div class="disclosure-form-section__header">
              <h4 class="mb-0 fw-bold" id="ownership-heading">Ownership and University Support Facts</h4>
            </div>
            <div class="row g-4">
              <div class="col-12">
                <details class="alert alert-info ownership-information p-0 mb-0">
                  <summary class="ownership-information__summary">
                    <span class="ownership-information__preview">
                      <strong class="d-block">Ownership Information</strong>
                      <span>The ownership of Technology/IP is determined under the SLSU Intellectual Property Policy, considering the Creator's relationship with the University, how it was created, resources and funding used, and applicable agreements. Expand to read the full declaration.</span>
                    </span>
                    <i class="icon-base ri ri-arrow-down-s-line ownership-information__chevron" aria-hidden="true"></i>
                  </summary>
                  <div class="ownership-information__content">
                    <h5>Declaration</h5>
                    <p>The ownership of the Technology/IP shall be determined in accordance with the SLSU Intellectual Property Policy, taking into consideration the Creator's relationship with the University, the circumstances under which the Technology/IP was created, the use of University resources, funding, duties or assignments, and applicable agreements.</p>

                    <h6>If Ownership Is Automatically Vested in SLSU</h6>
                    <p>Technology/IP created by SLSU personnel shall be owned by SLSU when it falls under the ownership provisions of the SLSU Intellectual Property Policy, including when it is:</p>
                    <ul>
                      <li>Created in the performance of regularly assigned duties, official functions, designation, Special Order, or project assignment;</li>
                      <li>Developed through the use of substantial SLSU resources;</li>
                      <li>Created pursuant to a commission, consultancy, research engagement, or other written agreement that vests ownership in SLSU;</li>
                      <li>Derived from SLSU-funded or applicable government-funded research or innovation activities where ownership is vested in SLSU;</li>
                      <li>Generated under a collaborative, sponsored, or jointly implemented project where the applicable agreement vests ownership in SLSU; or</li>
                      <li>Foreground IP or Serendipitous IP generated under a collaborative or sponsored research undertaking, unless the applicable agreement provides otherwise.</li>
                    </ul>

                    <h6>If Ownership Is Assigned by Creator</h6>
                    <p>The Creator may choose to assign ownership to SLSU or retain ownership, subject to the SLSU Intellectual Property Policy and applicable agreements.</p>
                    <p>The Creator agrees to execute a separate Deed of Assignment/Written Assignment Agreement in favor of SLSU. Upon valid assignment and acceptance, SLSU shall become the sole owner of the Technology/IP. The Creator shall retain the right to proper acknowledgment and applicable revenue or royalty benefits in accordance with the SLSU Intellectual Property Policy.</p>
                    <p>SLSU, through the ITSO, shall thereafter determine whether the IP shall be pursued for protection, development, or commercialization. Applicable IP transaction fees shall be handled in accordance with the SLSU IP Policy and applicable funding arrangements.</p>

                    <h6>If Ownership Is Retained by the Creator</h6>
                    <p>The Creator may retain full ownership and control of the Technology/IP and shall be responsible for pursuing its protection, including filing, prosecution, maintenance, and other related expenses. The ITSO shall provide technical guidance and facilitation for IP protection and processing, subject to the applicable ITSO service fees.</p>
                    <p class="mb-0">The Creator shall retain full authority over the Technology/IP and shall be entitled to the corresponding royalties or other income from its commercialization. The Creator shall shoulder all IP transaction fees and ITSO service fees applicable to the requested services. A detailed list of typical IP transaction fees can be found <a href="https://drive.google.com/file/d/138zRWPEofZJCc5TzJvJ4hwUx8UPM-cPX/view" target="_blank" rel="noopener noreferrer">here</a>.</p>
                  </div>
                </details>
              </div>
              <div class="col-md-8">
                <label class="form-label" for="ownership_declaration">Ownership selection</label>
                <select class="form-select" id="ownership_declaration" name="ownership_declaration" required>
                  <option value="">Choose an ownership option</option>
                  <option value="slsu_owned" @selected(old('ownership_declaration')==='slsu_owned')>SLSU-owned (auto-vested, no assignment required)</option>
                  <option value="assigned_to_slsu" @selected(old('ownership_declaration')==='assigned_to_slsu')>Assign to SLSU (Deed of Assignment required)</option>
                  <option value="creator_retained" @selected(old('ownership_declaration')==='creator_retained')>Creator retained (creator pays ITSO fees)</option>
                </select>
              </div>
              <div class="col-12 {{ old('ownership_declaration') === 'assigned_to_slsu' && old('university_relationship') === 'student' ? '' : 'd-none' }}" id="student-assignment-notice">
                <div class="alert alert-warning mb-0"><strong>Student inventor:</strong> Please visit the IP Office to complete your Deed of Assignment. A signed copy must be uploaded below before submitting.</div>
              </div>
              <div class="col-12 {{ old('ownership_declaration') === 'assigned_to_slsu' ? '' : 'd-none' }}" id="deed-assignment-field">
                <label class="form-label" for="attachment-deed-assignment">Signed Deed of Assignment (PDF) <span class="text-danger">*</span></label>
                <input class="form-control pdf-upload" id="attachment-deed-assignment" name="attachments[deed_assignment]" type="file" accept="application/pdf,.pdf" data-file-label="Deed of Assignment" @required(old('ownership_declaration') === 'assigned_to_slsu')>
                <div class="form-text">Maximum 15 MB. If you are a student, obtain this document from the IP Office first.</div>
              </div>
            </div>
          </section>
        </div>

        <div class="col-12">
          <section class="disclosure-form-section bg-white shadow-sm" aria-labelledby="technical-description-heading">
            <div class="disclosure-form-section__header d-flex flex-wrap justify-content-between align-items-center gap-3">
              <h4 class="mb-0 fw-bold" id="technical-description-heading">Technical / Creative Description</h4>
              <button class="btn btn-outline-primary" type="button" id="add-technology" data-bs-toggle="modal" data-bs-target="#technology-modal"><i class="icon-base ri ri-add-line me-1"></i>Add Technology</button>
            </div>
            <div class="alert alert-info mb-4">This technical disclosure is collected for evaluation and record-keeping. Complete each narrative field with specific, factual details.</div>
            <div class="table-responsive">
              <table class="table table-hover align-middle technology-table mb-0">
                <thead>
                  <tr>
                    <th scope="col">Technology Title</th>
                    @foreach ($technologyTableColumns as $label)
                    <th scope="col" class="text-center">{{ $label }}</th>
                    @endforeach
                    <th scope="col" class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="technologies-table-body"></tbody>
              </table>
            </div>
            <div id="technology-hidden-fields" class="d-none"></div>
            <p class="form-text mb-0 mt-3">Add up to two technologies under this research proposal.</p>
          </section>
        </div>

        <div class="col-12">
          <section class="disclosure-form-section bg-white shadow-sm" aria-labelledby="supporting-materials-heading">
            <div class="disclosure-form-section__header"><h4 class="mb-0 fw-bold" id="supporting-materials-heading">Drawings, Photographs, Code, Models, or Other Supporting Materials</h4></div>
            <p class="text-body-secondary">All uploads must be PDF files (maximum 15 MB each). Put photos, drawings, code, or model images into a PDF before uploading. Typed signatures are used instead of image-signature uploads.</p>
            <div class="row g-4">
              <div class="col-md-6"><label class="form-label" for="attachment-sample-photo">Sample photo of the product (PDF) <span class="text-danger">*</span></label><input class="form-control pdf-upload" id="attachment-sample-photo" name="attachments[sample_photo]" type="file" accept="application/pdf,.pdf" data-file-label="Sample photo" required></div>
              <div class="col-md-6 d-none" id="additional-sheet-field"><label class="form-label" for="attachment-additional-sheet">Additional signed creator sheet (PDF) <span class="text-danger">*</span></label><input class="form-control pdf-upload" id="attachment-additional-sheet" name="attachments[additional_signed_sheet]" type="file" accept="application/pdf,.pdf" data-file-label="Additional signed creator sheet"></div>
              <div class="col-md-6"><label class="form-label" for="file_drive_link">File / Drive link <span class="text-body-secondary">(optional)</span></label><input class="form-control" id="file_drive_link" name="file_drive_link" type="url" value="{{ old('file_drive_link') }}" maxlength="2048" placeholder="https://"></div>
              <div class="col-md-6"><label class="form-label" for="attachment-count">Number of attachments</label><input class="form-control" id="attachment-count" value="0" readonly aria-live="polite"><div class="form-text">Automatically counted from the selected PDF files.</div></div>
            </div>
          </section>
        </div>

        <div class="col-12">
          <section class="disclosure-form-section bg-white shadow-sm" aria-labelledby="costing-heading">
            <div class="disclosure-form-section__header d-flex flex-wrap justify-content-between align-items-center gap-3">
              <h4 class="mb-0 fw-bold" id="costing-heading">Costing of Materials and Resources</h4>
              <button class="btn btn-outline-primary" type="button" id="add-material" data-bs-toggle="modal" data-bs-target="#material-modal"><i class="icon-base ri ri-add-line me-1"></i>Add item</button>
            </div>
            <div id="materials-list" class="table-responsive">
              <table class="table table-hover align-middle materials-table mb-0">
                <thead>
                  <tr>
                    <th scope="col">Item / Material</th>
                    <th scope="col">Description / Specification</th>
                    <th scope="col">Qty.</th>
                    <th scope="col">Unit Cost</th>
                    <th scope="col">Line Total</th>
                    <th scope="col">Purpose / Remarks</th>
                    <th scope="col" class="text-end">Action</th>
                  </tr>
                </thead>
                <tbody id="materials-table-body"></tbody>
              </table>
            </div>
            <div class="row justify-content-end mt-3"><div class="col-md-5 col-lg-4"><label class="form-label fw-semibold" for="total-development-cost">Total Estimated Development Cost (₱)</label><input class="form-control fw-semibold" id="total-development-cost" value="0.00" readonly aria-live="polite"></div></div>
          </section>
        </div>

        <div class="col-12">
          <section class="disclosure-form-section bg-white shadow-sm" aria-labelledby="declaration-heading">
            <div class="disclosure-form-section__header"><h4 class="mb-0 fw-bold" id="declaration-heading">Declaration &amp; Receipt</h4></div>
            <p class="mb-4">Each creator's typed signature, confirmation, and signing date are collected with their contribution above. The receipt details below are completed by ITSO personnel when the form is received.</p>
            <div class="row g-4">
              <div class="col-md-6"><label class="form-label" for="received-by-display">Received by (ITSO Personnel)</label><input class="form-control" id="received-by-display" value="To be completed by ITSO personnel" disabled></div>
              <div class="col-md-6"><label class="form-label" for="date-received-display">Date received</label><input class="form-control" id="date-received-display" value="To be completed upon receipt" disabled></div>
            </div>
          </section>
        </div>
        <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <button class="btn btn-outline-secondary" type="button" id="import-idf-button"><i class="icon-base ri ri-upload-2-line me-1"></i>Import IDF</button>
            <input class="visually-hidden" type="file" id="import-idf-file" accept=".json,application/json" aria-label="Import saved IDF JSON file">
            <span class="small text-body-secondary" id="idf-draft-save-status" role="status" aria-live="polite"></span>
          </div>
          <div class="d-flex flex-wrap justify-content-end gap-2">
            <button class="btn btn-outline-primary" type="button" id="save-idf-submission"><i class="icon-base ri ri-download-2-line me-1"></i>Save submission</button>
            <button class="btn btn-primary" type="submit"><i class="icon-base ri ri-send-plane-line me-1"></i>Submit disclosure</button>
          </div>
        </div>
      </div>
    </form>
  </div>

  <div class="modal fade" id="material-modal" tabindex="-1" aria-labelledby="material-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable idf-modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="material-modal-title">Add Cost Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3" id="material-modal-fields">
            <div class="col-md-6"><label class="form-label" for="material-modal-item">Item / Material</label><input class="form-control" id="material-modal-item" data-material-modal-field="item" maxlength="255" required></div>
            <div class="col-md-6"><label class="form-label" for="material-modal-description">Description / Specification</label><input class="form-control" id="material-modal-description" data-material-modal-field="description" maxlength="2000" required></div>
            <div class="col-md-4"><label class="form-label" for="material-modal-quantity">Quantity</label><input class="form-control" id="material-modal-quantity" data-material-modal-field="quantity" type="number" min="0.01" step="0.01" required></div>
            <div class="col-md-4"><label class="form-label" for="material-modal-unit-cost">Unit Cost (₱)</label><input class="form-control" id="material-modal-unit-cost" data-material-modal-field="unit_cost" type="number" min="0" step="0.01" required></div>
            <div class="col-md-4"><label class="form-label" for="material-modal-purpose">Purpose / Remarks</label><input class="form-control" id="material-modal-purpose" data-material-modal-field="purpose" maxlength="2000"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="save-material"><i class="icon-base ri ri-check-line me-1"></i>Save Item</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="technology-modal" tabindex="-1" aria-labelledby="technology-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable idf-modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="technology-modal-title">Add Technology</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-info mb-3" role="note">
            <i class="icon-base ri ri-information-line me-1" aria-hidden="true"></i>
            For each narrative, enter the text or upload a PDF (maximum 15 MB).
          </div>
          <div class="row g-3" id="technology-modal-fields">
            <div class="col-12"><label class="form-label" for="technology-modal-title-input">Title of Invention / Technology <span class="text-danger">*</span></label><input class="form-control" id="technology-modal-title-input" data-technology-field="technology_title" maxlength="500" required placeholder="Use a specific technical or creative title."></div>
            @foreach ([
              'abstract_summary' => ['Abstract / Summary', 'Describe the problem, core solution, and principal features.'],
              'technical_field' => ['Technical / Creative Field', 'State the general field and specific field.'],
              'background_summary' => ['Background / Problem', 'Describe the current situation, limitations or gaps, and differences from existing knowledge.'],
              'invention_summary' => ['Summary of the Invention / Innovation', 'Explain the technical solution, objectives, essential features, and advantages.'],
              'detailed_description' => ['Detailed Description', 'Include components, steps, procedures, parameters, conditions, instruments, tests, results, and processes.'],
              'examples_results' => ['Examples / Experimental Results / Validation', 'Describe test procedures, results, optimization, validation, and reduction-to-practice evidence.'],
            ] as $field => [$label, $placeholder])
            <div class="col-12 technology-narrative-field" data-narrative-field="{{ $field }}">
              <label class="form-label" for="technology-modal-{{ $field }}">{{ $label }} <span class="text-danger">*</span></label>
              <textarea class="form-control mb-2" id="technology-modal-{{ $field }}" data-technology-field="{{ $field }}" rows="3" placeholder="{{ $placeholder }}"></textarea>
              <div class="technology-upload-control" data-technology-upload-slot="{{ $field }}">
                <input class="form-control pdf-upload" id="technology-modal-file-{{ $field }}" data-technology-file="{{ $field }}" type="file" accept="application/pdf,.pdf" data-file-label="{{ $label }}" aria-describedby="technology-file-status-{{ $field }}">
                <button class="btn btn-icon btn-text-danger d-none technology-remove-file" type="button" data-remove-technology-file="{{ $field }}" aria-label="Remove {{ $label }} PDF" title="Remove PDF">
                  <i class="icon-base ri ri-delete-bin-line" aria-hidden="true"></i>
                </button>
              </div>
              <div class="technology-upload-status">
                <span class="form-text" id="technology-file-status-{{ $field }}" data-existing-technology-file="{{ $field }}"></span>
              </div>
            </div>
            @endforeach
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="save-technology"><i class="icon-base ri ri-check-line me-1"></i>Save Technology</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="technology-file-preview-modal" tabindex="-1" aria-labelledby="technology-file-preview-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable idf-modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="technology-file-preview-title">Narrative PDF</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body p-0">
          <iframe id="technology-file-preview-frame" class="technology-file-preview-frame" title="Narrative PDF preview"></iframe>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="technology-narrative-view-modal" tabindex="-1" aria-labelledby="technology-narrative-view-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable idf-modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="technology-narrative-view-title">Narrative</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4" id="technology-narrative-view-content"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="creator-modal" tabindex="-1" aria-labelledby="creator-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable idf-modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="creator-modal-title">Add Creator</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3" id="creator-modal-fields">
            <div class="col-md-4"><label class="form-label" for="creator-modal-last-name">Last Name</label><input class="form-control" id="creator-modal-last-name" data-creator-field="last_name" maxlength="100" required></div>
            <div class="col-md-4"><label class="form-label" for="creator-modal-first-name">First Name</label><input class="form-control" id="creator-modal-first-name" data-creator-field="first_name" maxlength="100" required></div>
            <div class="col-md-4"><label class="form-label" for="creator-modal-middle-initial">Middle Initial <span class="text-body-secondary">(optional)</span></label><input class="form-control" id="creator-modal-middle-initial" data-creator-field="middle_initial" maxlength="1" placeholder="A"></div>
            <div class="col-md-4"><label class="form-label" for="creator-modal-suffix">Suffix <span class="text-body-secondary">(optional)</span></label><input class="form-control" id="creator-modal-suffix" data-creator-field="suffix" maxlength="20" placeholder="Jr., III"></div>
            <div class="col-md-6"><label class="form-label" for="creator-modal-email">Email Address</label><input class="form-control" id="creator-modal-email" data-creator-field="email" type="email" maxlength="255" required></div>
            <div class="col-md-6"><label class="form-label" for="creator-modal-position">Position / Affiliation</label><input class="form-control" id="creator-modal-position" data-creator-field="position_affiliation" maxlength="500" required></div>
            <div class="col-12"><label class="form-label" for="creator-modal-contribution">Specific Intellectual / Technical Contribution</label><textarea class="form-control" id="creator-modal-contribution" data-creator-field="technical_contribution" rows="2" required></textarea></div>
            <div class="col-md-4"><label class="form-label" for="creator-modal-percent">Declared Contribution (%)</label><input class="form-control" id="creator-modal-percent" data-creator-field="contribution_percent" type="number" min="0.01" max="100" step="0.01" required></div>
            <fieldset class="col-12 creator-nature-fieldset">
              <legend class="form-label">Nature of contribution <span class="text-danger">*</span></legend>
              <div class="row g-2">
                @foreach (['conception' => 'Conception', 'identification_essential_element' => 'Identification of an essential element', 'technical_feature' => 'Technical feature', 'experimental_refinement' => 'Experimental refinement', 'reduction_to_practice' => 'Reduction to practice', 'testing_validation' => 'Testing / validation', 'optimization' => 'Optimization', 'other_substantive' => 'Other substantive contribution'] as $nature => $label)
                <div class="col-sm-6 col-lg-6"><div class="form-check"><input class="form-check-input nature-checkbox" data-nature="{{ $nature }}" id="creator-modal-nature-{{ $nature }}" type="checkbox" value="{{ $nature }}"><label class="form-check-label" for="creator-modal-nature-{{ $nature }}">{{ $label }}</label></div></div>
                @endforeach
              </div>
            </fieldset>
            <div class="col-12 d-none" data-other-contribution-field><label class="form-label" for="creator-modal-other">Specify Other Substantive Contribution</label><input class="form-control" id="creator-modal-other" data-creator-field="contribution_other" maxlength="500"></div>
            <div class="col-md-6"><label class="form-label" for="creator-modal-signature">Typed Signature</label><input class="form-control" id="creator-modal-signature" data-creator-field="signature_name" maxlength="255" placeholder="Type full legal name" required></div>
            <div class="col-md-6"><label class="form-label" for="creator-modal-date">Date Signed</label><input class="form-control" id="creator-modal-date" data-creator-field="declaration_date" type="date" required></div>
            <div class="col-12"><div class="form-check"><input class="form-check-input" id="creator-modal-confirmed" data-creator-field="declaration_confirmed" type="checkbox" value="1" required><label class="form-check-label" for="creator-modal-confirmed">I confirm that my information and contribution are true and correct, and that I have read and understood the ownership provisions.</label></div></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="save-creator"><i class="icon-base ri ri-check-line me-1"></i>Save Creator</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="creator-view-modal" tabindex="-1" aria-labelledby="creator-view-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable idf-modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="creator-view-modal-title">Creator Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4" id="creator-view-fields">
            @foreach ([
              'last_name' => 'Last Name',
              'first_name' => 'First Name',
              'middle_initial' => 'Middle Initial',
              'suffix' => 'Suffix',
              'email' => 'Email Address',
              'position_affiliation' => 'Position / Affiliation',
              'contribution_percent' => 'Declared Contribution',
              'technical_contribution' => 'Specific Intellectual / Technical Contribution',
              'contribution_nature' => 'Nature of Contribution',
              'contribution_other' => 'Other Substantive Contribution',
              'signature_name' => 'Typed Signature',
              'declaration_date' => 'Date Signed',
              'declaration_confirmed' => 'Declaration Confirmation',
            ] as $field => $label)
            <div class="{{ in_array($field, ['technical_contribution', 'contribution_nature', 'contribution_other', 'declaration_confirmed'], true) ? 'col-12' : 'col-md-6' }}" data-creator-view-row="{{ $field }}">
              <span class="form-label text-uppercase small fw-semibold d-block">{{ $label }}</span>
              <div class="form-control bg-lighter creator-view-value" data-creator-view-value="{{ $field }}" aria-readonly="true"></div>
            </div>
            @endforeach
          </div>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button></div>
      </div>
    </div>
  </div>

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
<script src="{{ asset('user/js/user-submission-status.js') }}" defer></script>
@endif

@if ($page === 'form')
<script>
  document.addEventListener('click', async (event) => {
    if (!(event.target instanceof Element) || !event.target.closest('#save-technology')) return;
    event.preventDefault();
    event.stopPropagation();
    try {
      await requestTechnologySave();
    } catch (error) {
      console.error('Technology save handler failed:', error);
      if (typeof window.Swal?.fire === 'function') {
        await window.Swal.fire({
          title: 'Could not save technology',
          text: error instanceof Error ? error.message : 'An unexpected error interrupted the save action.',
          icon: 'error',
          confirmButtonText: 'OK'
        });
      }
    }
  });

  const disclosureForm = document.querySelector('#new-disclosure form');
  const relationshipField = disclosureForm.querySelector('[name="university_relationship"]');
  const otherRelationshipField = document.querySelector('#other-relationship-field');
  const otherRelationshipInput = disclosureForm.querySelector('[name="other_relationship"]');
  const ownershipField = disclosureForm.querySelector('[name="ownership_declaration"]');
  const deedField = document.querySelector('#deed-assignment-field');
  const deedInput = disclosureForm.querySelector('[name="attachments[deed_assignment]"]');
  const studentAssignmentNotice = document.querySelector('#student-assignment-notice');
  const inventorsTableBody = document.querySelector('#inventors-table-body');
  const inventorHiddenFields = document.querySelector('#inventor-hidden-fields');
  const creatorModal = document.querySelector('#creator-modal');
  const creatorModalTitle = document.querySelector('#creator-modal-title');
  const creatorModalFields = document.querySelector('#creator-modal-fields');
  const saveCreatorButton = document.querySelector('#save-creator');
  const creatorViewModal = document.querySelector('#creator-view-modal');
  const addInventorButton = document.querySelector('#add-inventor');
  const creatorFieldNames = [
    'last_name',
    'first_name',
    'middle_initial',
    'suffix',
    'email',
    'position_affiliation',
    'technical_contribution',
    'contribution_percent',
    'contribution_other',
    'signature_name',
    'declaration_date',
    'declaration_confirmed'
  ];
  const natureOptions = [...creatorModal.querySelectorAll('.nature-checkbox')].map((checkbox) => ({
    value: checkbox.value,
    label: creatorModal.querySelector(`label[for="${checkbox.id}"]`).textContent.trim()
  }));
  let creators = @json($inventorRows);
  let editingCreatorIndex = null;
  const materialsTableBody = document.querySelector('#materials-table-body');
  const materialModal = document.querySelector('#material-modal');
  const materialModalTitle = document.querySelector('#material-modal-title');
  const materialModalFields = document.querySelector('#material-modal-fields');
  const saveMaterialButton = document.querySelector('#save-material');
  const addMaterialButton = document.querySelector('#add-material');
  const materialFieldNames = ['item', 'description', 'quantity', 'unit_cost', 'purpose'];
  let materials = @json(array_values($materialRows));
  let editingMaterialIndex = null;
  const technologyNarratives = @json($narrativeFields);
  const technologyNarrativeLabels = @json($narrativeLabels);
  const technologyTableColumns = @json($technologyTableColumns);
  const technologiesTableBody = document.querySelector('#technologies-table-body');
  const technologyHiddenFields = document.querySelector('#technology-hidden-fields');
  const technologyModal = document.querySelector('#technology-modal');
  const technologyModalTitle = document.querySelector('#technology-modal-title');
  const technologyModalFields = document.querySelector('#technology-modal-fields');
  const technologyFilePreviewModal = document.querySelector('#technology-file-preview-modal');
  const technologyFilePreviewTitle = document.querySelector('#technology-file-preview-title');
  const technologyFilePreviewFrame = document.querySelector('#technology-file-preview-frame');
  const technologyNarrativeViewModal = document.querySelector('#technology-narrative-view-modal');
  const technologyNarrativeViewTitle = document.querySelector('#technology-narrative-view-title');
  const technologyNarrativeViewContent = document.querySelector('#technology-narrative-view-content');
  const saveTechnologyButton = document.querySelector('#save-technology');
  const addTechnologyButton = document.querySelector('#add-technology');
  let technologies = @json($technologyRows).map((technology) => ({
    ...technology,
    uploadInputs: {},
    uploadNames: {}
  }));
  let editingTechnologyIndex = null;
  const removedTechnologyFiles = new Set();
  let technologyPreviewUrl = null;
  const idfDraftKey = 'idf-draft-user-{{ auth()->id() }}';
  const idfDraftDatabaseName = 'ipms-idf-drafts';
  const idfSubmissionRecoveryKey = 'ipms-idf-submit-recovery-user-{{ auth()->id() }}';
  const idfDraftVersion = 1;
  const idfDraftStatus = document.querySelector('#idf-draft-save-status');
  const idfDraftImportInput = document.querySelector('#import-idf-file');
  let idfDraftDatabasePromise = null;
  let idfDraftSaveTimer = null;
  let idfDraftSaveQueue = Promise.resolve();
  let idfDraftErrorShown = false;
  let idfDraftUserInteracted = false;

  async function showDraftStorageError(title, error) {
    idfDraftStatus.textContent = 'Local draft storage is unavailable.';
    console.error(title, error);
    if (idfDraftErrorShown) return;
    idfDraftErrorShown = true;
    await window.Swal.fire({
      title,
      text: error instanceof Error ? error.message : 'Browser storage could not be accessed.',
      icon: 'error',
      confirmButtonText: 'OK'
    });
  }

  function openIdfDraftDatabase() {
    if (idfDraftDatabasePromise) return idfDraftDatabasePromise;
    idfDraftDatabasePromise = new Promise((resolve, reject) => {
      const request = indexedDB.open(idfDraftDatabaseName, 1);
      request.onupgradeneeded = () => {
        const database = request.result;
        if (!database.objectStoreNames.contains('drafts')) {
          database.createObjectStore('drafts', { keyPath: 'id' });
        }
      };
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error || new Error('Could not open local draft storage.'));
    });
    return idfDraftDatabasePromise;
  }

  function readFileAsDraftData(file) {
    if (!file) return Promise.resolve(null);
    return file.arrayBuffer().then(data => ({
      name: file.name,
      type: file.type || 'application/pdf',
      lastModified: file.lastModified,
      data
    }));
  }

  function saveDraftRecord(record) {
    return openIdfDraftDatabase().then(database => new Promise((resolve, reject) => {
      const transaction = database.transaction('drafts', 'readwrite');
      transaction.objectStore('drafts').put(record);
      transaction.oncomplete = resolve;
      transaction.onerror = () => reject(transaction.error || new Error('Could not save the local IDF draft.'));
      transaction.onabort = () => reject(transaction.error || new Error('Local IDF draft save was interrupted.'));
    }));
  }

  function clearDraftRecord() {
    return openIdfDraftDatabase().then(database => new Promise((resolve, reject) => {
      const transaction = database.transaction('drafts', 'readwrite');
      transaction.objectStore('drafts').delete(idfDraftKey);
      transaction.oncomplete = resolve;
      transaction.onerror = () => reject(transaction.error || new Error('Could not clear the local IDF draft.'));
      transaction.onabort = () => reject(transaction.error || new Error('Local IDF draft cleanup was interrupted.'));
    }));
  }

  function collectFormFields() {
    const fields = {};
    disclosureForm.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
      if (field.type === 'file' || field.type === 'hidden') return;
      if (field.closest('#inventor-hidden-fields, #technology-hidden-fields')) return;
      if (field.type === 'checkbox' || field.type === 'radio') {
        if (field.checked) fields[field.name] = field.value;
        return;
      }
      fields[field.name] = field.value;
    });
    return fields;
  }

  async function createIdfDraftSnapshot() {
    const staticFiles = {};
    for (const input of disclosureForm.querySelectorAll('input[type="file"][name^="attachments["]')) {
      if (input.files?.[0]) staticFiles[input.name] = await readFileAsDraftData(input.files[0]);
    }

    const savedCreators = JSON.parse(JSON.stringify(creators));
    if (creatorModal.classList.contains('show')) {
      const creatorDraft = getCreatorModalValues();
      const hasCreatorDraft = Object.entries(creatorDraft)
        .some(([key, value]) => key === 'contribution_nature' ? value.length > 0 : Boolean(value));
      if (hasCreatorDraft) {
        if (editingCreatorIndex === null) savedCreators.push(creatorDraft);
        else savedCreators[editingCreatorIndex] = creatorDraft;
      }
    }

    const savedMaterials = JSON.parse(JSON.stringify(materials));
    if (materialModal.classList.contains('show')) {
      const materialDraft = getMaterialModalValues();
      if (Object.values(materialDraft).some(Boolean)) {
        if (editingMaterialIndex === null) savedMaterials.push(materialDraft);
        else savedMaterials[editingMaterialIndex] = materialDraft;
      }
    }

    const savedTechnologies = [];
    for (const technology of technologies) {
      const files = {};
      for (const narrative of technologyNarratives) {
        const fileInput = technology.uploadInputs?.[narrative];
        const file = fileInput?.files?.[0];
        if (file) files[narrative] = await readFileAsDraftData(file);
      }
      savedTechnologies.push({
        technology_title: technology.technology_title || '',
        ...Object.fromEntries(technologyNarratives.map(narrative => [narrative, technology[narrative] || ''])),
        files
      });
    }

    if (technologyModal.classList.contains('show')) {
      const { technology, uploadInputs } = getTechnologyModalValues();
      const technologyDraft = { ...technology, files: {} };
      const previousTechnology = editingTechnologyIndex === null
        ? null
        : technologies[editingTechnologyIndex];
      for (const narrative of technologyNarratives) {
        const selectedFile = uploadInputs[narrative]?.files?.[0];
        const previousFile = previousTechnology?.uploadInputs?.[narrative]?.files?.[0];
        const file = selectedFile || (
          !removedTechnologyFiles.has(narrative) ? previousFile : null
        );
        if (file) technologyDraft.files[narrative] = await readFileAsDraftData(file);
      }
      const hasTechnologyDraft = technologyDraft.technology_title
        || technologyNarratives.some(narrative =>
          technologyDraft[narrative] || technologyDraft.files[narrative]
        );
      if (hasTechnologyDraft) {
        if (editingTechnologyIndex === null) savedTechnologies.push(technologyDraft);
        else savedTechnologies[editingTechnologyIndex] = technologyDraft;
      }
    }

    return {
      format: 'ipms-idf-draft',
      version: idfDraftVersion,
      fields: collectFormFields(),
      creators: savedCreators,
      materials: savedMaterials.filter(material =>
        materialFieldNames.some(field => String(material[field] ?? '').trim() !== '')
      ),
      technologies: savedTechnologies,
      staticFiles
    };
  }

  function queueDraftSave() {
    idfDraftSaveQueue = idfDraftSaveQueue.then(async () => {
      const data = await createIdfDraftSnapshot();
      await saveDraftRecord({ id: idfDraftKey, data, savedAt: new Date().toISOString() });
      idfDraftStatus.textContent = `Draft saved locally at ${new Date().toLocaleTimeString()}.`;
      return true;
    }).catch((error) => {
      showDraftStorageError('Could not save draft', error);
      return false;
    });
    return idfDraftSaveQueue;
  }

  function scheduleDraftSave() {
    window.clearTimeout(idfDraftSaveTimer);
    idfDraftStatus.textContent = 'Saving draft…';
    idfDraftSaveTimer = window.setTimeout(queueDraftSave, 500);
  }

  window.saveIdfDraftBeforeSubmit = async () => {
    window.clearTimeout(idfDraftSaveTimer);
    await queueDraftSave();
  };
  window.markIdfSubmissionPending = () => {
    sessionStorage.setItem(idfSubmissionRecoveryKey, 'true');
  };

  function makeFileInput(fileData) {
    if (!fileData?.data) return null;
    const file = new File([fileData.data], fileData.name, {
      type: fileData.type || 'application/pdf',
      lastModified: fileData.lastModified || Date.now()
    });
    const input = document.createElement('input');
    input.type = 'file';
    const transfer = new DataTransfer();
    transfer.items.add(file);
    input.files = transfer.files;
    return input;
  }

  function restoreDraftFields(fields) {
    disclosureForm.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
      if (field.type === 'file' || field.type === 'hidden' || field.disabled) return;
      if (field.closest('#inventor-hidden-fields, #technology-hidden-fields')) return;
      if (!Object.prototype.hasOwnProperty.call(fields, field.name)) return;
      if (field.type === 'checkbox' || field.type === 'radio') {
        field.checked = fields[field.name] === field.value;
      } else {
        field.value = fields[field.name];
      }
    });
  }

  async function restoreIdfDraft(data) {
    if (!data || data.format !== 'ipms-idf-draft' || data.version !== idfDraftVersion) {
      throw new Error('This file is not a supported IPMS IDF draft.');
    }
    if (!data.fields || !Array.isArray(data.creators) || !Array.isArray(data.materials)
      || !Array.isArray(data.technologies) || !data.staticFiles) {
      throw new Error('The IDF draft is incomplete or damaged.');
    }

    restoreDraftFields(data.fields);
    creators = data.creators;
    materials = data.materials.filter(material =>
      materialFieldNames.some(field => String(material[field] ?? '').trim() !== '')
    );
    technologies = data.technologies.map((technology) => {
      const uploadInputs = {};
      const uploadNames = {};
      Object.entries(technology.files || {}).forEach(([narrative, fileData]) => {
        if (!technologyNarratives.includes(narrative)) return;
        const input = makeFileInput(fileData);
        if (!input) return;
        uploadInputs[narrative] = input;
        uploadNames[narrative] = fileData.name;
      });
      return { ...technology, uploadInputs, uploadNames };
    });

    for (const input of disclosureForm.querySelectorAll('input[type="file"][name^="attachments["]')) {
      input.value = '';
      const fileData = data.staticFiles[input.name];
      const restoredInput = makeFileInput(fileData);
      if (restoredInput) input.files = restoredInput.files;
    }

    updateRelationshipFields();
    updateOwnershipFields();
    updateCreatorRows();
    updateCosts();
    renderTechnologies();
    updateAttachmentCount();
    scheduleDraftSave();
  }

  function encodeFileData(fileData) {
    if (!fileData) return Promise.resolve(null);
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => {
        const binary = new Uint8Array(reader.result);
        let encoded = '';
        for (let offset = 0; offset < binary.length; offset += 0x8000) {
          encoded += String.fromCharCode(...binary.subarray(offset, offset + 0x8000));
        }
        resolve({
          name: fileData.name,
          type: fileData.type,
          lastModified: fileData.lastModified,
          base64: btoa(encoded)
        });
      };
      reader.onerror = () => reject(reader.error || new Error('Could not prepare an uploaded file for export.'));
      reader.readAsArrayBuffer(new Blob([fileData.data]));
    });
  }

  async function createPortableIdfFile() {
    const snapshot = await createIdfDraftSnapshot();
    for (const [name, fileData] of Object.entries(snapshot.staticFiles)) {
      snapshot.staticFiles[name] = await encodeFileData(fileData);
    }
    for (const technology of snapshot.technologies) {
      for (const [narrative, fileData] of Object.entries(technology.files)) {
        technology.files[narrative] = await encodeFileData(fileData);
      }
    }
    return snapshot;
  }

  async function savePortableIdfFile(snapshot, filename) {
    const file = new Blob([JSON.stringify(snapshot, null, 2)], { type: 'application/json' });
    if (typeof window.showSaveFilePicker === 'function') {
      const handle = await window.showSaveFilePicker({
        suggestedName: filename,
        types: [{
          description: 'IPMS IDF draft',
          accept: { 'application/json': ['.json'] }
        }]
      });
      const writable = await handle.createWritable();
      await writable.write(file);
      await writable.close();
      return 'saved';
    }

    const link = document.createElement('a');
    link.href = URL.createObjectURL(file);
    link.download = filename;
    link.click();
    window.setTimeout(() => URL.revokeObjectURL(link.href), 1000);
    return 'download-started';
  }

  function decodePortableFile(fileData) {
    if (!fileData || typeof fileData.base64 !== 'string' || typeof fileData.name !== 'string') {
      throw new Error('The IDF draft contains an invalid uploaded file.');
    }
    const binary = atob(fileData.base64);
    const bytes = new Uint8Array(binary.length);
    for (let index = 0; index < binary.length; index += 1) {
      bytes[index] = binary.charCodeAt(index);
    }
    return { ...fileData, data: bytes.buffer };
  }

  function decodePortableDraft(data) {
    Object.entries(data.staticFiles || {}).forEach(([name, fileData]) => {
      data.staticFiles[name] = decodePortableFile(fileData);
    });
    data.technologies.forEach((technology) => {
      Object.entries(technology.files || {}).forEach(([narrative, fileData]) => {
        technology.files[narrative] = decodePortableFile(fileData);
      });
    });
    return data;
  }

  function resetCreatorModal() {
    editingCreatorIndex = null;
    creatorModalTitle.textContent = 'Add Creator';
    creatorModalFields.querySelectorAll('[data-creator-field]').forEach((field) => {
      if (field.type === 'checkbox') field.checked = false;
      else field.value = '';
    });
    creatorModal.querySelectorAll('.nature-checkbox').forEach((checkbox) => {
      checkbox.checked = false;
      checkbox.required = false;
    });
    updateModalOtherContribution();
    updateNatureRequirement();
  }

  function updateNatureRequirement() {
    const natureCheckboxes = [...creatorModal.querySelectorAll('.nature-checkbox')];
    const selected = natureCheckboxes.some((checkbox) => checkbox.checked);
    natureCheckboxes.forEach((checkbox, index) => {
      checkbox.required = !selected && index === 0;
    });
  }

  function updateModalOtherContribution() {
    const otherSelected = creatorModal.querySelector('[data-nature="other_substantive"]').checked;
    const wrapper = creatorModal.querySelector('[data-other-contribution-field]');
    const input = creatorModal.querySelector('[data-creator-field="contribution_other"]');
    wrapper.classList.toggle('d-none', !otherSelected);
    input.disabled = !otherSelected;
    input.required = otherSelected;
    if (!otherSelected) input.value = '';
  }

  function renderCreatorTable() {
    inventorsTableBody.replaceChildren();
    if (creators.length === 0) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.colSpan = 5;
      cell.className = 'text-center text-body-secondary py-4';
      cell.textContent = 'No creators have been added yet.';
      row.append(cell);
      inventorsTableBody.append(row);
    }

    creators.forEach((creator, index) => {
      const row = document.createElement('tr');
      const fullName = creatorFullName(creator);
      const tableName = creatorFullName(creator, creator.last_name?.toLocaleUpperCase());
      for (const value of [
        tableName,
        creator.position_affiliation,
        `${Number(creator.contribution_percent).toFixed(2)}%`,
        creator.technical_contribution
      ]) {
        const cell = document.createElement('td');
        cell.textContent = value || '';
        row.append(cell);
      }

      const actionCell = document.createElement('td');
      actionCell.colSpan = 1;
      actionCell.className = 'text-end';
      const dropdown = document.createElement('div');
      dropdown.className = 'dropdown';
      const menuButton = document.createElement('button');
      menuButton.type = 'button';
      menuButton.className = 'btn text-body-secondary p-0 creator-action-trigger';
      menuButton.setAttribute('data-bs-toggle', 'dropdown');
      menuButton.setAttribute('aria-label', `Actions for ${fullName}`);
      menuButton.setAttribute('aria-haspopup', 'true');
      menuButton.setAttribute('aria-expanded', 'false');
      menuButton.innerHTML = '<i class="icon-base ri ri-more-2-fill" aria-hidden="true"></i>';
      const menu = document.createElement('ul');
      menu.className = 'dropdown-menu dropdown-menu-end py-2';
      const viewItem = document.createElement('li');
      const viewButton = document.createElement('button');
      viewButton.type = 'button';
      viewButton.className = 'dropdown-item';
      viewButton.dataset.creatorAction = 'view';
      viewButton.dataset.creatorIndex = index;
      viewButton.innerHTML = '<i class="icon-base ri ri-eye-line me-2" aria-hidden="true"></i>View';
      viewItem.append(viewButton);
      menu.append(viewItem);
      for (const [action, label, icon] of [
        ['edit', 'Edit', 'ri-edit-line'],
        ['delete', 'Delete', 'ri-delete-bin-line']
      ]) {
        const item = document.createElement('li');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `dropdown-item${action === 'delete' ? ' text-danger' : ''}`;
        button.dataset.creatorAction = action;
        button.dataset.creatorIndex = index;
        button.innerHTML = `<i class="icon-base ri ${icon} me-2" aria-hidden="true"></i>${label}`;
        item.append(button);
        menu.append(item);
      }
      dropdown.append(menuButton, menu);
      actionCell.append(dropdown);
      row.append(actionCell);
      inventorsTableBody.append(row);
    });

    const contributionTotal = creators.reduce(
      (total, creator) => total + (Number(creator.contribution_percent) || 0),
      0
    );
    document.querySelector('#contribution-total').textContent = `${contributionTotal.toFixed(2)}%`;
    const requiresAdditionalSheet = creators.length > 6;
    const additionalSheetField = document.querySelector('#additional-sheet-field');
    const additionalSheetInput = disclosureForm.querySelector('[name="attachments[additional_signed_sheet]"]');
    additionalSheetField.classList.toggle('d-none', !requiresAdditionalSheet);
    additionalSheetInput.required = requiresAdditionalSheet;
    renderCreatorHiddenFields();
  }

  function renderCreatorHiddenFields() {
    inventorHiddenFields.replaceChildren();
    creators.forEach((creator, index) => {
      for (const key of creatorFieldNames) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `inventors[${index}][${key}]`;
        input.value = key === 'declaration_confirmed'
          ? (creator[key] ? '1' : '')
          : (creator[key] ?? '');
        inventorHiddenFields.append(input);
      }
      for (const nature of creator.contribution_nature ?? []) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `inventors[${index}][contribution_nature][]`;
        input.value = nature;
        inventorHiddenFields.append(input);
      }
    });
  }

  function getCreatorModalValues() {
    const creator = {};
    creatorModalFields.querySelectorAll('[data-creator-field]').forEach((field) => {
      creator[field.dataset.creatorField] = field.type === 'checkbox' ? field.checked : field.value.trim();
    });
    creator.contribution_nature = [...creatorModal.querySelectorAll('.nature-checkbox:checked')]
      .map((checkbox) => checkbox.value);
    if (!creator.contribution_nature.includes('other_substantive')) creator.contribution_other = '';
    return creator;
  }

  function creatorFullName(creator, lastName = creator.last_name) {
    const middleInitial = creator.middle_initial ? `${creator.middle_initial.replace(/\.$/, '')}.` : '';
    const firstAndSuffix = [creator.first_name, middleInitial, creator.suffix].filter(Boolean).join(' ');
    return lastName ? `${lastName}, ${firstAndSuffix}` : firstAndSuffix;
  }

  function openCreatorForEdit(index) {
    const creator = creators[index];
    if (!creator) return;
    resetCreatorModal();
    editingCreatorIndex = index;
    creatorModalTitle.textContent = 'Edit Creator';
    creatorFieldNames.forEach((key) => {
      const field = creatorModal.querySelector(`[data-creator-field="${key}"]`);
      if (field.type === 'checkbox') field.checked = creator[key] === true || creator[key] === '1' || creator[key] === 1;
      else field.value = creator[key] ?? '';
    });
    const selectedNatures = creator.contribution_nature ?? [];
    creatorModal.querySelectorAll('.nature-checkbox').forEach((checkbox) => {
      checkbox.checked = selectedNatures.includes(checkbox.value);
    });
    updateModalOtherContribution();
    updateNatureRequirement();
    showCreatorModal();
  }

  function openCreatorForView(index) {
    const creator = creators[index];
    if (!creator) return;
    creatorViewModal.querySelector('#creator-view-modal-title').textContent =
      `${creatorFullName(creator)} - Creator Details`;
    const natureLabels = new Map(natureOptions.map(({ value, label }) => [value, label]));
    const values = {
      last_name: creator.last_name,
      first_name: creator.first_name,
      middle_initial: creator.middle_initial,
      suffix: creator.suffix,
      email: creator.email,
      position_affiliation: creator.position_affiliation,
      contribution_percent: `${Number(creator.contribution_percent).toFixed(2)}%`,
      technical_contribution: creator.technical_contribution,
      contribution_nature: (creator.contribution_nature ?? [])
        .map((nature) => natureLabels.get(nature) ?? nature)
        .join(', '),
      contribution_other: creator.contribution_other,
      signature_name: creator.signature_name,
      declaration_date: creator.declaration_date,
      declaration_confirmed: creator.declaration_confirmed ? 'Confirmed' : 'Not confirmed'
    };
    Object.entries(values).forEach(([field, value]) => {
      const row = creatorViewModal.querySelector(`[data-creator-view-row="${field}"]`);
      const valueElement = creatorViewModal.querySelector(`[data-creator-view-value="${field}"]`);
      const hasValue = Boolean(value);
      row.classList.toggle('d-none', !hasValue);
      valueElement.textContent = value || 'Not provided';
    });
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'd-none';
    trigger.dataset.bsToggle = 'modal';
    trigger.dataset.bsTarget = '#creator-view-modal';
    document.body.append(trigger);
    trigger.click();
    trigger.remove();
  }

  function showCreatorModal() {
    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'd-none';
    trigger.dataset.bsToggle = 'modal';
    trigger.dataset.bsTarget = '#creator-modal';
    document.body.append(trigger);
    trigger.click();
    trigger.remove();
  }

  function updateCreatorRows() {
    renderCreatorTable();
  }

  function updateAttachmentCount() {
    const count = [...disclosureForm.querySelectorAll('input[type="file"]')]
      .filter((input) => input.files.length > 0).length;
    document.querySelector('#attachment-count').value = count;
  }

  function updateRelationshipFields() {
    const isOther = relationshipField.value === 'other_specify';
    otherRelationshipField.classList.toggle('d-none', !isOther);
    otherRelationshipInput.required = isOther;
    otherRelationshipInput.disabled = !isOther;
    if (!isOther) otherRelationshipInput.value = '';
  }

  function updateOwnershipFields() {
    const isAssignment = ownershipField.value === 'assigned_to_slsu';
    const isStudentAssignment = isAssignment && relationshipField.value === 'student';
    deedField.classList.toggle('d-none', !isAssignment);
    deedInput.required = isAssignment;
    deedInput.disabled = !isAssignment;
    studentAssignmentNotice.classList.toggle('d-none', !isStudentAssignment);
    if (!isAssignment) deedInput.value = '';
    updateAttachmentCount();
  }

  function updateCosts() {
    let grandTotal = 0;
    materialsTableBody.replaceChildren();
    materials.forEach((material, index) => {
      const quantity = Number(material.quantity) || 0;
      const unitCost = Number(material.unit_cost) || 0;
      const lineTotal = Math.round((quantity * unitCost + Number.EPSILON) * 100) / 100;
      grandTotal += lineTotal;
      const row = document.createElement('tr');
      row.className = 'material-row';
      row.dataset.materialIndex = String(index);
      [material.item, material.description, material.quantity, material.unit_cost, lineTotal.toFixed(2), material.purpose]
        .forEach((value) => {
          const cell = document.createElement('td');
          cell.textContent = value === null || value === undefined || value === '' ? '—' : String(value);
          row.append(cell);
        });
      materialFieldNames.forEach((fieldName) => {
        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = `materials[${index}][${fieldName}]`;
        hiddenField.value = material[fieldName] ?? '';
        row.cells[0].append(hiddenField);
      });
      const actionCell = document.createElement('td');
      actionCell.className = 'text-end text-nowrap';
      const dropdown = document.createElement('div');
      dropdown.className = 'dropdown';
      const menuButton = document.createElement('button');
      menuButton.type = 'button';
      menuButton.className = 'btn text-body-secondary p-0 creator-action-trigger';
      menuButton.setAttribute('data-bs-toggle', 'dropdown');
      menuButton.setAttribute('aria-label', `Actions for ${material.item}`);
      menuButton.setAttribute('aria-haspopup', 'true');
      menuButton.setAttribute('aria-expanded', 'false');
      menuButton.innerHTML = '<i class="icon-base ri ri-more-2-fill" aria-hidden="true"></i>';
      const menu = document.createElement('ul');
      menu.className = 'dropdown-menu dropdown-menu-end py-2';
      for (const [action, label, icon] of [
        ['edit', 'Edit', 'ri-edit-line'],
        ['delete', 'Delete', 'ri-delete-bin-line']
      ]) {
        const item = document.createElement('li');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `dropdown-item${action === 'delete' ? ' text-danger' : ''}`;
        button.dataset.materialAction = action;
        button.innerHTML = `<i class="icon-base ri ${icon} me-2" aria-hidden="true"></i>${label}`;
        item.append(button);
        menu.append(item);
      }
      dropdown.append(menuButton, menu);
      actionCell.append(dropdown);
      row.append(actionCell);
      materialsTableBody.append(row);
    });
    document.querySelector('#total-development-cost').value =
      (Math.round((grandTotal + Number.EPSILON) * 100) / 100).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
  }

  function resetMaterialModal() {
    editingMaterialIndex = null;
    materialModalTitle.textContent = 'Add Cost Item';
    materialModalFields.querySelectorAll('[data-material-modal-field]').forEach((field) => {
      field.value = '';
    });
  }

  function openMaterialForEdit(index) {
    const material = materials[index];
    if (!material) return;
    editingMaterialIndex = index;
    materialModalTitle.textContent = 'Edit Cost Item';
    materialFieldNames.forEach((fieldName) => {
      materialModalFields.querySelector(`[data-material-modal-field="${fieldName}"]`).value = material[fieldName] ?? '';
    });
    bootstrap.Modal.getOrCreateInstance(materialModal).show();
  }

  function getMaterialModalValues() {
    return Object.fromEntries(materialFieldNames.map((fieldName) => [
      fieldName,
      materialModalFields.querySelector(`[data-material-modal-field="${fieldName}"]`).value.trim()
    ]));
  }

  function resetTechnologyModal() {
    editingTechnologyIndex = null;
    removedTechnologyFiles.clear();
    technologyModalTitle.textContent = 'Add Technology';
    technologyModalFields.querySelectorAll('[data-technology-field]').forEach((field) => {
      field.value = '';
      field.required = field.dataset.technologyField === 'technology_title';
    });
    technologyModalFields.querySelectorAll('[data-technology-file]').forEach((field) => {
      field.value = '';
    });
    technologyModalFields.querySelectorAll('[data-existing-technology-file]').forEach((label) => {
      label.replaceChildren();
    });
    technologyModalFields.querySelectorAll('[data-remove-technology-file]').forEach((button) => {
      button.classList.add('d-none');
    });
    updateAttachmentCount();
  }

  function updateTechnologyFileStatus(narrative) {
    const upload = technologyModalFields.querySelector(`[data-technology-file="${narrative}"]`);
    const status = technologyModalFields.querySelector(`[data-existing-technology-file="${narrative}"]`);
    const removeButton = technologyModalFields.querySelector(`[data-remove-technology-file="${narrative}"]`);
    const originalTechnology = editingTechnologyIndex === null ? null : technologies[editingTechnologyIndex];
    const selectedFile = upload?.files?.[0];
    const existingFileName = removedTechnologyFiles.has(narrative)
      ? ''
      : originalTechnology?.uploadNames?.[narrative] || originalTechnology?.uploadInputs?.[narrative]?.files?.[0]?.name || '';

    if (selectedFile) {
      status.textContent = '';
    } else if (existingFileName) {
      status.textContent = `Attached PDF: ${existingFileName}`;
    } else if (removedTechnologyFiles.has(narrative)) {
      status.textContent = 'PDF will be removed when you save this technology.';
    } else {
      status.textContent = '';
    }

    removeButton.classList.toggle('d-none', !selectedFile && !existingFileName);
  }

  function getTechnologyModalValues() {
    const technology = { technology_title: technologyModalFields.querySelector('[data-technology-field="technology_title"]').value.trim() };
    const uploadInputs = {};
    const uploadNames = {};
    technologyNarratives.forEach((narrative) => {
      technology[narrative] = technologyModalFields.querySelector(`[data-technology-field="${narrative}"]`).value.trim();
      const upload = technologyModalFields.querySelector(`[data-technology-file="${narrative}"]`);
      if (upload?.files?.length) {
        uploadInputs[narrative] = upload;
        uploadNames[narrative] = upload.files[0].name;
      }
    });
    return { technology, uploadInputs, uploadNames };
  }

  function renderTechnologies() {
    technologiesTableBody.replaceChildren();
    technologyHiddenFields.replaceChildren();
    technologies.forEach((technology, index) => {
      const row = document.createElement('tr');
      row.className = 'technology-row';
      row.dataset.technologyIndex = String(index);
      const titleCell = document.createElement('td');
      titleCell.className = 'fw-semibold';
      titleCell.textContent = technology.technology_title;
      row.append(titleCell);
      const titleInput = document.createElement('input');
      titleInput.type = 'hidden';
      titleInput.name = `technologies[${index}][technology_title]`;
      titleInput.value = technology.technology_title ?? '';
      technologyHiddenFields.append(titleInput);
      Object.entries(technologyTableColumns).forEach(([field, label]) => {
        const fileInput = technology.uploadInputs?.[field];
        const fileName = technology.uploadNames?.[field] || fileInput?.files?.[0]?.name || '';
        const narrativeText = technology[field]?.trim() || '';
        const isComplete = Boolean(narrativeText || fileName);
        const statusCell = document.createElement('td');
        statusCell.className = 'technology-status-cell text-center text-nowrap';
        const status = document.createElement('span');
        status.className = `technology-status-icon ${isComplete ? 'is-complete' : 'is-incomplete'}`;
        status.setAttribute('aria-label', `${label}: ${isComplete ? 'complete' : 'incomplete'}`);
        status.title = `${label}: ${isComplete ? 'Complete' : 'Incomplete'}`;
        const icon = document.createElement('i');
        icon.className = `icon-base ri ${isComplete ? 'ri-checkbox-circle-fill' : 'ri-close-circle-fill'}`;
        icon.setAttribute('aria-hidden', 'true');
        status.append(icon);
        statusCell.append(status);
        row.append(statusCell);
      });
      const actionCell = document.createElement('td');
      actionCell.className = 'text-end text-nowrap';
      const dropdown = document.createElement('div');
      dropdown.className = 'dropdown';
      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'btn text-body-secondary p-0 creator-action-trigger';
      trigger.dataset.bsToggle = 'dropdown';
      trigger.setAttribute('aria-label', `Actions for ${technology.technology_title}`);
      trigger.setAttribute('aria-haspopup', 'true');
      trigger.innerHTML = '<i class="icon-base ri ri-more-2-fill" aria-hidden="true"></i>';
      const menu = document.createElement('ul');
      menu.className = 'dropdown-menu dropdown-menu-end py-2';
      for (const [action, label, icon] of [
        ['view', 'View', 'ri-eye-line'],
        ['edit', 'Edit', 'ri-edit-line'],
        ['delete', 'Delete', 'ri-delete-bin-line']
      ]) {
        const item = document.createElement('li');
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `dropdown-item${action === 'delete' ? ' text-danger' : ''}`;
        button.dataset.technologyAction = action;
        button.disabled = action === 'delete' && technologies.length === 1;
        button.innerHTML = `<i class="icon-base ri ${icon} me-2" aria-hidden="true"></i>${label}`;
        item.append(button);
        menu.append(item);
      }
      dropdown.append(trigger, menu);
      actionCell.append(dropdown);
      row.append(actionCell);
      technologiesTableBody.append(row);

      technologyNarratives.forEach((narrative) => {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = `technologies[${index}][${narrative}]`;
        hidden.value = technology[narrative] ?? '';
        technologyHiddenFields.append(hidden);
        const fileInput = technology.uploadInputs?.[narrative];
        if (fileInput) {
          fileInput.id = `technology-upload-${index}-${narrative}`;
          fileInput.name = `technologies[${index}][files][${narrative}]`;
          technologyHiddenFields.append(fileInput);
          const slot = technologyModalFields.querySelector(`[data-technology-upload-slot="${narrative}"]`);
          const replacement = fileInput.cloneNode(false);
          replacement.id = `technology-modal-file-${index}-${narrative}-replacement`;
          replacement.name = '';
          replacement.value = '';
          slot.insertBefore(replacement, slot.querySelector('[data-remove-technology-file]'));
        }
      });
    });
    if (technologies.length === 0) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.colSpan = Object.keys(technologyTableColumns).length + 2;
      cell.className = 'text-center text-body-secondary py-4';
      cell.textContent = 'No technologies have been added yet.';
      row.append(cell);
      technologiesTableBody.append(row);
    }
    addTechnologyButton.disabled = technologies.length >= 2;
    updateAttachmentCount();
  }

  function openTechnologyForEdit(index) {
    const technology = technologies[index];
    if (!technology) return;
    removedTechnologyFiles.clear();
    editingTechnologyIndex = index;
    technologyModalTitle.textContent = 'Edit Technology';
    technologyModalFields.querySelector('[data-technology-field="technology_title"]').value = technology.technology_title ?? '';
    technologyNarratives.forEach((narrative) => {
      technologyModalFields.querySelector(`[data-technology-field="${narrative}"]`).value = technology[narrative] ?? '';
      updateTechnologyFileStatus(narrative);
    });
    bootstrap.Modal.getOrCreateInstance(technologyModal).show();
  }

  async function requestTechnologySave() {
    if (saveTechnologyButton.disabled) return;
    saveTechnologyButton.disabled = true;
    const titleField = technologyModalFields.querySelector('[data-technology-field="technology_title"]');
    try {
      if (typeof window.Swal?.fire !== 'function') {
        throw new Error('SweetAlert is not ready. Refresh the page and try again.');
      }

      if (!titleField.value.trim()) {
        await window.Swal.fire({
          title: 'Technology title required',
          text: 'Enter a title for this technology before saving.',
          icon: 'error',
          confirmButtonText: 'OK'
        });
        titleField.focus();
        return;
      }

      const { technology, uploadInputs, uploadNames } = getTechnologyModalValues();
      const originalTechnology = editingTechnologyIndex === null ? null : technologies[editingTechnologyIndex];
      const missingNarratives = technologyNarratives.filter((narrative) => {
        const existingUpload = !removedTechnologyFiles.has(narrative) && originalTechnology?.uploadInputs?.[narrative];
        return !technology[narrative] && !uploadInputs[narrative] && !existingUpload;
      });

      if (missingNarratives.length) {
        await window.Swal.fire({
          title: 'Required narratives are missing',
          text: `Provide text or a PDF for: ${missingNarratives.map((narrative) => technologyNarrativeLabels[narrative]).join(', ')}.`,
          icon: 'error',
          confirmButtonText: 'Review narratives'
        });
        technologyModalFields.querySelector(`[data-technology-field="${missingNarratives[0]}"]`).focus();
        return;
      }

      if (originalTechnology) {
        technologyNarratives.forEach((narrative) => {
          if (uploadInputs[narrative] || removedTechnologyFiles.has(narrative) || !originalTechnology.uploadInputs?.[narrative]) return;
          uploadInputs[narrative] = originalTechnology.uploadInputs[narrative];
          uploadNames[narrative] = originalTechnology.uploadNames[narrative];
        });
      }

      const isEditing = editingTechnologyIndex !== null;
      const result = await window.Swal.fire({
        title: isEditing ? 'Save technology changes?' : 'Add this technology?',
        text: `${technology.technology_title} will be ${isEditing ? 'updated' : 'added'} to the disclosure.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: isEditing ? 'Save changes' : 'Add technology',
        cancelButtonText: 'Review details',
        reverseButtons: true,
        focusCancel: true
      });

      if (!result.isConfirmed) return;

      const savedTechnology = { ...technology, uploadInputs, uploadNames };
      if (isEditing) {
        if (!technologies[editingTechnologyIndex]) {
          throw new Error('The technology being edited is no longer available. Close the modal and try again.');
        }
        technologies[editingTechnologyIndex] = savedTechnology;
      } else {
        if (technologies.length >= 2) {
          throw new Error('A maximum of two technologies can be added to one disclosure.');
        }
        technologies.push(savedTechnology);
      }

      renderTechnologies();
      scheduleDraftSave();
      const modalHidden = new Promise((resolve) => {
        technologyModal.addEventListener('hidden.bs.modal', resolve, { once: true });
      });
      bootstrap.Modal.getOrCreateInstance(technologyModal).hide();
      await modalHidden;
      await window.Swal.fire({
        title: isEditing ? 'Technology updated' : 'Technology added',
        text: `${technology.technology_title} has been ${isEditing ? 'updated' : 'added'} to the disclosure.`,
        icon: 'success',
        timer: 1800,
        showConfirmButton: false
      });
    } catch (error) {
      console.error('Technology save failed:', error);
      if (typeof window.Swal?.fire === 'function') {
        await window.Swal.fire({
          title: 'Could not save technology',
          text: error instanceof Error ? error.message : 'An unexpected error occurred while saving the technology.',
          icon: 'error',
          confirmButtonText: 'OK'
        });
      }
    } finally {
      saveTechnologyButton.disabled = false;
    }
  }

  function openTechnologyModal() {
    if (technologies.length >= 2) return;
    resetTechnologyModal();
  }

  addInventorButton.addEventListener('click', () => {
    resetCreatorModal();
  });

  creatorModal.querySelectorAll('.nature-checkbox').forEach((checkbox) => {
    checkbox.addEventListener('change', () => {
      updateNatureRequirement();
      updateModalOtherContribution();
    });
  });

  saveCreatorButton.addEventListener('click', async () => {
    saveCreatorButton.disabled = true;
    try {
      if (typeof window.Swal?.fire !== 'function') {
        throw new Error('SweetAlert is not ready. Refresh the page and try again.');
      }

      updateNatureRequirement();
      updateModalOtherContribution();
      const invalidField = [...creatorModalFields.querySelectorAll('input, textarea')]
        .find((field) => !field.disabled && !field.checkValidity());
      if (invalidField) {
        const fieldLabel = invalidField.labels?.[0]?.textContent.trim()
          || invalidField.closest('fieldset')?.querySelector('legend')?.textContent.trim()
          || 'This field';
        let message = invalidField.validationMessage;
        if (invalidField.validity.valueMissing) {
          message = `${fieldLabel} is required.`;
        } else if (invalidField.validity.typeMismatch) {
          message = `Enter a valid ${fieldLabel.toLowerCase()}.`;
        }
        await window.Swal.fire({
          title: 'Check creator information',
          text: message,
          target: creatorModal,
          icon: 'error',
          confirmButtonText: 'Review details'
        });
        invalidField.focus();
        return;
      }

      const creator = getCreatorModalValues();
      const isEditing = editingCreatorIndex !== null;
      const result = await window.Swal.fire({
        title: isEditing ? 'Save creator changes?' : 'Add this creator?',
        text: `${creator.last_name}, ${creator.first_name} will be ${isEditing ? 'updated' : 'added'} to the disclosure.`,
        target: creatorModal,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: isEditing ? 'Save changes' : 'Add creator',
        cancelButtonText: 'Review details',
        reverseButtons: true,
        focusCancel: true
      });
      if (!result.isConfirmed) return;

      if (editingCreatorIndex === null) creators.push(creator);
      else if (creators[editingCreatorIndex]) creators[editingCreatorIndex] = creator;
      else throw new Error('The creator being edited could not be found.');

      updateCreatorRows();
      scheduleDraftSave();
      const modalHidden = new Promise((resolve) => {
        creatorModal.addEventListener('hidden.bs.modal', resolve, { once: true });
      });
      bootstrap.Modal.getOrCreateInstance(creatorModal).hide();
      await modalHidden;
      await window.Swal.fire({
        title: isEditing ? 'Creator updated successfully' : 'Creator added successfully',
        text: `${creator.first_name} ${creator.last_name} has been ${isEditing ? 'updated' : 'added'} to the disclosure.`,
        icon: 'success',
        timer: 1800,
        showConfirmButton: false
      });
    } catch (error) {
      console.error('Creator save failed:', error);
      if (typeof window.Swal?.fire === 'function') {
        await window.Swal.fire({
          title: 'Could not save creator',
          text: error instanceof Error ? error.message : 'An unexpected error occurred while saving the creator.',
          target: creatorModal,
          icon: 'error',
          confirmButtonText: 'OK'
        });
      }
    } finally {
      saveCreatorButton.disabled = false;
    }
  });

  inventorsTableBody.addEventListener('click', (event) => {
    const actionButton = event.target.closest('[data-creator-action]');
    if (!actionButton) return;
    const index = Number(actionButton.dataset.creatorIndex);
    if (actionButton.dataset.creatorAction === 'view') {
      openCreatorForView(index);
      return;
    }
    if (actionButton.dataset.creatorAction === 'edit') {
      openCreatorForEdit(index);
      return;
    }
    document.dispatchEvent(new CustomEvent('creator:delete', { detail: { index } }));
  });

  document.addEventListener('creator:delete-confirmed', (event) => {
    const index = Number(event.detail.index);
    if (!Number.isInteger(index) || !creators[index]) return;
    creators.splice(index, 1);
    updateCreatorRows();
    scheduleDraftSave();
  });

  addMaterialButton.addEventListener('click', resetMaterialModal);
  saveMaterialButton.addEventListener('click', async () => {
    if (saveMaterialButton.disabled) return;
    saveMaterialButton.disabled = true;
    try {
      const invalidField = [...materialModalFields.querySelectorAll('input')]
        .find((field) => !field.disabled && !field.checkValidity());
      if (invalidField) {
        const fieldLabel = invalidField.labels?.[0]?.textContent.trim() || 'This field';
        let message = invalidField.validationMessage;
        if (invalidField.validity.valueMissing) {
          message = `${fieldLabel} is required.`;
        } else if (invalidField.validity.typeMismatch) {
          message = `Enter a valid ${fieldLabel.toLowerCase()}.`;
        }
        await window.Swal.fire({
          title: 'Check cost item details',
          text: message,
          target: materialModal,
          icon: 'error',
          confirmButtonText: 'Review details'
        });
        invalidField.focus();
        return;
      }

      const material = getMaterialModalValues();
      const isEditing = editingMaterialIndex !== null;
      const result = await window.Swal.fire({
        title: isEditing ? 'Save cost item changes?' : 'Add this cost item?',
        text: `${material.item} will be ${isEditing ? 'updated' : 'added'} to the costing table.`,
        target: materialModal,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: isEditing ? 'Save changes' : 'Add item',
        cancelButtonText: 'Review details',
        reverseButtons: true,
        focusCancel: true
      });
      if (!result.isConfirmed) return;

      if (editingMaterialIndex === null) materials.push(material);
      else if (materials[editingMaterialIndex]) materials[editingMaterialIndex] = material;
      else throw new Error('The cost item being edited could not be found.');

      updateCosts();
      scheduleDraftSave();
      const modalHidden = new Promise((resolve) => {
        materialModal.addEventListener('hidden.bs.modal', resolve, { once: true });
      });
      bootstrap.Modal.getOrCreateInstance(materialModal).hide();
      await modalHidden;
    } catch (error) {
      console.error('Cost item save failed:', error);
      await window.Swal.fire({
        title: 'Could not save cost item',
        text: error instanceof Error ? error.message : 'An unexpected error occurred while saving the cost item.',
        target: materialModal,
        icon: 'error',
        confirmButtonText: 'OK'
      });
    } finally {
      saveMaterialButton.disabled = false;
    }
  });

  materialsTableBody.addEventListener('click', (event) => {
    const actionButton = event.target.closest('[data-material-action]');
    if (!actionButton) return;
    const index = Number(actionButton.closest('.material-row').dataset.materialIndex);
    if (actionButton.dataset.materialAction === 'edit') {
      openMaterialForEdit(index);
      return;
    }
    document.dispatchEvent(new CustomEvent('material:delete', { detail: { index } }));
  });

  document.addEventListener('material:delete-confirmed', (event) => {
    const index = Number(event.detail.index);
    if (!Number.isInteger(index) || !materials[index]) return;
    materials.splice(index, 1);
    updateCosts();
    scheduleDraftSave();
  });

  addTechnologyButton.addEventListener('click', openTechnologyModal);
  creatorModalFields.addEventListener('input', scheduleDraftSave);
  creatorModalFields.addEventListener('change', scheduleDraftSave);
  materialModalFields.addEventListener('input', scheduleDraftSave);
  technologyModalFields.addEventListener('input', scheduleDraftSave);
  technologyModalFields.addEventListener('change', async (event) => {
    const upload = event.target.closest('[data-technology-file]');
    if (!upload) return;
    const narrative = upload.dataset.technologyFile;
    const narrativeField = technologyModalFields.querySelector(`[data-technology-field="${narrative}"]`);
    const selectedFile = upload.files?.[0];
    if (selectedFile && selectedFile.name.split('.').pop()?.toLowerCase() === 'pdf' && narrativeField.value.trim()) {
      if (typeof window.Swal?.fire !== 'function') {
        upload.value = '';
        updateTechnologyFileStatus(narrative);
        console.error('Could not confirm replacing the narrative because SweetAlert is unavailable.');
        return;
      }

      upload.disabled = true;
      try {
        const result = await window.Swal.fire({
          title: 'Narrative already exists',
          text: `A text narrative already exists for ${technologyNarrativeLabels[narrative]}. Proceeding will replace the text with the uploaded PDF.`,
          target: technologyModal,
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Proceed',
          cancelButtonText: 'Cancel',
          reverseButtons: true,
          focusCancel: true
        });
        if (result.isConfirmed) {
          narrativeField.value = '';
        } else {
          upload.value = '';
        }
      } catch (error) {
        upload.value = '';
        console.error('Could not confirm replacing the technology narrative:', error);
        await window.Swal.fire({
          title: 'Could not replace narrative',
          text: 'The PDF was not selected. Please try again.',
          target: technologyModal,
          icon: 'error',
          confirmButtonText: 'OK'
        });
      } finally {
        upload.disabled = false;
      }
    }
    updateTechnologyFileStatus(narrative);
    updateAttachmentCount();
    scheduleDraftSave();
  });
  technologyModalFields.addEventListener('click', async (event) => {
    const removeButton = event.target.closest('[data-remove-technology-file]');
    if (!removeButton) return;
    const narrative = removeButton.dataset.removeTechnologyFile;
    const upload = technologyModalFields.querySelector(`[data-technology-file="${narrative}"]`);
    const originalTechnology = editingTechnologyIndex === null ? null : technologies[editingTechnologyIndex];
    const hasSelectedFile = Boolean(upload.files.length);
    const hasExistingFile = Boolean(
      originalTechnology?.uploadInputs?.[narrative]
      || originalTechnology?.uploadNames?.[narrative]
    ) && !removedTechnologyFiles.has(narrative);
    if (!hasSelectedFile && !hasExistingFile) return;
    if (typeof window.Swal?.fire !== 'function') {
      console.error('Could not confirm removing the technology PDF because SweetAlert is unavailable.');
      return;
    }

    removeButton.disabled = true;
    try {
      const result = await window.Swal.fire({
        title: 'Remove this PDF?',
        text: 'The selected or attached PDF will be removed from this narrative.',
        target: technologyModal,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, remove PDF',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33',
        reverseButtons: true,
        focusCancel: true
      });
      if (!result.isConfirmed) return;

      upload.value = '';
      if (hasExistingFile) removedTechnologyFiles.add(narrative);
      updateTechnologyFileStatus(narrative);
      updateAttachmentCount();
    } catch (error) {
      console.error('Could not confirm removing the technology PDF:', error);
      await window.Swal.fire({
        title: 'Could not remove PDF',
        text: 'The PDF was not removed. Please try again.',
        target: technologyModal,
        icon: 'error',
        confirmButtonText: 'OK'
      });
    } finally {
      removeButton.disabled = false;
    }
  });
  technologyModal.addEventListener('hidden.bs.modal', () => {
    if (editingTechnologyIndex !== null) renderTechnologies();
    resetTechnologyModal();
  });
  technologyFilePreviewModal.addEventListener('hidden.bs.modal', () => {
    technologyFilePreviewFrame.src = 'about:blank';
    if (technologyPreviewUrl) URL.revokeObjectURL(technologyPreviewUrl);
    technologyPreviewUrl = null;
  });

  function openTechnologyPdfPreview(index, narrative, fileName) {
    const file = technologies[index]?.uploadInputs?.[narrative]?.files?.[0];
    if (!file) return;
    if (technologyPreviewUrl) URL.revokeObjectURL(technologyPreviewUrl);
    technologyPreviewUrl = URL.createObjectURL(file);
    technologyFilePreviewTitle.textContent = fileName || file.name;
    technologyFilePreviewFrame.src = technologyPreviewUrl;
    bootstrap.Modal.getOrCreateInstance(technologyFilePreviewModal).show();
  }

  technologyNarrativeViewContent.addEventListener('click', (event) => {
    const previewButton = event.target.closest('[data-technology-file-preview]');
    if (!previewButton) return;
    const technologyIndex = Number(previewButton.dataset.technologyIndex);
    const narrative = previewButton.dataset.technologyFilePreview;
    const viewModal = bootstrap.Modal.getOrCreateInstance(technologyNarrativeViewModal);
    technologyNarrativeViewModal.addEventListener('hidden.bs.modal', () => {
      openTechnologyPdfPreview(technologyIndex, narrative, previewButton.dataset.technologyFileName);
    }, { once: true });
    viewModal.hide();
  });

  technologiesTableBody.addEventListener('click', (event) => {
    const actionButton = event.target.closest('[data-technology-action]');
    if (!actionButton) return;
    const index = Number(actionButton.closest('.technology-row').dataset.technologyIndex);
    if (actionButton.dataset.technologyAction === 'view') {
      const technology = technologies[index];
      technologyNarrativeViewTitle.textContent = technology.technology_title || 'Technology Details';
      technologyNarrativeViewContent.replaceChildren();
      technologyNarratives.forEach((narrative) => {
        const value = technology[narrative]?.trim();
        const fileInput = technology.uploadInputs?.[narrative];
        const fileName = technology.uploadNames?.[narrative] || fileInput?.files?.[0]?.name;
        if (!value && !fileName) return;
        const field = document.createElement('div');
        field.className = 'col-12';
        if (value) {
          const accordion = document.createElement('div');
          accordion.className = 'accordion accordion-flush border rounded mb-3';
          const item = document.createElement('div');
          item.className = 'accordion-item';
          const heading = document.createElement('h2');
          heading.className = 'accordion-header';
          const toggle = document.createElement('button');
          const collapseId = `technology-view-${index}-${narrative}-collapse`;
          toggle.className = 'accordion-button collapsed fw-semibold';
          toggle.type = 'button';
          toggle.dataset.bsToggle = 'collapse';
          toggle.dataset.bsTarget = `#${collapseId}`;
          toggle.setAttribute('aria-expanded', 'false');
          toggle.setAttribute('aria-controls', collapseId);
          toggle.textContent = technologyNarrativeLabels[narrative];
          heading.append(toggle);
          const collapse = document.createElement('div');
          collapse.className = 'accordion-collapse collapse';
          collapse.id = collapseId;
          const body = document.createElement('div');
          body.className = 'accordion-body';
          const content = document.createElement('textarea');
          content.className = 'form-control';
          content.rows = 4;
          content.readOnly = true;
          content.value = value;
          body.append(content);
          collapse.append(body);
          item.append(heading, collapse);
          accordion.append(item);
          field.append(accordion);
        }
        if (fileName) {
          const fileBanner = document.createElement('div');
          fileBanner.className = 'd-flex align-items-center gap-3';
          const fileIcon = document.createElement('i');
          fileIcon.className = 'icon-base ri ri-file-pdf-2-line icon-24px text-danger flex-shrink-0';
          fileIcon.setAttribute('aria-hidden', 'true');
          const fileDetails = document.createElement('div');
          fileDetails.className = 'flex-grow-1 min-w-0';
          const fileType = document.createElement('span');
          fileType.className = 'badge bg-label-danger mb-1';
          fileType.textContent = 'PDF DOCUMENT';
          const fileTitle = document.createElement('div');
          fileTitle.className = 'fw-medium text-break';
          fileTitle.textContent = fileName;
          fileDetails.append(fileType, fileTitle);
          const previewButton = document.createElement('button');
          previewButton.type = 'button';
          previewButton.className = 'btn btn-icon btn-text-secondary flex-shrink-0';
          previewButton.dataset.technologyFilePreview = narrative;
          previewButton.dataset.technologyIndex = String(index);
          previewButton.dataset.technologyFileName = fileName;
          previewButton.setAttribute('aria-label', `View ${technologyNarrativeLabels[narrative]} PDF: ${fileName}`);
          previewButton.title = `View PDF: ${fileName}`;
          previewButton.innerHTML = '<i class="icon-base ri ri-eye-line" aria-hidden="true"></i>';
          fileBanner.append(fileIcon, fileDetails, previewButton);
          field.append(fileBanner);
        }
        technologyNarrativeViewContent.append(field);
      });
      bootstrap.Modal.getOrCreateInstance(technologyNarrativeViewModal).show();
      return;
    }
    if (actionButton.dataset.technologyAction === 'edit') {
      openTechnologyForEdit(index);
      return;
    }
    document.dispatchEvent(new CustomEvent('technology:delete', { detail: { index } }));
  });

  document.addEventListener('technology:delete-confirmed', (event) => {
    const index = Number(event.detail.index);
    if (!Number.isInteger(index) || !technologies[index] || technologies.length === 1) return;
    technologies.splice(index, 1);
    renderTechnologies();
    scheduleDraftSave();
  });

  relationshipField.addEventListener('change', () => {
    idfDraftUserInteracted = true;
    updateRelationshipFields();
    updateOwnershipFields();
  });
  ownershipField.addEventListener('change', () => {
    idfDraftUserInteracted = true;
    updateOwnershipFields();
  });
  updateRelationshipFields();
  updateOwnershipFields();
  updateCreatorRows();
  updateCosts();
  renderTechnologies();

  disclosureForm.addEventListener('change', (event) => {
    idfDraftUserInteracted = true;
    if (event.target.matches('input[type="file"]')) {
      updateAttachmentCount();
      if (!event.target.matches('[data-technology-file]')) scheduleDraftSave();
    } else {
      scheduleDraftSave();
    }
  });
  disclosureForm.addEventListener('input', (event) => {
    idfDraftUserInteracted = true;
    if (!event.target.matches('input[type="file"]')) scheduleDraftSave();
  });
  [creatorModalFields, materialModalFields, technologyModalFields].forEach((fields) => {
    fields.addEventListener('input', () => { idfDraftUserInteracted = true; });
    fields.addEventListener('change', () => { idfDraftUserInteracted = true; });
  });
  window.addEventListener('pagehide', () => {
    window.clearTimeout(idfDraftSaveTimer);
    if (sessionStorage.getItem(idfSubmissionRecoveryKey) !== 'true') {
      clearDraftRecord().catch(error => console.error('Could not clear local IDF draft during navigation:', error));
    }
  });
  window.addEventListener('pageshow', (event) => {
    if (event.persisted) window.location.reload();
  });

  document.querySelector('#save-idf-submission').addEventListener('click', async (event) => {
    const button = event.currentTarget;
    button.disabled = true;
    try {
      await queueDraftSave();
      const snapshot = await createPortableIdfFile();
      const filenameBase = disclosureForm.querySelector('[name="research_title"]').value
        .trim()
        .replace(/[^a-z0-9]+/gi, '-')
        .replace(/^-|-$/g, '')
        .toLowerCase() || 'idf-draft';
      const saveResult = await savePortableIdfFile(snapshot, `${filenameBase}-idf-draft.json`);
      if (saveResult === 'saved') {
        await window.Swal.fire({
          title: 'IDF file saved',
          text: 'Your submission data and uploaded PDFs were saved to the selected location.',
          icon: 'success',
          confirmButtonText: 'Done'
        });
      } else {
        idfDraftStatus.textContent = 'Download started. Confirm or locate the file in your browser downloads.';
      }
    } catch (error) {
      if (error?.name === 'AbortError') return;
      console.error('IDF export failed:', error);
      await window.Swal.fire({
        title: 'Could not save IDF file',
        text: error instanceof Error ? error.message : 'The draft file could not be created.',
        icon: 'error',
        confirmButtonText: 'OK'
      });
    } finally {
      button.disabled = false;
    }
  });

  document.querySelector('#import-idf-button').addEventListener('click', () => {
    idfDraftUserInteracted = true;
    idfDraftImportInput.click();
  });
  idfDraftImportInput.addEventListener('change', async () => {
    const file = idfDraftImportInput.files?.[0];
    if (!file) return;
    try {
      const result = await window.Swal.fire({
        title: 'Import IDF file?',
        text: 'Importing replaces the values currently entered in this form.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Import IDF',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: true
      });
      if (!result.isConfirmed) return;
      const imported = decodePortableDraft(JSON.parse(await file.text()));
      await restoreIdfDraft(imported);
      await window.Swal.fire({
        title: 'IDF imported',
        text: 'The saved data and attached PDFs were restored into the form.',
        icon: 'success',
        confirmButtonText: 'Continue'
      });
    } catch (error) {
      console.error('IDF import failed:', error);
      await window.Swal.fire({
        title: 'Could not import IDF',
        text: error instanceof Error ? error.message : 'The selected file could not be imported.',
        icon: 'error',
        confirmButtonText: 'OK'
      });
    } finally {
      idfDraftImportInput.value = '';
    }
  });

  (async () => {
    try {
      const savedRecord = await openIdfDraftDatabase().then(database => new Promise((resolve, reject) => {
        const request = database.transaction('drafts', 'readonly').objectStore('drafts').get(idfDraftKey);
        request.onsuccess = () => resolve(request.result || null);
        request.onerror = () => reject(request.error || new Error('Could not read the local IDF draft.'));
      }));
      const isSubmissionRecovery = sessionStorage.getItem(idfSubmissionRecoveryKey) === 'true';
      if (isSubmissionRecovery && savedRecord?.data && !idfDraftUserInteracted) {
        await restoreIdfDraft(savedRecord.data);
        sessionStorage.removeItem(idfSubmissionRecoveryKey);
        idfDraftStatus.textContent = 'Your locally saved IDF draft was restored.';
      } else {
        await clearDraftRecord();
        sessionStorage.removeItem(idfSubmissionRecoveryKey);
        scheduleDraftSave();
      }
    } catch (error) {
      await showDraftStorageError('Could not restore draft', error);
    }
  })();
</script>
<script src="{{ asset('user/js/innovation-disclosure.js') }}" defer></script>
@endif
@endsection