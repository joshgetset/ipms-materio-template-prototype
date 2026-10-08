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
  $materialRows = old('materials');
  if (! is_array($materialRows) || $materialRows === []) {
  $materialRows = [['item' => '', 'description' => '', 'quantity' => '', 'unit_cost' => '', 'purpose' => '']];
  }
  $narrativeFields = ['abstract_summary', 'technical_field', 'background_summary', 'invention_summary', 'detailed_description', 'examples_results'];
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
            <div class="row g-4">
              <div class="col-md-6"><label class="form-label" for="lead_creator_name">Name of lead creator</label><input class="form-control" id="lead_creator_name" name="lead_creator_name" value="{{ old('lead_creator_name') }}" required maxlength="255"></div>
              <div class="col-md-6"><label class="form-label" for="college_department">College / Department</label><input class="form-control" id="college_department" name="college_department" value="{{ old('college_department') }}" required maxlength="255"></div>
              <div class="col-md-6"><label class="form-label" for="campus">Campus</label><input class="form-control" id="campus" name="campus" value="{{ old('campus') }}" required maxlength="255"></div>
              <div class="col-md-6">
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
              <div class="col-12 {{ old('university_relationship') === 'other_specify' ? '' : 'd-none' }}" id="other-relationship-field">
                <label class="form-label" for="other_relationship">Please specify your relationship</label>
                <input class="form-control" id="other_relationship" name="other_relationship" value="{{ old('other_relationship') }}" maxlength="255" @required(old('university_relationship') === 'other_specify')>
              </div>
              <div class="col-md-6"><label class="form-label" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required maxlength="255"></div>
              <div class="col-md-6"><label class="form-label" for="mobile_no">Contact number</label><input class="form-control" id="mobile_no" name="mobile_no" type="tel" value="{{ old('mobile_no') }}" required maxlength="20" pattern="(09|\+639)[0-9]{9}" title="Use 09 followed by 9 digits or +639 followed by 9 digits."></div>
              <div class="col-md-6"><label class="form-label" for="research_title">Research project title</label><input class="form-control" id="research_title" name="research_title" value="{{ old('research_title') }}" required maxlength="500"></div>
              <div class="col-md-6"><label class="form-label" for="funding_source">Funding source</label><input class="form-control" id="funding_source" name="funding_source" value="{{ old('funding_source') }}" required maxlength="500"></div>
              <div class="col-md-4"><label class="form-label" for="date_of_creation">Date of creation</label><input class="form-control" id="date_of_creation" name="date_of_creation" type="date" value="{{ old('date_of_creation') }}" required></div>
              <div class="col-md-4"><label class="form-label" for="research_approval_date">Research proposal approval date</label><input class="form-control" id="research_approval_date" name="research_approval_date" type="date" value="{{ old('research_approval_date') }}" required></div>
              <div class="col-md-4"><label class="form-label" for="disclosure-date-display">Date of this disclosure</label><input class="form-control" id="disclosure-date-display" value="{{ now()->toDateString() }}" readonly aria-describedby="disclosure-date-help"><div class="form-text" id="disclosure-date-help">Automatically set to today when submitted.</div></div>
              <div class="col-md-6">
                <label class="form-label" for="technology_type">Technology / Invention Category</label>
                <select class="form-select" id="technology_type" name="technology_type" required>
                  <option value="">Choose a category</option>
                  <option value="mechanical_engineering" @selected(old('technology_type')==='mechanical_engineering')>IT, IoT &amp; Engineering</option>
                  <option value="chemical" @selected(old('technology_type')==='chemical')>Food, Pharmaceutical, Structural Chemistry</option>
                </select>
              </div>
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
                <thead><tr><th scope="col">Technology Title</th><th scope="col">Narratives</th><th scope="col" class="text-end">Action</th></tr></thead>
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
        <div class="col-12 d-flex justify-content-end">
          <button class="btn btn-primary" type="submit"><i class="icon-base ri ri-send-plane-line me-1"></i>Submit disclosure</button>
        </div>
      </div>
    </form>
  </div>

  <div class="modal fade" id="material-modal" tabindex="-1" aria-labelledby="material-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
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
              <div data-technology-upload-slot="{{ $field }}">
                <input class="form-control pdf-upload" id="technology-modal-file-{{ $field }}" data-technology-file="{{ $field }}" type="file" accept="application/pdf,.pdf" data-file-label="{{ $label }}">
              </div>
              <div class="form-text" data-existing-technology-file="{{ $field }}"></div>
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
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
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

  <div class="modal fade" id="creator-modal" tabindex="-1" aria-labelledby="creator-modal-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
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
                <div class="col-sm-6 col-lg-4"><div class="form-check"><input class="form-check-input nature-checkbox" data-nature="{{ $nature }}" id="creator-modal-nature-{{ $nature }}" type="checkbox" value="{{ $nature }}"><label class="form-check-label" for="creator-modal-nature-{{ $nature }}">{{ $label }}</label></div></div>
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
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
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
@vite(['resources/assets/js/user-submission-status.js'])
@endif

