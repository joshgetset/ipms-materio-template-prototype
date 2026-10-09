<?php

namespace Tests\Feature;

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
      ->assertSee('<span class="menu-header-text">Inventors</span>', false)
      ->assertDontSee('IP Head</span>')
      ->assertSee('dashboard-wide-container', false)
      ->assertSee('Dashboard analytics')
      ->assertSee('Disclosure status')
      ->assertDontSee('name="technology_type"', false)
      ->assertDontSee('type="radio"', false)
      ->assertDontSee('CRM')
      ->assertDontSee('Layouts');
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
      ->assertSee('Creator Information')
      ->assertSee('Creators and Contributions')
      ->assertSee('id="add-technology"', false)
      ->assertSee('id="technology-modal"', false)
      ->assertSee('id="save-idf-submission"', false)
      ->assertSee('Save submission')
      ->assertSee('id="import-idf-button"', false)
      ->assertSee('Import IDF')
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
      ->assertSee("['view', 'View', 'ri-eye-line']", false)
      ->assertDontSee("viewButton.dataset.technologyAction = 'view'", false)
      ->assertSee('ri-checkbox-circle-fill', false)
      ->assertSee("accordion accordion-flush border rounded mb-3", false)
      ->assertSee("'PDF DOCUMENT'", false)
      ->assertSee('ri-file-pdf-2-line', false)
      ->assertSee('Narrative already exists')
      ->assertSee("confirmButtonText: 'Proceed'", false)
      ->assertSee("cancelButtonText: 'Cancel'", false)
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
      ->assertSee('data-bs-toggle="modal"', false)
      ->assertSee('Nature of contribution')
      ->assertSee('Ownership and University Support Facts')
      ->assertSee('Technical / Creative Description')
      ->assertSee('Costing of Materials and Resources')
      ->assertSee('name="technology_type"', false)
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
    $disclosure->update(['research_title' => 'Community Water Research Program']);
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
      ->assertSee('Technical / creative descriptions')
      ->assertSee('Creators and contributions')
      ->assertSee('Costing of materials and resources')
      ->assertSee('id="submission-review-title">Community Water Research Program</h3>', false)
      ->assertSee('<section class="submission-review-overview submission-review-section mb-4" id="submission-overview"', false)
      ->assertDontSee('<article class="card mb-4 submission-review-section" id="submission-overview"', false)
      ->assertSee('data-review-date="creation"', false)
      ->assertSee('data-review-date="approval"', false)
      ->assertSee('data-review-date="disclosure"', false)
      ->assertDontSee('submission-review-group-stack', false)
      ->assertSee('class="submission-review-stepper"', false)
      ->assertSee('id="submission-timeline-title">Timeline</h5>', false)
      ->assertDontSee('data-timeline-step="created"', false)
      ->assertSee('Detailed timeline')
      ->assertSee('submission-detailed-timeline-' . $disclosure->id, false)
      ->assertSee('class="submission-review-group submission-review-timeline"', false)
      ->assertSee('submission-review-important-dates', false)
      ->assertSee('submission-review-date-stack', false)
      ->assertSee('Research Approved')
      ->assertSee('IDF Submitted')
      ->assertSee('data-timeline-step="review"', false)
      ->assertSee('data-timeline-step="approved"', false)
      ->assertSee('IDF Applied to IPOPHL')
      ->assertSee('data-review-date="creation"', false)
      ->assertSee('class="table table-hover align-middle technology-table mb-0"', false)
      ->assertSee('class="table table-hover align-middle inventors-table mb-0"', false)
      ->assertSee('materials-table submission-review-costing-table', false)
      ->assertSee('data-review-section="technologies"', false)
      ->assertSee('data-review-section="costing"', false)
      ->assertSee('Community water filter')
      ->assertSee('Technology Title')
      ->assertSee('>Action</th>', false)
      ->assertSee('submission-technology-view-' . $disclosure->id . '-' . $technology->id, false)
      ->assertSee('A compact filter for community water systems.')
      ->assertSee('aria-label="View Detailed Description PDF:', false)
      ->assertSee(route('dashboard.submissions.technology-files.preview', [$disclosure, $narrativeFile]), false)
      ->assertSee('submission-creator-view-' . $disclosure->id, false)
      ->assertSee('Juan Dela Cruz')
      ->assertSee('Declared Contribution')
      ->assertSee('Filter media')
      ->assertDontSee('Save technical / creative changes')
      ->assertDontSee('Save creator changes')
      ->assertDontSee('Save costing changes')
      ->assertSee('Supporting files and revisions')
      ->assertSee('File uploaded')
      ->assertSee('Revision requested')
      ->assertSee('Please provide a signed copy.')
      ->assertSee('is-revision', false)
      ->assertSee('data-bs-target="#submission-attachment-preview-' . $attachment->id . '"', false)
      ->assertSee(route('dashboard.submissions.attachments.preview', [$disclosure, $attachment]), false)
      ->assertSee('Files cannot be changed until the IP Head reviews this submission.')
      ->assertSee('data-update-file-button', false)
      ->assertSee('class="btn btn-primary d-none"', false);

    $disclosure->update(['status' => 'returned']);
    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('Revision Requested')
      ->assertSee('class="submission-review-stepper__step is-current" data-timeline-step="review"', false);

    $disclosure->update(['status' => 'revised_submission']);
    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('Revision submitted')
      ->assertSee('class="submission-review-stepper__step is-current" data-timeline-step="review"', false);

    $disclosure->update(['status' => 'endorsed', 'endorsed_at' => now()]);
    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('class="submission-review-stepper__step is-current" data-timeline-step="approved"', false);

    $disclosure->update(['status' => 'filed']);
    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('class="submission-review-stepper__step is-current" data-timeline-step="ipophl-applied"', false);
  }

  public function test_returned_submission_can_update_existing_review_table_rows(): void
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
      'signature_name' => 'Juan P. Dela Cruz',
      'declaration_confirmed' => true,
      'declaration_date' => '2026-10-08',
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
      ->assertSee('Save technical / creative changes')
      ->assertSee('Save creator changes')
      ->assertSee('Save costing changes')
      ->assertSee('data-narrative-title="Abstract"', false)
      ->assertSee('data-narrative-content="Abstract"', false)
      ->assertSee('class="submission-revision-details-form" novalidate', false);

    $this->post(route('dashboard.submissions.revision-details', $disclosure), [
      'section' => 'technologies',
      'technologies' => [
        $technology->id => [
          'technology_title' => 'Improved community filter',
          'abstract_summary' => 'Updated abstract',
          'technical_field' => 'Water treatment',
          'background_summary' => 'Updated background',
          'invention_summary' => 'Updated summary',
          'detailed_description' => 'Updated description',
          'examples_results' => 'Updated results',
        ],
      ],
    ])->assertRedirect(route('dashboard.submissions.show', $disclosure));

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
      'abstract_summary' => 'Updated abstract',
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
            'abstract_summary' => $technology->abstract_summary,
            'technical_field' => $technology->technical_field,
            'background_summary' => $technology->background_summary,
            'invention_summary' => $technology->invention_summary,
            'detailed_description' => $technology->detailed_description,
            'examples_results' => $technology->examples_results,
          ],
          999999 => [
            'technology_title' => 'Unauthorized added technology',
            'abstract_summary' => 'Abstract',
            'technical_field' => 'Field',
            'background_summary' => 'Background',
            'invention_summary' => 'Summary',
            'detailed_description' => 'Description',
            'examples_results' => 'Results',
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
      ->assertSee('name="attachments[methodology]"', false)
      ->assertSee('data-update-file-button', false)
      ->assertSee('Update File');

    $this->assertSame(1, substr_count($response->getContent(), '>Update File'));
    $this->assertSame(1, substr_count($response->getContent(), 'class="attachment-update-form"'));
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
      ->assertSee('user-submission-file-card--revised', false)
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

  public function test_user_can_soft_delete_disclosure_while_retaining_its_row_and_file(): void
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
      ->delete(route('dashboard.submissions.destroy', $disclosure))
      ->assertRedirect(route('dashboard.submissions.index'))
      ->assertSessionHas('toast_type', 'success');

    $this->assertDatabaseHas('innovation_disclosures', [
      'id' => $disclosure->id,
      'technology_title' => 'Low-cost water filtration device',
    ]);
    $this->assertNotNull(DB::table('innovation_disclosures')->where('id', $disclosure->id)->value('deleted_at'));
    $this->assertTrue(Storage::disk('local')->exists($storedPath));
    $this->assertDatabaseHas('disclosure_attachments', [
      'innovation_disclosure_id' => $disclosure->id,
      'stored_path' => $storedPath,
    ]);

    $this->actingAs($user)
      ->get(route('dashboard.submissions.index'))
      ->assertOk()
      ->assertSee('Deleted submissions')
      ->assertSee('Low-cost water filtration device')
      ->assertSee('Deleted');

    $otherUser = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $this->actingAs($otherUser)
      ->delete(route('dashboard.submissions.destroy', $disclosure))
      ->assertNotFound();
  }

  public function test_authenticated_user_can_submit_an_innovation_disclosure(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $data['technologies'][] = [
      'technology_title' => 'Solar-powered water quality monitor',
      'abstract_summary' => 'A solar-powered monitoring device for community water systems.',
      'technical_field' => 'Environmental monitoring and renewable energy.',
      'background_summary' => 'Remote communities need affordable and continuous water quality monitoring.',
      'invention_summary' => 'The monitor combines sensors with a solar charging system.',
      'detailed_description' => 'The system samples water readings and records them in a controller powered by a solar panel.',
      'examples_results' => 'Prototype testing confirmed stable operation under field conditions.',
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
      'declaration_confirmed' => true,
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

    $disclosureId = DB::table('innovation_disclosures')
      ->where('technology_title', 'Low-cost water filtration device')
      ->value('id');

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
        return $errors->first('technologies.0.files.abstract_summary') === 'Narrative uploads must be PDF documents.';
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
        'abstract_summary' => 'An abstract describing a community water filtration device and its principal features.',
        'technical_field' => 'Water treatment and community-scale filtration technology.',
        'background_summary' => 'The current situation has limited access to clean water and existing systems are costly to maintain.',
        'invention_summary' => 'The invention provides a low-cost filtration device with replaceable media and simple operation.',
        'detailed_description' => 'The device uses replaceable filtration media in a hand-operated housing and includes defined flow and sealing components.',
        'examples_results' => 'Validation included controlled filtration tests, repeated measurements, and optimization of the media configuration.',
      ]],
      'technology_type' => 'mechanical_engineering',
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
        'signature_name' => 'Juan P. Dela Cruz',
        'declaration_confirmed' => '1',
        'declaration_date' => '2026-10-08',
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
