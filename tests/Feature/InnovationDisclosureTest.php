<?php

namespace Tests\Feature;

use App\Models\IdfStagedUpload;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InnovationDisclosureTest extends TestCase
{
  use DatabaseTransactions;

  public function test_dashboard_requires_authentication(): void
  {
    $this->get('/dashboard')->assertRedirect('/login');
  }

  public function test_submission_status_updates_route_returns_the_authenticated_users_disclosures(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);

    $this->actingAs($user)
      ->get(route('dashboard.submissions.status-updates'))
      ->assertOk()
      ->assertExactJson([]);
  }

  public function test_authenticated_user_can_view_the_disclosure_dashboard(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);

    $this->actingAs($user)
      ->get('/dashboard')
      ->assertOk()
      ->assertSee('data-submission-notifications', false)
      ->assertSee('data-submission-notification-list', false)
      ->assertSee('ri-notification-4-line', false)
      ->assertDontSee('file-review-notification')
      ->assertSee('<span class="menu-header-text">Inventors</span>', false)
      ->assertDontSee('IP Head</span>', false)
      ->assertSee('dashboard-wide-container', false)
      ->assertSee('ipms-dashboard-footer', false)
      ->assertSee('Intellectual Property Management System')
      ->assertDontSee('made with ❤️ by')
      ->assertDontSee('Admin Templates')
      ->assertSee('Dashboard analytics')
      ->assertSee('Disclosure status')
      ->assertDontSee('name="technology_type"', false)
      ->assertDontSee('type="radio"', false)
      ->assertDontSee('CRM')
      ->assertDontSee('Layouts');

    $this->actingAs($user)
      ->get(route('dashboard.submissions.index'))
      ->assertOk()
      ->assertSee('data-submission-notifications', false)
      ->assertDontSee('file-review-notification');
  }

  public function test_authenticated_user_can_view_the_disclosure_form_separately(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);

    $this->actingAs($user)
      ->get(route('dashboard.innovation-disclosure.create'))
      ->assertOk()
      ->assertSee('dashboard-wide-container', false)
      ->assertSee('Innovation Disclosure')
      ->assertSee('Ideally, the person filling out this Innovation Disclosure Form should be the Lead Creator.')
      ->assertSee('Creator Information')
      ->assertSee('Creators and Contributions')
      ->assertSee('id="add-technology"', false)
      ->assertSee('id="technology-modal"', false)
      ->assertSee('data-post-max-size=', false)
      ->assertSee('data-upload-max-size=', false)
      ->assertSee('data-upload-url=', false)
      ->assertSee('enctype="multipart/form-data"', false)
      ->assertSee('novalidate', false)
      ->assertSee('innovation-disclosure.js?v=', false)
      ->assertDontSee('id="delete-technology-modal"', false)
      ->assertDontSee("button.disabled = action === 'delete' && technologies.length === 1;", false)
      ->assertDontSee('technologies.length === 1) return;', false)
      ->assertSee('<th scope="col" aria-label="Abstract / Summary" title="Abstract / Summary">Summary</th>', false)
      ->assertSee('<th scope="col" aria-label="Technical / Creative Field" title="Technical / Creative Field">Field</th>', false)
      ->assertSee('<th scope="col" aria-label="Background / Problem" title="Background / Problem">Background</th>', false)
      ->assertSee('<th scope="col" aria-label="Summary of the Invention / Innovation" title="Summary of the Invention / Innovation">Invention</th>', false)
      ->assertSee('<th scope="col" aria-label="Detailed Description" title="Detailed Description">Description</th>', false)
      ->assertSee('<th scope="col" aria-label="Examples / Experimental Results / Validation" title="Examples / Experimental Results / Validation">Results</th>', false)
      ->assertSee('<th scope="col" aria-label="Sample picture" title="Sample picture">Sample</th>', false)
      ->assertSee('<th scope="col" aria-label="Technical drawing" title="Technical drawing">Drawing</th>', false)
      ->assertDontSee('Add up to two technologies', false)
      ->assertDontSee('<th scope="col">Status</th>', false)
      ->assertSee('ri-check-line', false)
      ->assertSee('class="col-12 technology-title-field"', false)
      ->assertSee('class="col-12 col-md-6 technology-narrative-field"', false)
      ->assertDontSee('data-technology-field="abstract_summary"', false)
      ->assertDontSee('placeholder="Enter Text"', false)
      ->assertSee('<div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable idf-modal-dialog">', false)
      ->assertSee('class="col-12 technology-upload-divider"', false)
      ->assertSee('class="form-control pdf-upload technology-file-input" id="technology-modal-file-abstract_summary"', false)
      ->assertSee('class="form-control pdf-upload technology-file-input" id="technology-modal-file-sample-photo"', false)
      ->assertSee('class="form-control pdf-upload technology-file-input" id="technology-modal-file-technical-drawing"', false)
      ->assertSee('const replacement = fileInput.cloneNode(false);', false)
      ->assertSee('class="technology-upload-control" data-technology-upload-slot="abstract_summary"', false)
      ->assertSee('data-existing-technology-file="abstract_summary"', false)
      ->assertSee('fileInput.setAttribute(\'form\', disclosureForm.id)', false)
      ->assertSee('fileInput.name = `technologies[${index}][files][${slot}]`', false)
      ->assertDontSee('field.disabled = true;', false)
      ->assertDontSee("disclosureForm.addEventListener('formdata'", false)
      ->assertSee('replacement.id = `technology-modal-file-${narrative}`', false)
      ->assertSee('fileLabel.htmlFor = replacement.id', false)
      ->assertSee('function getTechnologyFilePicker(slot)', false)
      ->assertSee('getTechnologyFilePicker(missingRequiredFiles[0])?.focus()', false)
      ->assertSee("new CustomEvent('technology:delete'", false)
      ->assertSee('id="idf-next-page"', false)
      ->assertSee('id="submit-idf-disclosure"', false)
      ->assertSee("field.disabled = false;", false)
      ->assertDontSee('id="idf-form-actions"', false)
      ->assertSee('id="idf-wizard-steps"', false)
      ->assertSee('data-wizard-step="4"', false)
      ->assertSee('data-idf-page="4"', false)
      ->assertDontSee('idf-page-status', false)
      ->assertDontSee('idf-pages-remaining', false)
      ->assertDontSee('(optional)')
      ->assertDontSee('For more than six creators')
      ->assertSee('Sample picture (PDF)', false)
      ->assertSee('Technical drawing (PDF)', false)
      ->assertDontSee('<th scope="col">Files</th>', false)
      ->assertSee('id="file_drive_link"', false)
      ->assertSee('id="attachment-count"', false)
      ->assertSee('id="new-disclosure-form"', false)
      ->assertSee('class="technology-upload-control" data-technology-upload-slot="sample_photo"', false)
      ->assertSee('class="technology-upload-control" data-technology-upload-slot="technical_drawing"', false)
      ->assertSee('id="save-idf-submission"', false)
      ->assertSee('Save submission')
      ->assertSee('id="import-idf-button"', false)
      ->assertSee('class="visually-hidden" id="idf-draft-save-status"', false)
      ->assertSee('Import submission')
      ->assertSee('btn-success', false)
      ->assertSee('btn-label-info', false)
      ->assertSee('sessionStorage.getItem(idfSubmissionRecoveryKey)', false)
      ->assertSee('window.saveIdfDraftBeforeSubmit', false)
      ->assertSee('window.markIdfSubmissionPending', false)
      ->assertSee('clearDraftRecord();', false)
      ->assertSee('technologies[${index}][technology_title]', false)
      ->assertSee('window.showSaveFilePicker', false)
      ->assertSee("materials.push(material)", false)
      ->assertDontSee("CustomEvent('material:save-requested'", false)
      ->assertSee("format: 'ipms-idf-draft'", false)
      ->assertSee('id="technology-narrative-view-modal"', false)
      ->assertSee('id="technology-narrative-view-content"', false)
      ->assertSee('technology-view-file-card__title', false)
      ->assertSee('technologyFileLabels[narrative]', false)
      ->assertSee('reopenTechnologyViewAfterPreview', false)
      ->assertSee("['view', 'View', 'ri-eye-line']", false)
      ->assertDontSee("viewButton.dataset.technologyAction = 'view'", false)
      ->assertSee('technology-file-status-cell', false)
      ->assertSee('technology-file-check', false)
      ->assertSee("accordion accordion-flush border rounded mb-3", false)
      ->assertSee("'PDF DOCUMENT'", false)
      ->assertSee('ri-file-pdf-2-line', false)
      ->assertSee("title: 'Remove this PDF?'", false)
      ->assertSee("confirmButtonText: 'Yes, remove PDF'", false)
      ->assertSee('technology-remove-file', false)
      ->assertSee('data-technology-file="abstract_summary"', false)
      ->assertSee('id="creator-modal"', false)
      ->assertSee('id="creator-view-modal"', false)
      ->assertSee('id="inventors-table-body"', false)
      ->assertSee('Declared Contribution')
      ->assertSee('Technical Contribution')
      ->assertSee('Last Name')
      ->assertSee('First Name')
      ->assertSee('Email Address')
      ->assertSee('data-creator-view-value="technical_contribution"', false)
      ->assertSee('class="modal fade creator-entry-modal" id="creator-modal"', false)
      ->assertSee('class="modal fade creator-list-modal" id="creator-staged-modal"', false)
      ->assertSee('creator-modal-panel', false)
      ->assertSee('id="staged-creators-table-body"', false)
      ->assertSee('id="save-creators"', false)
      ->assertSee('Save Creators')
      ->assertSee('id="save-creator"', false)
      ->assertSee('>Add</button>', false)
      ->assertDontSee('id="creator-modal-signature"', false)
      ->assertDontSee('id="creator-modal-date"', false)
      ->assertDontSee('declaration_confirmed')
      ->assertDontSee('I confirm')
      ->assertSee('data-bs-toggle="modal"', false)
      ->assertSee('Nature of contribution')
      ->assertSee('Ownership and University Support Facts')
      ->assertSee('Technical / Creative Description')
      ->assertSee('Costing of Materials and Resources')
      ->assertSee('name="technology_type"', false)
      ->assertSee('value="mechanical"', false)
      ->assertSee('Mechanical (IT, IoT &amp; Engineering)', false)
      ->assertSee('value="chemical"', false)
      ->assertSee('Chemical (Food, Pharmaceutical, Structural Chemistry)', false)
      ->assertDontSee('value="mechanical_engineering"', false)
      ->assertSee('name="attachments[sample_photo]"', false)
      ->assertSee('accept="application/pdf,.pdf"', false)
      ->assertDontSee('Disclosure status');
  }

  public function test_user_can_view_submissions_and_download_their_files(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $file = UploadedFile::fake()->create('methodology.pdf', 20, 'application/pdf');
    $path = $file->store("innovation-disclosures/{$disclosure->id}/methodology", 'local');
    $attachment = $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => $path,
      'mime_type' => 'application/pdf',
      'size_bytes' => 20480,
    ]);

    $this->actingAs($user)
      ->get(route('dashboard.submissions.index'))
      ->assertOk()
      ->assertSee('My Submission')
      ->assertSee('Low-cost water filtration device')
      ->assertSee('class="table table-hover align-middle mb-0 inventor-submissions-table"', false)
      ->assertSee('submission-action-trigger', false)
      ->assertSee('>View', false)
      ->assertSee(route('dashboard.submissions.show', $disclosure), false)
      ->assertSee('inventor-submissions-table__title', false)
      ->assertSee($disclosure->technology_title)
      ->assertSee('Mechanical')
      ->assertSee('<th>Department</th>', false)
      ->assertSee($disclosure->college_department)
      ->assertSee('class="submission-table__single-line"', false)
      ->assertDontSee('<th>Submitter</th>', false)
      ->assertDontSee('data-disclosure-review-note', false)
      ->assertDontSee('Revision requested:</strong>', false);

    $this->actingAs($user)
      ->get(route('dashboard.submissions.attachments.download', [$disclosure, $attachment]))
      ->assertDownload('methodology.pdf');
  }

  public function test_user_can_open_their_submission_detail_page(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $disclosure->update([
      'research_title' => 'Community Water Research Program',
      'campus' => 'Main',
      'college_department' => 'FAS',
      'lead_creator_name' => 'Conflicting legacy lead',
    ]);
    $technology = $disclosure->technologies()->create([
      'technology_title' => 'Community water filter',
      'abstract_summary' => 'A compact filter for community water systems.',
      'detailed_description' => 'A detailed implementation using layered filter media.',
    ]);
    $narrativePath = "innovation-disclosures/{$disclosure->id}/technologies/{$technology->id}/detailed_description/details.pdf";
    Storage::disk('local')->put($narrativePath, '%PDF-1.4 technology narrative');
    $narrativeFile = $technology->files()->create([
      'narrative_key' => 'detailed_description',
      'original_name' => 'details.pdf',
      'stored_path' => $narrativePath,
      'mime_type' => 'application/pdf',
      'size_bytes' => 29,
      'review_status' => 'revision_requested',
      'revision_notes' => 'Please add the missing implementation details.',
    ]);
    $disclosure->inventors()->create([
      'first_name' => 'Juan',
      'last_name' => 'Dela Cruz',
      'email' => $user->email,
      'country_of_citizenship' => 'Philippines',
      'affiliation' => 'student',
      'position_affiliation' => 'Student researcher',
      'technical_contribution' => 'Designed and tested the filter.',
      'contribution_percent' => '100.00',
      'contribution_nature' => ['conception'],
      'signature_name' => 'Juan Dela Cruz',
      'declaration_confirmed' => true,
      'declaration_date' => now()->toDateString(),
      'is_primary' => true,
    ]);
    $disclosure->costItems()->create([
      'item' => 'Filter media',
      'description' => 'Replacement filter media',
      'quantity' => '2.00',
      'unit_cost' => '150.00',
      'total_cost' => '300.00',
      'purpose' => 'Prototype testing',
    ]);
    $path = "innovation-disclosures/{$disclosure->id}/methodology/methodology.pdf";
    Storage::disk('local')->put($path, '%PDF-1.4 preview fixture');
    $attachment = $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => $path,
      'mime_type' => 'application/pdf',
      'size_bytes' => 24,
    ]);
    $attachment->events()->create([
      'event_type' => 'uploaded',
      'version' => 1,
      'original_name' => 'methodology.pdf',
      'occurred_at' => now()->subDays(2),
    ]);
    $attachment->events()->create([
      'event_type' => 'revision_requested',
      'version' => 1,
      'original_name' => 'methodology.pdf',
      'revision_notes' => 'Please provide a signed copy.',
      'occurred_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('Submission Review')
      ->assertDontSee('Technology title')
      ->assertDontSee('submission-review-nav', false)
      ->assertDontSee('id="submission-overview-title"', false)
      ->assertSee('Submission overview')
      ->assertSee('Submitted technologies')
      ->assertSee('Creators and contributions')
      ->assertSee('Costing of materials and resources')
      ->assertSeeInOrder(['Creators and contributions', 'Costing of materials and resources', 'Submitted technologies'])
      ->assertSee('class="inventor-technology-grid"', false)
      ->assertDontSee('Technology card pagination')
      ->assertSee('class="inventor-technology-grid"', false)
      ->assertDontSee('inventor-supporting-file', false)
      ->assertDontSee('technology-table review-files-table', false)
      ->assertSee('data-file-revision-note="Please add the missing implementation details."', false)
      ->assertDontSee('>Technology Title</th>', false)
      ->assertSee('Detailed Description')
      ->assertSee('details.pdf')
      ->assertSee('id="submission-review-title">Community Water Research Program</h3>', false)
      ->assertSee('class="submission-review-hero__layout"', false)
      ->assertSee('submission-review-hero__timeline', false)
      ->assertSee('submission-review-hero__actions', false)
      ->assertDontSee('class="avatar avatar-md flex-shrink-0"', false)
      ->assertDontSee('<span class="small text-body-secondary">Submitted ', false)
      ->assertSee('class="card submission-review-tracker"', false)
      ->assertSee('submission-review-stage-banner', false)
      ->assertSee('data-timeline-narrative-trigger="2"', false)
      ->assertSee('aria-pressed="true"', false)
      ->assertSee('Submission received.')
      ->assertSee('submission-review-hero__subtitle-item', false)
      ->assertSee('<i class="icon-base ri ri-map-pin-2-line" aria-hidden="true"></i>Main', false)
      ->assertSee('<i class="icon-base ri ri-building-4-line" aria-hidden="true"></i>FAS', false)
      ->assertSee('aria-live="polite"', false)
      ->assertDontSee('id="submission-timeline-title"', false)
      ->assertSee('<section class="submission-review-overview submission-review-section mb-4" id="submission-overview"', false)
      ->assertSee('class="card submission-review-overview-card"', false)
      ->assertSee('class="row g-0 submission-review-groups"', false)
      ->assertDontSee('<article class="card mb-4 submission-review-section" id="submission-overview"', false)
      ->assertSee('data-review-date="creation"', false)
      ->assertSee('data-review-date="approval"', false)
      ->assertSee('data-review-date="disclosure"', false)
      ->assertDontSee('submission-review-group-stack', false)
      ->assertSee('class="submission-review-stepper"', false)
      ->assertDontSee('data-timeline-step="created"', false)
      ->assertSee('View history')
      ->assertSee('submission-detailed-timeline-' . $disclosure->id, false)
      ->assertDontSee('submission-review-group submission-review-timeline', false)
      ->assertSee('submission-review-important-dates', false)
      ->assertDontSee('submission-review-date-stack', false)
      ->assertDontSee('Research Approved')
      ->assertSee('IDF Submitted')
      ->assertSee('data-timeline-step="review"', false)
      ->assertSee('data-timeline-step="approved"', false)
      ->assertSee('Submitted for Prior Art Search')
      ->assertSee('data-timeline-step="prior-art-search"', false)
      ->assertSee('IDF Applied to IPOPHL')
      ->assertSee('data-review-date="creation"', false)
      ->assertSee('Disclosure and contact')
      ->assertSee('Lead creator')
      ->assertDontSee('Conflicting legacy lead')
      ->assertSee('Research and ownership')
      ->assertDontSee('College / department')
      ->assertDontSee('Campus</span>', false)
      ->assertSee('Important dates')
      ->assertDontSee('technology-table review-files-table', false)
      ->assertDontSee('inventor-supporting-file', false)
      ->assertSee('class="table table-hover align-middle inventors-table mb-0"', false)
      ->assertSee('materials-table submission-review-costing-table', false)
      ->assertSee('data-review-section="technologies"', false)
      ->assertSee('data-review-section="costing"', false)
      ->assertSee('Community water filter')
      ->assertSee('Submitted technologies')
      ->assertSee('inventor-technology-card', false)
      ->assertSee('data-technology-file-open', false)
      ->assertSee('For Review')
      ->assertDontSee('submission-technology-view-', false)
      ->assertDontSee('>View details</button>', false)
      ->assertDontSee('A compact filter for community water systems.')
      ->assertSee('Detailed Description')
      ->assertSee('details.pdf')
      ->assertSee(route('dashboard.submissions.technology-files.preview', [$disclosure, $narrativeFile]), false)
      ->assertSee('submission-creator-view-' . $disclosure->id, false)
      ->assertSee('Juan Dela Cruz')
      ->assertSee('Declared Contribution')
      ->assertSee('Filter media')
      ->assertDontSee('Save technical / creative changes')
      ->assertDontSee('Save creator changes')
      ->assertDontSee('Save costing changes')
      ->assertDontSee('Supporting files and revisions')
      ->assertSee('File uploaded')
      ->assertSee('Revision requested')
      ->assertSee('Please provide a signed copy.')
      ->assertSee('is-revision', false)
      ->assertDontSee('inventor-supporting-file', false)
      ->assertDontSee('data-review-section="files"', false);

    $disclosure->update(['status' => 'returned']);
    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('Revision Requested')
      ->assertSee('Please review the requested changes')
      ->assertSee('class="submission-review-stepper__step is-current is-selected" data-timeline-step="review"', false);

    $disclosure->update(['status' => 'revised_submission']);
    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('Revision submitted')
      ->assertSee('class="submission-review-stepper__step is-current is-selected" data-timeline-step="review"', false);

    $disclosure->update(['status' => 'endorsed', 'endorsed_at' => now()]);
    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('>Approved</span>', false)
      ->assertSee('class="submission-review-stepper__step is-current is-selected" data-timeline-step="prior-art-search"', false);

    $disclosure->update(['status' => 'filed']);
    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('class="submission-review-stepper__step is-current is-selected" data-timeline-step="ipophl-applied"', false);
  }

  public function test_submission_review_displays_at_most_three_technology_cards_without_pagination(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    foreach (range(1, 5) as $number) {
      $disclosure->technologies()->create([
        'technology_title' => "Technology entry {$number}",
      ]);
    }

    $response = $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('Technology entry 1')
      ->assertSee('Technology entry 2')
      ->assertSee('Technology entry 3')
      ->assertDontSee('Technology entry 4')
      ->assertDontSee('Technology card pagination');

    $this->assertSame(3, substr_count($response->getContent(), 'class="inventor-technology-grid__item"'));
  }

  public function test_revision_requested_technology_file_can_be_replaced_from_its_review_dialog(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $technology = $disclosure->technologies()->create([
      'technology_title' => 'Community water filter',
      'abstract_summary' => 'Abstract',
    ]);
    $oldPath = "innovation-disclosures/{$disclosure->id}/technologies/{$technology->id}/abstract_summary/abstract.pdf";
    Storage::disk('local')->put($oldPath, '%PDF-1.4 original technology file');
    $technologyFile = $technology->files()->create([
      'narrative_key' => 'abstract_summary',
      'original_name' => 'abstract.pdf',
      'stored_path' => $oldPath,
      'mime_type' => 'application/pdf',
      'size_bytes' => 32,
      'review_status' => 'revision_requested',
      'revision_notes' => 'Please clarify the measurable result.',
    ]);
    $remainingFile = $technology->files()->create([
      'narrative_key' => 'technical_field',
      'original_name' => 'technical-field.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/technologies/{$technology->id}/technical_field/technical-field.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 28,
      'review_status' => 'revision_requested',
      'revision_notes' => 'Please clarify the field.',
    ]);

    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('Revision requested')
      ->assertSee('data-file-input-id="technology-file-revision-' . $technologyFile->id . '"', false)
      ->assertSee('name="file"', false)
      ->assertSee(route('dashboard.submissions.technology-files.revision', [$disclosure, $technology, $technologyFile]), false)
      ->assertDontSee('Supporting files');

    $this->actingAs($user)
      ->post(route('dashboard.submissions.technology-files.revision', [$disclosure, $technology, $technologyFile]), [
        'file' => UploadedFile::fake()->create('abstract-revised.pdf', 20, 'application/pdf'),
        'submission_note' => 'Updated the measured efficiency result.',
      ])
      ->assertRedirect(route('dashboard.submissions.show', $disclosure))
      ->assertSessionHas('toast_type', 'success');

    $technologyFile->refresh();
    $this->assertSame('abstract-revised.pdf', $technologyFile->original_name);
    $this->assertSame(2, $technologyFile->version);
    $this->assertSame('review_needed', $technologyFile->review_status);
    $this->assertSame('Updated the measured efficiency result.', $technologyFile->submission_note);
    $this->assertSame('returned', $disclosure->fresh()->status);
    $this->assertTrue(Storage::disk('local')->exists($oldPath));
    $this->assertSame(1, $technologyFile->versions()->count());
    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('Revised · In review')
      ->assertSee('Revised v2')
      ->assertSee('data-file-input-id="technology-file-revision-' . $remainingFile->id . '"', false)
      ->assertDontSee('name="inventors[', false)
      ->assertDontSee('Save creator changes')
      ->assertDontSee('Save technical / creative changes')
      ->assertSee('Please clarify the field.');
    $this->actingAs($user)
      ->post(route('dashboard.submissions.revision-details', $disclosure), ['section' => 'inventors'])
      ->assertStatus(409);

    $technologyFile->update(['review_status' => 'revision_requested']);
    $disclosure->update(['status' => 'filed']);
    $this->actingAs($user)
      ->post(route('dashboard.submissions.technology-files.revision', [$disclosure, $technology, $technologyFile]), [
        'file' => UploadedFile::fake()->create('blocked-revision.pdf', 20, 'application/pdf'),
      ])
      ->assertStatus(409);
  }

  public function test_returned_submission_can_update_existing_review_table_rows(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $disclosure->update(['status' => 'returned']);
    $technology = $disclosure->technologies()->create([
      'technology_title' => 'Community water filter',
      'abstract_summary' => 'Abstract',
      'technical_field' => 'Water treatment',
      'background_summary' => 'Background',
      'invention_summary' => 'Summary',
      'detailed_description' => 'Description',
      'examples_results' => 'Results',
    ]);
    $existingTechnologyPdf = UploadedFile::fake()->create('abstract-review.pdf', 5, 'application/pdf');
    $technologyPdfPath = $existingTechnologyPdf->store(
      "innovation-disclosures/{$disclosure->id}/technologies/{$technology->id}/abstract_summary",
      'local'
    );
    $technologyFile = $technology->files()->create([
      'narrative_key' => 'abstract_summary',
      'original_name' => $existingTechnologyPdf->getClientOriginalName(),
      'stored_path' => $technologyPdfPath,
      'mime_type' => 'application/pdf',
      'size_bytes' => 5120,
      'review_status' => 'review_needed',
    ]);
    $inventor = $disclosure->inventors()->create([
      'first_name' => 'Juan',
      'middle_initial' => 'P',
      'last_name' => 'Dela Cruz',
      'suffix' => '',
      'full_name' => 'Dela Cruz, Juan P.',
      'email' => 'juan@example.com',
      'country_of_citizenship' => 'Philippines',
      'affiliation' => 'Student researcher',
      'position_affiliation' => 'Student researcher',
      'technical_contribution' => 'Designed and tested the filter.',
      'contribution_percent' => '100.00',
      'contribution_nature' => ['conception'],
      'is_primary' => true,
    ]);
    $costItem = $disclosure->costItems()->create([
      'item' => 'Filter media',
      'description' => 'Replacement filter media',
      'quantity' => '2.00',
      'unit_cost' => '150.00',
      'total_cost' => '300.00',
      'purpose' => 'Prototype testing',
    ]);

    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertDontSee('Save technical / creative changes')
      ->assertDontSee('Save creator changes')
      ->assertDontSee('Save costing changes')
      ->assertSee('<table class="table table-hover align-middle inventors-table mb-0">', false)
      ->assertDontSee('name="inventors[', false)
      ->assertDontSee('name="cost_items[', false)
      ->assertSee('abstract-review.pdf')
      ->assertSee('data-technology-file-open', false)
      ->assertDontSee('>View details</button>', false)
      ->assertDontSee('submission-technology-view-', false)
      ->assertDontSee('name="technologies[' . $technology->id . '][abstract_summary]"', false)
      ->assertDontSee('<textarea class="form-control" name="technologies[' . $technology->id . '][abstract_summary]"', false)
      ->assertDontSee('class="submission-revision-details-form"', false);

    $technologyFiles = [];
    foreach (['abstract_summary', 'technical_field', 'background_summary', 'invention_summary', 'detailed_description', 'examples_results', 'sample_photo', 'technical_drawing'] as $narrative) {
      $technologyFiles[$narrative] = UploadedFile::fake()->create("updated-{$narrative}.pdf", 5, 'application/pdf');
    }
    $this->post(route('dashboard.submissions.revision-details', $disclosure), [
      'section' => 'technologies',
      'technologies' => [
        $technology->id => [
          'technology_title' => 'Improved community filter',
          'files' => $technologyFiles,
          'file_notes' => ['abstract_summary' => 'Updated abstract attached.'],
        ],
      ],
    ])->assertRedirect(route('dashboard.submissions.show', $disclosure));
    foreach ($technologyFiles as $narrative => $file) {
      $this->assertDatabaseHas('disclosure_technology_files', [
        'disclosure_technology_id' => $technology->id,
        'narrative_key' => $narrative,
        'original_name' => $file->getClientOriginalName(),
        'mime_type' => 'application/pdf',
        'review_status' => 'review_needed',
      ]);
      $storedPath = DB::table('disclosure_technology_files')
        ->where('disclosure_technology_id', $technology->id)
        ->where('narrative_key', $narrative)
        ->value('stored_path');
      $this->assertTrue(Storage::disk('local')->exists($storedPath));
    }
    $this->assertDatabaseHas('disclosure_technology_files', [
      'id' => $technology->files()->where('narrative_key', 'abstract_summary')->value('id'),
      'version' => 2,
      'submission_note' => 'Updated abstract attached.',
    ]);
    $this->assertDatabaseHas('disclosure_technology_file_versions', [
      'disclosure_technology_file_id' => $technology->files()->where('narrative_key', 'abstract_summary')->value('id'),
      'version' => 1,
      'original_name' => 'abstract-review.pdf',
    ]);
    $technologyFile = $technology->files()->where('narrative_key', 'abstract_summary')->firstOrFail();
    $previousVersion = $technologyFile->versions()->firstOrFail();
    $this->assertTrue(Storage::disk('local')->exists($previousVersion->stored_path));
    $this->actingAs($user)
      ->get(route('dashboard.submissions.technology-files.versions.preview', [$disclosure, $technologyFile, $previousVersion]))
      ->assertOk()
      ->assertHeader('Content-Type', 'application/pdf')
      ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
    $this->actingAs($user)
      ->get(route('dashboard.submissions.technology-files.preview', [
        $disclosure,
        $technologyFile,
        'v' => $technologyFile->version,
      ]))
      ->assertOk()
      ->assertHeader('Content-Type', 'application/pdf')
      ->assertHeader('Cache-Control', 'must-revalidate, no-cache, no-store, private');
    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('data-file-preview-url="' . route('dashboard.submissions.technology-files.preview', [
        $disclosure,
        $technologyFile,
        'v' => $technologyFile->version,
      ]) . '"', false);
    $this->assertDatabaseHas('disclosure_technologies', [
      'id' => $technology->id,
      'abstract_summary' => '',
    ]);

    $this->post(route('dashboard.submissions.revision-details', $disclosure), [
      'section' => 'inventors',
      'inventors' => [
        $inventor->id => [
          'first_name' => 'Jane',
          'middle_initial' => 'Q',
          'last_name' => 'Doe',
          'suffix' => '',
          'email' => 'jane@example.com',
          'country_of_citizenship' => 'Philippines',
          'position_affiliation' => 'Faculty researcher',
          'technical_contribution' => 'Updated contribution details.',
          'contribution_percent' => '100.00',
          'contribution_nature' => ['technical_feature'],
          'contribution_other' => '',
          'signature_name' => 'Jane Q. Doe',
          'declaration_confirmed' => '1',
          'declaration_date' => '2026-10-09',
        ],
      ],
    ])->assertRedirect(route('dashboard.submissions.show', $disclosure));

    $this->post(route('dashboard.submissions.revision-details', $disclosure), [
      'section' => 'cost_items',
      'cost_items' => [
        $costItem->id => [
          'item' => 'Updated filter media',
          'description' => 'Updated replacement media',
          'quantity' => '3.00',
          'unit_cost' => '50.55',
          'purpose' => 'Updated testing',
        ],
      ],
    ])->assertRedirect(route('dashboard.submissions.show', $disclosure));

    $this->assertDatabaseHas('disclosure_technologies', [
      'id' => $technology->id,
      'technology_title' => 'Improved community filter',
      'abstract_summary' => '',
    ]);
    $this->assertDatabaseHas('inventors', [
      'id' => $inventor->id,
      'first_name' => 'Jane',
      'full_name' => 'Doe, Jane Q.',
    ]);
    $this->assertDatabaseHas('disclosure_cost_items', [
      'id' => $costItem->id,
      'item' => 'Updated filter media',
      'total_cost' => '151.65',
    ]);
    $this->assertSame('151.65', $disclosure->fresh()->total_development_cost);
    $this->assertSame('returned', $disclosure->fresh()->status);
  }

  public function test_returned_technology_edits_require_pdfs_and_ignore_legacy_text_fields(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $disclosure->update(['status' => 'returned']);
    $technology = $disclosure->technologies()->create([
      'technology_title' => 'Community water filter',
    ]);

    $this->actingAs($user)
      ->from(route('dashboard.submissions.show', $disclosure))
      ->post(route('dashboard.submissions.revision-details', $disclosure), [
        'section' => 'technologies',
        'technologies' => [
          $technology->id => [
            'technology_title' => 'Updated water filter',
            'abstract_summary' => 'This legacy text must not replace the required PDF.',
          ],
        ],
      ])
      ->assertSessionHasErrors("technologies.{$technology->id}.files.abstract_summary");

    $this->assertDatabaseHas('disclosure_technologies', [
      'id' => $technology->id,
      'technology_title' => 'Community water filter',
      'abstract_summary' => null,
    ]);
  }

  public function test_submission_details_cannot_be_edited_before_revision_is_requested(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);

    $this->actingAs($user)
      ->post(route('dashboard.submissions.revision-details', $disclosure), [
        'section' => 'cost_items',
      ])
      ->assertStatus(409);
  }

  public function test_revision_detail_edits_cannot_add_new_rows(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $disclosure->update(['status' => 'returned']);
    $technology = $disclosure->technologies()->create([
      'technology_title' => 'Community water filter',
      'abstract_summary' => 'Abstract',
      'technical_field' => 'Water treatment',
      'background_summary' => 'Background',
      'invention_summary' => 'Summary',
      'detailed_description' => 'Description',
      'examples_results' => 'Results',
    ]);

    $this->actingAs($user)
      ->post(route('dashboard.submissions.revision-details', $disclosure), [
        'section' => 'technologies',
        'technologies' => [
          $technology->id => [
            'technology_title' => $technology->technology_title,
          ],
          999999 => [
            'technology_title' => 'Unauthorized added technology',
          ],
        ],
      ])
      ->assertStatus(422);

    $this->assertSame(1, $disclosure->technologies()->count());
    $this->assertDatabaseMissing('disclosure_technologies', [
      'technology_title' => 'Unauthorized added technology',
    ]);
  }

  public function test_review_submission_has_one_update_button_for_requested_files(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $disclosure->update([
      'status' => 'returned',
      'review_notes' => 'Please revise the requested files.',
    ]);
    $path = "innovation-disclosures/{$disclosure->id}/methodology/methodology.pdf";
    Storage::disk('local')->put($path, '%PDF-1.4 preview fixture');
    $attachment = $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => $path,
      'mime_type' => 'application/pdf',
      'size_bytes' => 24,
      'needs_revision' => true,
      'revision_notes' => 'Please revise this document.',
    ]);

    $response = $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertDontSee('name="attachments[methodology]"', false)
      ->assertDontSee('inventor-supporting-file', false)
      ->assertDontSee('Update File');

    $this->assertSame(0, substr_count($response->getContent(), 'class="attachment-update-form"'));
    $this->assertStringNotContainsString('Back to submissions', $response->getContent());
    $this->assertDatabaseHas('disclosure_attachments', [
      'id' => $attachment->id,
      'needs_revision' => true,
    ]);
  }

  public function test_user_can_preview_owned_pdf_attachments_inline(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $path = "innovation-disclosures/{$disclosure->id}/methodology/methodology.pdf";
    Storage::disk('local')->put($path, '%PDF-1.4 preview fixture');
    $attachment = $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => $path,
      'mime_type' => 'application/pdf',
      'size_bytes' => 24,
    ]);

    $response = $this->actingAs($user)->get(
      route('dashboard.submissions.attachments.preview', [$disclosure, $attachment])
    );

    $response->assertOk()
      ->assertHeader('Content-Type', 'application/pdf')
      ->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));

    $otherUser = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $this->actingAs($otherUser)
      ->get(route('dashboard.submissions.attachments.preview', [$disclosure, $attachment]))
      ->assertNotFound();
  }

  public function test_user_can_update_only_ip_head_requested_files_after_review(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $oldFile = UploadedFile::fake()->create('methodology.pdf', 20, 'application/pdf');
    $oldPath = $oldFile->store("innovation-disclosures/{$disclosure->id}/methodology", 'local');
    $attachment = $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => $oldPath,
      'mime_type' => 'application/pdf',
      'size_bytes' => 20480,
    ]);

    $this->actingAs($user)
      ->post(route('dashboard.submissions.attachments.store', $disclosure), [
        'file' => UploadedFile::fake()->create('methodology-revised.pdf', 15, 'application/pdf'),
      ])
      ->assertStatus(409);

    $disclosure->update(['status' => 'returned']);
    $attachment->update([
      'needs_revision' => true,
      'revision_notes' => 'Please revise this document.',
    ]);
    $drawingPath = "innovation-disclosures/{$disclosure->id}/drawings/drawing.png";
    Storage::disk('local')->put($drawingPath, 'original drawing');
    $drawingAttachment = $disclosure->attachments()->create([
      'type' => 'drawings',
      'original_name' => 'drawing.png',
      'stored_path' => $drawingPath,
      'mime_type' => 'image/png',
      'size_bytes' => 16,
      'needs_revision' => true,
      'revision_notes' => 'Please update this drawing.',
    ]);

    $this->actingAs($user)
      ->post(route('dashboard.submissions.attachments.store', $disclosure), [
        'return_to_review' => '1',
        'attachments' => [
          'methodology' => UploadedFile::fake()->create('methodology-revised.pdf', 15, 'application/pdf'),
          'drawings' => UploadedFile::fake()->create('drawing-revised.pdf', 15, 'application/pdf'),
        ],
      ])
      ->assertRedirect(route('dashboard.submissions.show', $disclosure))
      ->assertSessionHas('toast_type', 'success');

    $attachment->refresh();
    $this->assertSame('methodology-revised.pdf', $attachment->original_name);
    $this->assertSame(2, $attachment->version);
    $this->assertFalse($attachment->needs_revision);
    $this->assertNull($attachment->revision_notes);
    $drawingAttachment->refresh();
    $this->assertSame('drawing-revised.pdf', $drawingAttachment->original_name);
    $this->assertSame(2, $drawingAttachment->version);
    $this->assertFalse($drawingAttachment->needs_revision);
    $this->assertSame('revised_submission', $disclosure->fresh()->status);
    $this->assertNull($disclosure->fresh()->review_notes);
    $this->assertSame(2, $disclosure->attachments()->count());
    $this->assertFalse(Storage::disk('local')->exists($oldPath));
    $this->assertFalse(Storage::disk('local')->exists($drawingPath));
    $this->assertTrue(Storage::disk('local')->exists($attachment->stored_path));
    $this->assertTrue(Storage::disk('local')->exists($drawingAttachment->stored_path));
    $this->assertDatabaseHas('disclosure_attachment_events', [
      'disclosure_attachment_id' => $attachment->id,
      'event_type' => 'uploaded',
      'version' => 2,
      'original_name' => 'methodology-revised.pdf',
    ]);
    $this->assertDatabaseHas('disclosure_attachment_events', [
      'disclosure_attachment_id' => $drawingAttachment->id,
      'event_type' => 'uploaded',
      'version' => 2,
      'original_name' => 'drawing-revised.pdf',
    ]);

    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('Revised Submission')
      ->assertDontSee('inventor-supporting-file', false)
      ->assertSee('class="alert alert-warning mb-4 d-none"', false);

    $this->actingAs($user)
      ->get(route('dashboard.submissions.index'))
      ->assertOk()
      ->assertSee('Revised Submission');

    $this->actingAs($user)
      ->get(route('dashboard.submissions.status-updates'))
      ->assertOk()
      ->assertJsonPath('0.status', 'revised_submission')
      ->assertJsonPath('0.attachments.0.is_latest_submission', true);

    $otherUser = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $this->actingAs($otherUser)
      ->get(route('dashboard.submissions.attachments.download', [$disclosure, $attachment]))
      ->assertNotFound();
  }

  public function test_user_cannot_replace_a_file_not_flagged_for_revision(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $disclosure->update(['status' => 'under_review']);
    $file = UploadedFile::fake()->create('methodology.pdf', 20, 'application/pdf');
    $path = $file->store("innovation-disclosures/{$disclosure->id}/methodology", 'local');
    $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => $path,
      'mime_type' => 'application/pdf',
      'size_bytes' => 20480,
      'needs_revision' => false,
    ]);

    $this->actingAs($user)
      ->post(route('dashboard.submissions.attachments.store', $disclosure), [
        'attachments' => [
          'methodology' => UploadedFile::fake()->create('unrequested-revision.pdf', 15, 'application/pdf'),
        ],
      ])
      ->assertStatus(409);
  }

  public function test_submission_remains_revision_requested_until_all_flagged_files_are_updated(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $disclosure->update([
      'status' => 'returned',
      'review_notes' => 'Please revise both files.',
    ]);
    $attachmentRows = [];

    foreach (['methodology' => 'methodology.pdf', 'assistance_form' => 'assistance.pdf'] as $type => $filename) {
      $path = "innovation-disclosures/{$disclosure->id}/{$type}/{$filename}";
      Storage::disk('local')->put($path, '%PDF-1.4 original');
      $attachmentRows[$type] = $disclosure->attachments()->create([
        'type' => $type,
        'original_name' => $filename,
        'stored_path' => $path,
        'mime_type' => 'application/pdf',
        'size_bytes' => 16,
        'needs_revision' => true,
        'revision_notes' => 'Please revise this document.',
      ]);
    }

    $this->actingAs($user)
      ->post(route('dashboard.submissions.attachments.store', $disclosure), [
        'attachments' => [
          'methodology' => UploadedFile::fake()->create('methodology-revised.pdf', 10, 'application/pdf'),
        ],
      ])
      ->assertRedirect(route('dashboard.submissions.index'));

    $this->assertSame('returned', $disclosure->fresh()->status);
    $this->assertSame('Please revise both files.', $disclosure->fresh()->review_notes);
    $this->assertFalse($attachmentRows['methodology']->fresh()->needs_revision);
    $this->assertTrue($attachmentRows['assistance_form']->fresh()->needs_revision);
  }

  public function test_user_cannot_delete_a_disclosure_from_the_submission_review(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $disclosure = $this->makeDisclosure($user);
    $file = UploadedFile::fake()->create('methodology.pdf', 20, 'application/pdf');
    $storedPath = $file->store("innovation-disclosures/{$disclosure->id}/methodology", 'local');
    $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => $storedPath,
      'mime_type' => 'application/pdf',
      'size_bytes' => 20480,
    ]);

    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertDontSee('Delete disclosure')
      ->assertDontSee('disclosure-delete-form', false);

    $this->actingAs($user)
      ->delete("/dashboard/submissions/{$disclosure->id}")
      ->assertMethodNotAllowed();

    $this->assertNull(DB::table('innovation_disclosures')->where('id', $disclosure->id)->value('deleted_at'));
    $this->assertTrue(Storage::disk('local')->exists($storedPath));
    $this->assertDatabaseHas('disclosure_attachments', [
      'innovation_disclosure_id' => $disclosure->id,
      'stored_path' => $storedPath,
    ]);
  }

  public function test_authenticated_user_can_submit_an_innovation_disclosure(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    foreach ($data['inventors'] as &$inventor) {
      unset(
        $inventor['signature_name'],
        $inventor['declaration_confirmed'],
        $inventor['declaration_date']
      );
    }
    unset($inventor);
    $data['technologies'][] = [
      'technology_title' => 'Solar-powered water quality monitor',
      'files' => [
        'abstract_summary' => UploadedFile::fake()->create('monitor-abstract.pdf', 20, 'application/pdf'),
        'technical_field' => UploadedFile::fake()->create('monitor-field.pdf', 20, 'application/pdf'),
        'background_summary' => UploadedFile::fake()->create('monitor-background.pdf', 20, 'application/pdf'),
        'invention_summary' => UploadedFile::fake()->create('monitor-invention-summary.pdf', 20, 'application/pdf'),
        'detailed_description' => UploadedFile::fake()->create('monitor-description.pdf', 20, 'application/pdf'),
        'examples_results' => UploadedFile::fake()->create('monitor-results.pdf', 20, 'application/pdf'),
        'sample_photo' => UploadedFile::fake()->create('monitor-picture.pdf', 20, 'application/pdf'),
        'technical_drawing' => UploadedFile::fake()->create('monitor-drawing.pdf', 20, 'application/pdf'),
      ],
    ];
    $data['attachments'] = [
      'sample_photo' => UploadedFile::fake()->create('sample-photo.pdf', 20, 'application/pdf'),
    ];

    $response = $this->actingAs($user)->post('/dashboard/innovation-disclosures', $data);

    $response->assertRedirect(route('dashboard.submissions.index'));
    $this->actingAs($user)
      ->get(route('dashboard.submissions.index'))
      ->assertOk()
      ->assertSee("indexedDB.open('ipms-idf-drafts', 1)", false)
      ->assertSee('idf-draft-user-' . $user->id, false);
    $this->assertDatabaseHas('innovation_disclosures', [
      'user_id' => $user->id,
      'email' => 'inventor@example.com',
      'technology_title' => 'Low-cost water filtration device',
      'status' => 'submitted',
    ]);
    $this->assertDatabaseHas('inventors', [
      'innovation_disclosure_id' => DB::table('innovation_disclosures')->where('technology_title', 'Low-cost water filtration device')->value('id'),
      'first_name' => 'Juan',
      'middle_initial' => 'P',
      'is_primary' => true,
    ]);
    $this->assertDatabaseHas('disclosure_attachments', [
      'type' => 'sample_photo',
      'original_name' => 'sample-photo.pdf',
      'is_latest_submission' => true,
    ]);
    $attachmentId = DB::table('disclosure_attachments')
      ->where('original_name', 'sample-photo.pdf')
      ->value('id');
    $this->assertDatabaseHas('disclosure_attachment_events', [
      'disclosure_attachment_id' => $attachmentId,
      'event_type' => 'uploaded',
      'version' => 1,
      'original_name' => 'sample-photo.pdf',
    ]);
    $storedPath = DB::table('disclosure_attachments')
      ->where('original_name', 'sample-photo.pdf')
      ->value('stored_path');
    $this->assertTrue(Storage::disk('local')->exists($storedPath));
    $this->assertDatabaseHas('innovation_disclosures', [
      'user_id' => $user->id,
      'lead_creator_name' => 'Juan P. Dela Cruz',
      'campus' => 'Main Campus',
      'disclosure_date' => now()->toDateString(),
      'total_development_cost' => '1250.00',
    ]);
    $this->assertDatabaseHas('inventors', [
      'last_name' => 'Dela Cruz',
      'first_name' => 'Juan',
      'middle_initial' => 'P',
      'email' => 'juan@example.com',
      'full_name' => 'Dela Cruz, Juan P.',
      'contribution_percent' => '100.00',
      'declaration_confirmed' => false,
      'signature_name' => null,
      'declaration_date' => null,
    ]);
    $this->assertDatabaseHas('disclosure_cost_items', [
      'item' => 'Filter material',
      'total_cost' => '1250.00',
    ]);
    $this->assertDatabaseHas('disclosure_technologies', [
      'innovation_disclosure_id' => DB::table('innovation_disclosures')->where('technology_title', 'Low-cost water filtration device')->value('id'),
      'technology_title' => 'Solar-powered water quality monitor',
    ]);
    $this->assertSame(2, DB::table('disclosure_technologies')
      ->where('innovation_disclosure_id', DB::table('innovation_disclosures')->where('technology_title', 'Low-cost water filtration device')->value('id'))
      ->count());
    $this->assertDatabaseHas('disclosure_technology_files', [
      'narrative_key' => 'sample_photo',
      'original_name' => 'sample-picture.pdf',
    ]);
    $this->assertDatabaseHas('disclosure_technology_files', [
      'narrative_key' => 'technical_drawing',
      'original_name' => 'technical-drawing.pdf',
    ]);
    $this->assertDatabaseHas('disclosure_technology_files', [
      'narrative_key' => 'sample_photo',
      'original_name' => 'monitor-picture.pdf',
    ]);
    $this->assertDatabaseHas('disclosure_technology_files', [
      'narrative_key' => 'technical_drawing',
      'original_name' => 'monitor-drawing.pdf',
    ]);

    $disclosureId = DB::table('innovation_disclosures')
      ->where('technology_title', 'Low-cost water filtration device')
      ->value('id');
    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosureId))
      ->assertOk()
      ->assertSee('Low-cost water filtration device')
      ->assertSee('Solar-powered water quality monitor')
      ->assertSee('abstract-summary.pdf')
      ->assertSee('monitor-abstract.pdf')
      ->assertSee('Sample picture')
      ->assertSee('Technical drawing');

    $this->expectException(QueryException::class);
    DB::table('inventors')->insert([
      'innovation_disclosure_id' => $disclosureId,
      'last_name' => 'Santos',
      'first_name' => 'Maria',
      'country_of_citizenship' => 'Philippines',
      'affiliation' => 'student',
      'is_primary' => true,
      'created_at' => now(),
      'updated_at' => now(),
    ]);
  }

  public function test_authenticated_user_can_submit_without_cost_items(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $data['materials'] = [[
      'item' => '',
      'description' => '',
      'quantity' => '',
      'unit_cost' => '',
      'purpose' => '',
    ]];
    $data['attachments'] = [
      'sample_photo' => UploadedFile::fake()->create('sample-photo.pdf', 20, 'application/pdf'),
    ];

    $this->actingAs($user)
      ->post(route('dashboard.innovation-disclosures.store'), $data)
      ->assertRedirect(route('dashboard.submissions.index'));

    $disclosureId = DB::table('innovation_disclosures')
      ->where('user_id', $user->id)
      ->value('id');
    $this->assertDatabaseHas('innovation_disclosures', [
      'id' => $disclosureId,
      'total_development_cost' => '0.00',
    ]);
    $this->assertSame(0, DB::table('disclosure_cost_items')
      ->where('innovation_disclosure_id', $disclosureId)
      ->count());
  }

  public function test_creator_contributions_must_total_one_hundred_percent(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $data['inventors'][0]['contribution_percent'] = '99';
    $data['attachments'] = [
      'sample_photo' => UploadedFile::fake()->create('sample-photo.pdf', 5, 'application/pdf'),
    ];

    $this->actingAs($user)
      ->from(route('dashboard.innovation-disclosure.create'))
      ->post(route('dashboard.innovation-disclosures.store'), $data)
      ->assertSessionHasErrors('inventors');
  }

  public function test_student_assignment_requires_a_deed_of_assignment_pdf(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $data['ownership_declaration'] = 'assigned_to_slsu';
    $data['attachments'] = [
      'sample_photo' => UploadedFile::fake()->create('sample-photo.pdf', 5, 'application/pdf'),
    ];

    $this->actingAs($user)
      ->from(route('dashboard.innovation-disclosure.create'))
      ->post(route('dashboard.innovation-disclosures.store'), $data)
      ->assertSessionHasErrors('attachments.deed_assignment');

    $data['attachments']['deed_assignment'] = UploadedFile::fake()->create('deed.pdf', 5, 'application/pdf');
    $this->actingAs($user)
      ->post(route('dashboard.innovation-disclosures.store'), $data)
      ->assertRedirect(route('dashboard.submissions.index'));
    $this->assertDatabaseHas('disclosure_attachments', [
      'type' => 'deed_assignment',
      'original_name' => 'deed.pdf',
    ]);
  }

  public function test_all_disclosure_uploads_only_accept_pdf_files(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $data['attachments'] = [
      'sample_photo' => UploadedFile::fake()->create('sample-photo.png', 10, 'image/png'),
    ];

    $this->actingAs($user)
      ->from(route('dashboard.innovation-disclosure.create'))
      ->post(route('dashboard.innovation-disclosures.store'), $data)
      ->assertSessionHasErrors('attachments.sample_photo')
      ->assertSessionHas('errors', function ($errors): bool {
        return $errors->first('attachments.sample_photo') === 'All uploaded files must be PDF documents.';
      });
  }

  public function test_each_technology_requires_a_sample_picture_and_technical_drawing_pdf(): void
  {
    foreach (['sample_photo', 'technical_drawing'] as $requiredFile) {
      $user = User::factory()->create([
        'username' => fake()->unique()->userName(),
      ]);
      $data = $this->validDisclosureData();
      unset($data['technologies'][0]['files'][$requiredFile]);

      $this->actingAs($user)
        ->from(route('dashboard.innovation-disclosure.create'))
        ->post(route('dashboard.innovation-disclosures.store'), $data)
        ->assertSessionHasErrors("technologies.0.files.{$requiredFile}");
    }
  }

  public function test_each_technology_narrative_requires_a_pdf_even_when_legacy_text_is_submitted(): void
  {
    foreach (['abstract_summary', 'technical_field', 'background_summary', 'invention_summary', 'detailed_description', 'examples_results'] as $narrative) {
      $user = User::factory()->create([
        'username' => fake()->unique()->userName(),
      ]);
      $data = $this->validDisclosureData();
      unset($data['technologies'][0]['files'][$narrative]);
      $data['technologies'][0][$narrative] = 'Legacy text must not satisfy the PDF requirement.';

      $this->actingAs($user)
        ->from(route('dashboard.innovation-disclosure.create'))
        ->post(route('dashboard.innovation-disclosures.store'), $data)
        ->assertSessionHasErrors("technologies.0.files.{$narrative}");
    }
  }

  public function test_a_technology_narrative_can_be_provided_as_a_pdf(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    foreach (['abstract_summary', 'background_summary', 'detailed_description'] as $narrative) {
      $data['technologies'][0][$narrative] = '';
      $data['technologies'][0]['files'][$narrative] = UploadedFile::fake()->create(
        "{$narrative}.pdf",
        5,
        'application/pdf'
      );
    }
    $data['attachments'] = [
      'sample_photo' => UploadedFile::fake()->create('sample-photo.pdf', 5, 'application/pdf'),
    ];

    $response = $this->actingAs($user)
      ->post(route('dashboard.innovation-disclosures.store'), $data);

    $response->assertRedirect(route('dashboard.submissions.index'));
    $disclosureId = DB::table('innovation_disclosures')
      ->where('technology_title', 'Low-cost water filtration device')
      ->value('id');
    foreach (['abstract_summary', 'background_summary', 'detailed_description'] as $narrative) {
      $this->assertDatabaseHas('disclosure_technology_files', [
        'narrative_key' => $narrative,
        'original_name' => "{$narrative}.pdf",
        'mime_type' => 'application/pdf',
      ]);
      $storedPath = DB::table('disclosure_technology_files')
        ->where('original_name', "{$narrative}.pdf")
        ->value('stored_path');
      $this->assertTrue(Storage::disk('local')->exists($storedPath));
    }
    $fileId = DB::table('disclosure_technology_files')
      ->where('original_name', 'abstract_summary.pdf')
      ->value('id');
    $this->actingAs($user)
      ->get(route('dashboard.submissions.technology-files.preview', [$disclosureId, $fileId]))
      ->assertOk()
      ->assertHeader('content-type', 'application/pdf');

    $otherUser = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $this->actingAs($otherUser)
      ->get(route('dashboard.submissions.technology-files.preview', [$disclosureId, $fileId]))
      ->assertForbidden();
  }

  public function test_technology_narrative_uploads_only_accept_pdf_files(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $data['technologies'][0]['abstract_summary'] = '';
    $data['technologies'][0]['files']['abstract_summary'] = UploadedFile::fake()->create(
      'abstract.png',
      5,
      'image/png'
    );
    $data['attachments'] = [
      'sample_photo' => UploadedFile::fake()->create('sample-photo.pdf', 5, 'application/pdf'),
    ];

    $this->actingAs($user)
      ->from(route('dashboard.innovation-disclosure.create'))
      ->post(route('dashboard.innovation-disclosures.store'), $data)
      ->assertSessionHasErrors('technologies.0.files.abstract_summary')
      ->assertSessionHas('errors', function ($errors): bool {
        return $errors->first('technologies.0.files.abstract_summary') === 'Technology uploads must be PDF documents.';
      });
  }

  public function test_other_relationship_requires_a_specification(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $data['university_relationship'] = 'other_specify';
    $data['attachments'] = [
      'sample_photo' => UploadedFile::fake()->create('sample-photo.pdf', 5, 'application/pdf'),
    ];

    $this->actingAs($user)
      ->from(route('dashboard.innovation-disclosure.create'))
      ->post(route('dashboard.innovation-disclosures.store'), $data)
      ->assertSessionHasErrors('other_relationship');
  }

  public function test_more_than_six_creators_requires_an_additional_signed_sheet(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $data = $this->validDisclosureData();
    $data['inventors'] = [];
    for ($index = 0; $index < 7; $index++) {
      $inventor = $this->validDisclosureData()['inventors'][0];
      $inventor['last_name'] = "Creator {$index}";
      $inventor['first_name'] = 'Tester';
      $inventor['contribution_percent'] = $index === 6 ? '14.32' : '14.28';
      $data['inventors'][] = $inventor;
    }
    $data['attachments'] = [
      'sample_photo' => UploadedFile::fake()->create('sample-photo.pdf', 5, 'application/pdf'),
    ];

    $this->actingAs($user)
      ->from(route('dashboard.innovation-disclosure.create'))
      ->post(route('dashboard.innovation-disclosures.store'), $data)
      ->assertSessionHasErrors('attachments.additional_signed_sheet');
  }

  public function test_authenticated_user_can_stage_and_submit_idf_pdfs_separately(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $pdf = static fn (string $name): UploadedFile => UploadedFile::fake()->createWithContent(
      $name,
      "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF"
    );
    foreach (array_keys($data['technologies'][0]['files']) as $key) {
      $data['technologies'][0]['files'][$key] = $pdf("{$key}.pdf");
    }
    $stagedTokens = ['technologies' => [0 => []], 'attachments' => []];

    foreach ($data['technologies'][0]['files'] as $key => $file) {
      $response = $this->actingAs($user)->postJson(
        route('dashboard.innovation-disclosure.uploads.store'),
        ['file' => $file]
      );
      $response->assertCreated();
      $stagedTokens['technologies'][0][$key] = $response->json('token');
    }
    $data['attachments'] = [
      'sample_photo' => $pdf('sample-photo.pdf'),
    ];
    $response = $this->actingAs($user)->postJson(
      route('dashboard.innovation-disclosure.uploads.store'),
      ['file' => $data['attachments']['sample_photo']]
    );
    $response->assertCreated();
    $stagedTokens['attachments']['sample_photo'] = $response->json('token');

    $data['technologies'][0]['files'] = [];
    $data['attachments'] = [];
    $data['idf_upload_tokens'] = $stagedTokens;

    $this->assertSame(9, IdfStagedUpload::query()->count());
    $this->actingAs($user)
      ->postJson(route('dashboard.innovation-disclosures.store'), $data)
      ->assertRedirect(route('dashboard.submissions.index'));

    $this->assertDatabaseHas('disclosure_technology_files', [
      'original_name' => 'abstract_summary.pdf',
    ]);
    $this->assertDatabaseHas('disclosure_attachments', [
      'original_name' => 'sample-photo.pdf',
    ]);
    $this->assertSame(0, IdfStagedUpload::query()->count());
  }

  public function test_staged_idf_pdfs_cannot_be_used_by_another_user_or_after_expiry(): void
  {
    Storage::fake('local');
    $owner = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $otherUser = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $stageResponse = $this->actingAs($owner)->postJson(
      route('dashboard.innovation-disclosure.uploads.store'),
      ['file' => UploadedFile::fake()->createWithContent(
        'sample-photo.pdf',
        "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF"
      )]
    );
    $stageResponse->assertCreated();
    $token = $stageResponse->json('token');

    $data = $this->validDisclosureData();
    $data['idf_upload_tokens'] = [
      'attachments' => ['sample_photo' => $token],
    ];
    $this->actingAs($otherUser)
      ->from(route('dashboard.innovation-disclosure.create'))
      ->post(route('dashboard.innovation-disclosures.store'), $data)
      ->assertSessionHasErrors('idf_upload_tokens');
    $this->assertSame(1, IdfStagedUpload::query()->count());

    $retryData = $this->validDisclosureData();
    $retryData['inventors'][0]['contribution_percent'] = '99.00';
    $retryData['idf_upload_tokens'] = $data['idf_upload_tokens'];
    $this->actingAs($owner)
      ->from(route('dashboard.innovation-disclosure.create'))
      ->post(route('dashboard.innovation-disclosures.store'), $retryData)
      ->assertSessionHasErrors('inventors');
    $this->get(route('dashboard.innovation-disclosure.create'))
      ->assertOk()
      ->assertSee('name="idf_upload_tokens[attachments][sample_photo]"', false)
      ->assertSee('data-original-name="sample-photo.pdf"', false);

    IdfStagedUpload::query()->update(['expires_at' => now()->subMinute()]);
    $this->actingAs($owner)
      ->from(route('dashboard.innovation-disclosure.create'))
      ->post(route('dashboard.innovation-disclosures.store'), $data)
      ->assertSessionHasErrors('idf_upload_tokens');
  }

  private function validDisclosureData(): array
  {
    return [
      'email' => 'inventor@example.com',
      'mobile_no' => '09171234567',
      'lead_creator_name' => 'Juan P. Dela Cruz',
      'college_department' => 'College of Engineering',
      'campus' => 'Main Campus',
      'technologies' => [[
        'technology_title' => 'Low-cost water filtration device',
        'files' => [
          'abstract_summary' => UploadedFile::fake()->create('abstract-summary.pdf', 5, 'application/pdf'),
          'technical_field' => UploadedFile::fake()->create('technical-field.pdf', 5, 'application/pdf'),
          'background_summary' => UploadedFile::fake()->create('background-summary.pdf', 5, 'application/pdf'),
          'invention_summary' => UploadedFile::fake()->create('invention-summary.pdf', 5, 'application/pdf'),
          'detailed_description' => UploadedFile::fake()->create('detailed-description.pdf', 5, 'application/pdf'),
          'examples_results' => UploadedFile::fake()->create('examples-results.pdf', 5, 'application/pdf'),
          'sample_photo' => UploadedFile::fake()->create('sample-picture.pdf', 5, 'application/pdf'),
          'technical_drawing' => UploadedFile::fake()->create('technical-drawing.pdf', 5, 'application/pdf'),
        ],
      ]],
      'technology_type' => 'mechanical',
      'university_relationship' => 'student',
      'research_title' => 'Community water systems',
      'funding_source' => 'University Research Grant',
      'date_of_creation' => '2026-08-20',
      'research_approval_date' => '2026-07-01',
      'ownership_declaration' => 'creator_retained',
      'inventors' => [[
        'last_name' => 'Dela Cruz',
        'first_name' => 'Juan',
        'middle_initial' => 'P',
        'suffix' => '',
        'email' => 'juan@example.com',
        'position_affiliation' => 'Student researcher',
        'technical_contribution' => 'Designed the filtration arrangement and performed validation testing.',
        'contribution_percent' => '100.00',
        'contribution_nature' => ['conception', 'technical_feature'],
      ]],
      'materials' => [[
        'item' => 'Filter material',
        'description' => 'Replaceable filtration medium',
        'quantity' => '2',
        'unit_cost' => '625.00',
        'purpose' => 'Prototype testing',
      ]],
    ];
  }

  private function makeDisclosure(User $user): \App\Models\InnovationDisclosure
  {
    return $user->innovationDisclosures()->create([
      'email' => 'inventor@example.com',
      'mobile_no' => '09171234567',
      'technology_title' => 'Low-cost water filtration device',
      'technology_type' => 'mechanical',
      'university_relationship' => 'student',
      'funding_source' => 'personal',
      'ownership_declaration' => 'retain_ownership',
      'background_summary' => str_repeat('A compact filtration device for community water systems. ', 3),
      'detailed_description' => str_repeat('The device uses replaceable filtration media in a hand-operated housing. ', 3),
      'status' => 'submitted',
    ]);
  }
}