@if ($page === 'form')
<script>
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
  const technologiesTableBody = document.querySelector('#technologies-table-body');
  const technologyHiddenFields = document.querySelector('#technology-hidden-fields');
  const technologyModal = document.querySelector('#technology-modal');
  const technologyModalTitle = document.querySelector('#technology-modal-title');
  const technologyModalFields = document.querySelector('#technology-modal-fields');
  const technologyFilePreviewModal = document.querySelector('#technology-file-preview-modal');
  const technologyFilePreviewTitle = document.querySelector('#technology-file-preview-title');
  const technologyFilePreviewFrame = document.querySelector('#technology-file-preview-frame');
  const saveTechnologyButton = document.querySelector('#save-technology');
  const addTechnologyButton = document.querySelector('#add-technology');
  let technologies = @json($technologyRows).map((technology) => ({
    ...technology,
    uploadInputs: {},
    uploadNames: {}
  }));
  let editingTechnologyIndex = null;
  let technologyPreviewUrl = null;

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
        button.disabled = action === 'delete' && materials.length === 1;
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
    technologyModalTitle.textContent = 'Add Technology';
    technologyModalFields.querySelectorAll('[data-technology-field]').forEach((field) => {
      field.value = '';
      field.required = false;
    });
    technologyModalFields.querySelectorAll('[data-technology-file]').forEach((field) => {
      field.value = '';
    });
    technologyModalFields.querySelectorAll('[data-existing-technology-file]').forEach((label) => {
      label.replaceChildren();
    });
    updateAttachmentCount();
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
      const narrativeCell = document.createElement('td');
      narrativeCell.className = 'technology-narrative-summary';
      technologyNarratives.forEach((narrative) => {
        const fileName = technology.uploadNames?.[narrative];
        const item = fileName && technology.uploadInputs?.[narrative]
          ? document.createElement('button')
          : document.createElement('span');
        item.className = 'badge bg-label-primary me-1 mb-1';
        if (fileName && technology.uploadInputs?.[narrative]) {
          item.type = 'button';
          item.classList.add('technology-file-preview-trigger');
          item.dataset.technologyFilePreview = narrative;
          item.dataset.technologyFileName = fileName;
          item.setAttribute('aria-label', `Preview ${narrative.replaceAll('_', ' ')} PDF: ${fileName}`);
          item.textContent = fileName;
        } else {
          item.textContent = technology[narrative] ? 'Typed' : 'PDF';
        }
        narrativeCell.append(item);
      });
      row.append(narrativeCell);
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
      for (const [action, label, icon] of [['edit', 'Edit', 'ri-edit-line'], ['delete', 'Delete', 'ri-delete-bin-line']]) {
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
          slot.replaceChildren(replacement);
        }
      });
    });
    if (technologies.length === 0) {
      const row = document.createElement('tr');
      const cell = document.createElement('td');
      cell.colSpan = 3;
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
    editingTechnologyIndex = index;
    technologyModalTitle.textContent = 'Edit Technology';
    technologyModalFields.querySelector('[data-technology-field="technology_title"]').value = technology.technology_title ?? '';
    technologyNarratives.forEach((narrative) => {
      technologyModalFields.querySelector(`[data-technology-field="${narrative}"]`).value = technology[narrative] ?? '';
      const existing = technologyModalFields.querySelector(`[data-existing-technology-file="${narrative}"]`);
      existing.textContent = technology.uploadNames?.[narrative]
        ? `Previously selected PDF: ${technology.uploadNames[narrative]} (kept unless replaced).`
        : '';
    });
    bootstrap.Modal.getOrCreateInstance(technologyModal).show();
  }

  function requestTechnologySave() {
    const titleField = technologyModalFields.querySelector('[data-technology-field="technology_title"]');
    if (!titleField.checkValidity()) {
      titleField.reportValidity();
      return;
    }
    const { technology, uploadInputs, uploadNames } = getTechnologyModalValues();
    const originalTechnology = editingTechnologyIndex === null ? null : technologies[editingTechnologyIndex];
    for (const narrative of technologyNarratives) {
      const existingUpload = originalTechnology?.uploadInputs?.[narrative];
      if (technology[narrative] || uploadInputs[narrative] || existingUpload) continue;
      const field = technologyModalFields.querySelector(`[data-technology-field="${narrative}"]`);
      field.setCustomValidity('Enter this narrative or upload its PDF.');
      field.reportValidity();
      field.setCustomValidity('');
      return;
    }
    if (originalTechnology) {
      technologyNarratives.forEach((narrative) => {
        if (uploadInputs[narrative] || technology[narrative] || !originalTechnology.uploadInputs?.[narrative]) return;
        uploadInputs[narrative] = originalTechnology.uploadInputs[narrative];
        uploadNames[narrative] = originalTechnology.uploadNames[narrative];
      });
    }
    document.dispatchEvent(new CustomEvent('technology:save-requested', {
      detail: { technology, uploadInputs, uploadNames, editingIndex: editingTechnologyIndex }
    }));
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

  saveCreatorButton.addEventListener('click', () => {
    updateNatureRequirement();
    updateModalOtherContribution();
    const invalidField = [...creatorModalFields.querySelectorAll('input, textarea')]
      .find((field) => !field.disabled && !field.checkValidity());
    if (invalidField) {
      invalidField.reportValidity();
      return;
    }
    const creator = getCreatorModalValues();
    document.dispatchEvent(new CustomEvent('creator:save-requested', {
      detail: { creator, editingIndex: editingCreatorIndex }
    }));
  });

  document.addEventListener('creator:save-confirmed', (event) => {
    const { creator, editingIndex } = event.detail;
    if (editingIndex === null) creators.push(creator);
    else if (creators[editingIndex]) creators[editingIndex] = creator;
    else return;
    updateCreatorRows();
    creatorModal.querySelector('[data-bs-dismiss="modal"]').click();
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
  });

  addMaterialButton.addEventListener('click', resetMaterialModal);
  saveMaterialButton.addEventListener('click', () => {
    const invalidField = [...materialModalFields.querySelectorAll('input')]
      .find((field) => !field.disabled && !field.checkValidity());
    if (invalidField) {
      invalidField.reportValidity();
      return;
    }
    document.dispatchEvent(new CustomEvent('material:save-requested', {
      detail: { material: getMaterialModalValues(), editingIndex: editingMaterialIndex }
    }));
  });

  document.addEventListener('material:save-confirmed', (event) => {
    const { material, editingIndex } = event.detail;
    if (editingIndex === null) materials.push(material);
    else if (materials[editingIndex]) materials[editingIndex] = material;
    else return;
    updateCosts();
    materialModal.querySelector('[data-bs-dismiss="modal"]').click();
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
    if (!Number.isInteger(index) || !materials[index] || materials.length === 1) return;
    materials.splice(index, 1);
    updateCosts();
  });

  addTechnologyButton.addEventListener('click', openTechnologyModal);
  saveTechnologyButton.addEventListener('click', requestTechnologySave);
  technologyModal.addEventListener('hidden.bs.modal', () => {
    if (editingTechnologyIndex !== null) renderTechnologies();
    resetTechnologyModal();
  });
  technologyFilePreviewModal.addEventListener('hidden.bs.modal', () => {
    technologyFilePreviewFrame.src = 'about:blank';
    if (technologyPreviewUrl) URL.revokeObjectURL(technologyPreviewUrl);
    technologyPreviewUrl = null;
  });

  technologiesTableBody.addEventListener('click', (event) => {
    const previewButton = event.target.closest('[data-technology-file-preview]');
    if (previewButton) {
      const rowIndex = Number(previewButton.closest('.technology-row').dataset.technologyIndex);
      const narrative = previewButton.dataset.technologyFilePreview;
      const file = technologies[rowIndex]?.uploadInputs?.[narrative]?.files?.[0];
      if (!file) return;
      if (technologyPreviewUrl) URL.revokeObjectURL(technologyPreviewUrl);
      technologyPreviewUrl = URL.createObjectURL(file);
      technologyFilePreviewTitle.textContent = previewButton.dataset.technologyFileName;
      technologyFilePreviewFrame.src = technologyPreviewUrl;
      bootstrap.Modal.getOrCreateInstance(technologyFilePreviewModal).show();
      return;
    }
    const actionButton = event.target.closest('[data-technology-action]');
    if (!actionButton) return;
    const index = Number(actionButton.closest('.technology-row').dataset.technologyIndex);
    if (actionButton.dataset.technologyAction === 'edit') {
      openTechnologyForEdit(index);
      return;
    }
    document.dispatchEvent(new CustomEvent('technology:delete', { detail: { index } }));
  });

  document.addEventListener('technology:save-confirmed', (event) => {
    const { technology, uploadInputs, uploadNames, editingIndex } = event.detail;
    const savedTechnology = { ...technology, uploadInputs, uploadNames };
    if (editingIndex === null) technologies.push(savedTechnology);
    else if (technologies[editingIndex]) technologies[editingIndex] = savedTechnology;
    else return;
    renderTechnologies();
    technologyModal.querySelector('[data-bs-dismiss="modal"]').click();
  });

  document.addEventListener('technology:delete-confirmed', (event) => {
    const index = Number(event.detail.index);
    if (!Number.isInteger(index) || !technologies[index] || technologies.length === 1) return;
    technologies.splice(index, 1);
    renderTechnologies();
  });

  relationshipField.addEventListener('change', () => {
    updateRelationshipFields();
    updateOwnershipFields();
  });
  ownershipField.addEventListener('change', updateOwnershipFields);
  updateRelationshipFields();
  updateOwnershipFields();
  updateCreatorRows();
  updateCosts();
  renderTechnologies();

  disclosureForm.addEventListener('change', (event) => {
    if (event.target.matches('input[type="file"]')) updateAttachmentCount();
  });
</script>
@vite(['resources/assets/js/innovation-disclosure.js'])
@endif
@endsection