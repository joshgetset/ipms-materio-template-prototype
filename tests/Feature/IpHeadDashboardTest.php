<?php

namespace Tests\Feature;

use App\Models\InnovationDisclosure;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IpHeadDashboardTest extends TestCase
{
  use DatabaseTransactions;

  private const TEST_IP_HEAD_PASSWORD = 'TestIpHeadPass!';

  public function test_only_ip_heads_can_access_the_review_dashboard_and_update_reviews(): void
  {
    Storage::fake('local');
    $submitter = $this->makeUser('submitter');
    $ipHead = $this->makeUser('ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);
    $disclosure->update(['lead_creator_name' => 'Conflicting legacy lead']);
    $disclosure->inventors()->create([
      'first_name' => 'Jane',
      'last_name' => 'Creator',
      'email' => 'jane.creator@example.com',
      'country_of_citizenship' => 'Philippines',
      'affiliation' => 'student',
      'technical_contribution' => 'Prototype development',
      'contribution_percent' => '100.00',
      'is_primary' => true,
      'declaration_confirmed' => true,
    ]);
    $technology = $disclosure->technologies()->create([
      'technology_title' => 'Water filtration patent',
      'background_summary' => 'Background summary',
    ]);
    $narrativePdf = UploadedFile::fake()->create('water-filter-abstract.pdf', 5, 'application/pdf');
    $storedPath = $narrativePdf->store("innovation-disclosures/{$disclosure->id}/technologies/{$technology->id}/abstract_summary", 'local');
    $technologyFile = $technology->files()->create([
      'narrative_key' => 'abstract_summary',
      'original_name' => 'water-filter-abstract.pdf',
      'stored_path' => $storedPath,
      'mime_type' => 'application/pdf',
      'size_bytes' => 5120,
    ]);
    $reviewedDisclosure = $this->makeDisclosure($submitter);
    $reviewedDisclosure->update(['status' => 'endorsed']);

    $this->actingAs($submitter)
      ->get(route('ip-head-dashboard.index'))
      ->assertForbidden();

    $this->actingAs($submitter)
      ->get(route('ip-head-dashboard.submissions'))
      ->assertForbidden();

    $this->actingAs($submitter)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertForbidden();

    $this->actingAs($submitter)
      ->post(route('ip-head-dashboard.review', $disclosure), [
        'status' => 'endorsed',
      ])
      ->assertForbidden();

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.index'))
      ->assertOk()
      ->assertSee('IP Head Dashboard')
      ->assertSee('ipms-dashboard-footer', false)
      ->assertSee('Intellectual Property Management System')
      ->assertDontSee('made with ❤️ by')
      ->assertDontSee('Admin Templates')
      ->assertSee('href="' . route('ip-head-dashboard.index') . '"', false)
      ->assertDontSee('data-submission-notifications', false)
      ->assertSee('<span class="menu-header-text">IP Head</span>', false)
      ->assertDontSee('Inventors')
      ->assertDontSee('<h4 class="mb-3">IP Head Dashboard</h4>', false)
      ->assertDontSee('page-content__divider', false)
      ->assertSee('bg-label-primary text-primary', false)
      ->assertSee('Innovation Submission')
      ->assertSee('For Review')
      ->assertSee('Revised Submission')
      ->assertDontSee('href="' . url('/dashboard') . '"', false)
      ->assertDontSee('>Analytics</div>', false)
      ->assertDontSee($disclosure->technology_title)
      ->assertDontSee('Reviewed Submissions');

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.submissions'))
      ->assertOk()
      ->assertSee('<hr class="page-content__divider">', false)
      ->assertSeeInOrder(['Home', 'Innovation Submission', 'For Review', $disclosure->technology_title, 'Reviewed Submissions', $reviewedDisclosure->technology_title])
      ->assertSeeInOrder(['Action', 'Type', 'Title', 'Submitter', 'Current Status', 'Last Updated', 'Created At'])
      ->assertSee('Low-cost water filtration device')
      ->assertSee('submitter@example.com')
      ->assertSee('ip_head/disclosures/' . $disclosure->id . '?queue=for-review&amp;page=1', false)
      ->assertDontSee('Submitted files')
      ->assertDontSee('Review files for', false)
      ->assertDontSee('ip-head-review-' . $disclosure->id);

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', ['disclosure' => $disclosure, 'queue' => 'for-review', 'page' => 1]))
      ->assertOk()
      ->assertSeeInOrder(['Home', 'Innovation Submission', 'Review Submission'])
      ->assertSee('<li class="menu-item active">', false)
      ->assertSee('data-review-section="details"', false)
      ->assertSee('data-review-section="inventors"', false)
      ->assertSee('data-review-section="technologies"', false)
      ->assertSee('data-review-section="costing"', false)
      ->assertDontSee('data-review-section="files"', false)
      ->assertSee('data-review-section="approval"', false)
      ->assertSee('Creators and contributions')
      ->assertDontSee('Supporting files')
      ->assertSeeInOrder(['Creators and contributions', 'Costing of materials and resources', 'Technologies in this submission'])
      ->assertSee('data-review-table data-page-size="4" data-disable-inactive', false)
      ->assertSee('technology-review-card__title')
      ->assertSee('data-open-technology-files=', false)
      ->assertSee('data-close-technology-files=', false)
      ->assertSee('Back to technologies')
      ->assertSee('Description Documents')
      ->assertDontSee('<thead><tr><th scope="col">Files</th>', false)
      ->assertSee('Submission approval')
      ->assertDontSee('submission-review-nav', false)
      ->assertSee('Back to submissions')
      ->assertSee('Submission overview')
      ->assertSee('Low-cost water filtration device')
      ->assertSee('water-filter-abstract.pdf')
      ->assertSee('data-ip-head-technology-review', false)
      ->assertSee('ip-head-technology-review-actions__trigger')
      ->assertSee('ri-more-2-fill')
      ->assertSee('data-file-preview-url=', false)
      ->assertSee('/technology-files/', false)
      ->assertSee('data-file-version="1"', false)
      ->assertSee('data-file-category="Abstract / Summary"', false)
      ->assertDontSee('ip-head-file-version-flag')
      ->assertSee('Review file')
      ->assertDontSee('target="_blank" rel="noopener noreferrer"', false)
      ->assertSee('Review Needed')
      ->assertSee('name="technology_files[' . $technologyFile->id . '][status]"', false)
      ->assertDontSee('Background summary')
      ->assertSee('Research title')
      ->assertSee('Submission overview')
      ->assertDontSee('>Overview</h4>', false)
      ->assertDontSee('Submission details at a glance')
      ->assertSee('ip-head-review-stats')
      ->assertSee("Est. dev't cost", false)
      ->assertSee('Disclosure and contact')
      ->assertSee('Research and ownership')
      ->assertSee('submission-review-groups')
      ->assertSee('Technologies in this submission')
      ->assertSee('Costing')
      ->assertSee('Documents')
      ->assertSee('Important dates')
      ->assertSee('creator-allocation')
      ->assertSee('ip-head-creator-row')
      ->assertSee('Jane Creator')
      ->assertDontSee('Conflicting legacy lead')
      ->assertSee('Prototype development')
      ->assertSee('Total: ₱')
      ->assertDontSee('Contact email')
      ->assertDontSee('Mobile number')
      ->assertDontSee('disabled readonly', false)
      ->assertSee('SLSU support')
      ->assertSee('Ownership consent')
      ->assertSee('ip-head-approval-form')
      ->assertDontSee('data-save-as-xml', false);
  }

  public function test_ip_head_technology_cards_are_paginated_in_groups_of_four(): void
  {
    $submitter = $this->makeUser('technology-card-owner');
    $ipHead = $this->makeUser('technology-card-ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);

    foreach (range(1, 5) as $number) {
      $disclosure->technologies()->create([
        'technology_title' => "Technology {$number}",
      ]);
    }

    $response = $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('data-page-size="4"', false)
      ->assertSee('Technology card pagination');

    $this->assertSame(5, substr_count($response->getContent(), 'data-technology-card'));
  }

  public function test_ip_head_can_approve_a_submission_after_every_file_is_approved(): void
  {
    $submitter = $this->makeUser('submitter');
    $ipHead = $this->makeUser('ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);
    $technology = $disclosure->technologies()->create(['technology_title' => 'Approved technology']);
    $technology->files()->create([
      'narrative_key' => 'abstract_summary',
      'original_name' => 'abstract.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/abstract.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'review_status' => 'accepted',
    ]);
    $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/methodology.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'review_status' => 'review_needed',
      'is_latest_submission' => true,
    ]);
    $duplicateAttachment = $disclosure->attachments()->create([
      'type' => 'sample_photo',
      'original_name' => 'abstract.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/sample-photo/abstract.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'review_status' => 'review_needed',
      'is_latest_submission' => true,
    ]);

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('abstract.pdf')
      ->assertDontSee('name="attachments[' . $duplicateAttachment->id . '][status]"', false)
      ->assertSee('name="attachments[' . $disclosure->attachments()->where('original_name', 'methodology.pdf')->value('id') . '][status]"', false)
      ->assertSee('<button class="btn btn-primary" type="submit" disabled', false);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.disclosures.attachments.review', $disclosure), [
        'attachments' => [
          $disclosure->attachments()->first()->id => ['status' => 'accepted'],
        ],
      ])
      ->assertOk()
      ->assertJsonPath('reviewed_count', 1);

    $this->assertDatabaseHas('disclosure_attachments', [
      'innovation_disclosure_id' => $disclosure->id,
      'review_status' => 'accepted',
      'is_latest_submission' => true,
    ]);
    $this->assertDatabaseHas('disclosure_attachments', [
      'id' => $duplicateAttachment->id,
      'review_status' => 'review_needed',
      'is_latest_submission' => true,
    ]);

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('<button class="btn btn-primary" type="submit" ', false)
      ->assertDontSee('<button class="btn btn-primary" type="submit" disabled', false);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.review', $disclosure), [
        'status' => 'endorsed',
      ])
      ->assertOk()
      ->assertJsonPath('message', 'The submitted IDF has been approved.')
      ->assertJsonPath('disclosure.status', 'endorsed');

    $this->assertDatabaseHas('innovation_disclosures', [
      'id' => $disclosure->id,
      'status' => 'endorsed',
      'review_notes' => null,
    ]);
    $this->assertNotNull($disclosure->refresh()->endorsed_at);
  }

  public function test_submission_with_unapproved_files_cannot_be_approved(): void
  {
    $submitter = $this->makeUser('pending-revision-owner');
    $ipHead = $this->makeUser('pending-revision-ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);
    $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => 'innovation-disclosures/1/methodology/methodology.pdf',
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'needs_revision' => true,
      'revision_notes' => 'Please update this file.',
    ]);

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('Approve submitted IDF')
      ->assertSee('<button class="btn btn-primary" type="submit" disabled', false);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.review', $disclosure), [
        'status' => 'endorsed',
      ])
      ->assertUnprocessable()
      ->assertJsonValidationErrors([
        'status' => 'Approve every current submitted file before approving this submission.',
      ]);

    $this->assertDatabaseHas('innovation_disclosures', [
      'id' => $disclosure->id,
      'status' => 'submitted',
      'endorsed_at' => null,
    ]);
  }

  public function test_ip_head_can_download_an_endorsed_submission_as_xml(): void
  {
    $submitter = $this->makeUser('xml-owner');
    $ipHead = $this->makeUser('xml-ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);
    $disclosure->update([
      'technology_title' => 'Water/Filter',
      'detailed_description' => 'A & <compact> filtration device.',
      'status' => 'endorsed',
      'endorsed_at' => now()->subDay()->startOfDay(),
    ]);
    $disclosure->inventors()->create([
      'last_name' => 'O/Neil',
      'first_name' => 'Primary',
      'country_of_citizenship' => 'Philippines',
      'affiliation' => 'student',
      'is_primary' => true,
    ]);
    $disclosure->inventors()->create([
      'last_name' => 'Second',
      'first_name' => 'Co-inventor',
      'country_of_citizenship' => 'Philippines',
      'affiliation' => 'student',
      'is_primary' => false,
    ]);
    $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => 'innovation-disclosures/1/methodology/methodology.pdf',
      'mime_type' => 'application/pdf',
      'size_bytes' => 2048,
      'is_latest_submission' => true,
      'is_latest_submission' => true,
    ]);
    $disclosure->attachments()->create([
      'type' => 'drawings',
      'original_name' => 'drawing.png',
      'stored_path' => 'innovation-disclosures/1/drawings/drawing.png',
      'mime_type' => 'image/png',
      'size_bytes' => 1024,
    ]);

    $route = route('ip-head-dashboard.disclosures.xml', $disclosure);

    $this->actingAs($submitter)->get($route)->assertForbidden();

    $response = $this->actingAs($ipHead)->get($route);
    $response->assertOk()
      ->assertDownload('Water-Filter_O-Neil_' . $disclosure->endorsed_at->format('Y-m-d') . '.xml');

    $document = new \DOMDocument();
    $this->assertTrue($document->loadXML($response->streamedContent()));
    $xpath = new \DOMXPath($document);
    $this->assertSame(
      'Water/Filter',
      $xpath->query('/eFiling/Title')->item(0)->textContent
    );
    $this->assertSame(
      'Mechanical',
      $xpath->query('/eFiling/Category')->item(0)->textContent
    );
    $this->assertSame(
      '',
      $xpath->query('/eFiling/RefNo')->item(0)->textContent
    );
    $this->assertSame(
      'xml-owner@example.com',
      $xpath->query('/eFiling/Applicant/eMail')->item(0)->textContent
    );
    $this->assertSame(
      'A & <compact> filtration device.',
      $xpath->query('/eFiling/IPMSData/disclosure/detailed_description')->item(0)->textContent
    );
    $this->assertSame(
      'O/Neil, Primary',
      $xpath->query('/eFiling/Maker/Name')->item(0)->textContent
    );
    $this->assertSame(0, $xpath->query('/eFiling/Document')->length);
    $this->assertSame('', $xpath->query('/eFiling/Applicant/Address/Street')->item(0)->textContent);
    $this->assertSame(
      'methodology.pdf',
      $xpath->query('/eFiling/IPMSData/attachments/attachment[type="methodology"]/original_name')->item(0)->textContent
    );

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('data-save-as-xml', false)
      ->assertSee('Save as XML');

    $disclosure->update(['status' => 'under_review']);
    $this->actingAs($ipHead)->get($route)->assertStatus(409);
  }

  public function test_ip_head_approval_request_returns_json(): void
  {
    $submitter = $this->makeUser('ajax-submit-owner');
    $ipHead = $this->makeUser('ajax-ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);
    $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/methodology.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'review_status' => 'accepted',
      'is_latest_submission' => true,
    ]);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.review', $disclosure), [
        'status' => 'endorsed',
        'queue' => 'for-review',
        'page' => 1,
      ])
      ->assertOk()
      ->assertJsonPath('message', 'The submitted IDF has been approved.')
      ->assertJsonPath('disclosure.id', $disclosure->id)
      ->assertJsonPath('disclosure.status', 'endorsed')
      ->assertJsonPath('disclosure.review_notes', null);
  }

  public function test_ip_head_can_request_revision_for_a_specific_file_and_submitter_can_see_it(): void
  {
    $submitter = $this->makeUser('file-owner');
    $ipHead = $this->makeUser('file-reviewer', true);
    $disclosure = $this->makeDisclosure($submitter);
    $attachment = $disclosure->attachments()->create([
      'type' => 'drawings',
      'original_name' => 'drawing.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/drawings/drawing.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'version' => 2,
      'submitted_at' => now()->subDays(5),
      'is_latest_submission' => true,
    ]);
    $imageAttachment = $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'detail.jpg',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/methodology/detail.jpg",
      'mime_type' => 'image/jpeg',
      'size_bytes' => 512,
    ]);

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.submissions'))
      ->assertOk()
      ->assertSee('Type')
      ->assertSee('Current Status')
      ->assertDontSee('Review files for', false)
      ->assertDontSee('Submitted files');

    $reviewPage = $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure));

    $reviewPage
      ->assertOk()
      ->assertSee('drawing.pdf')
      ->assertSee('detail.jpg')
      ->assertSee('Preview')
      ->assertSee('class="dropdown review-files-actions"', false)
      ->assertSee('ri-more-2-fill', false)
      ->assertSee('class="btn btn-primary ip-head-file-card__save" type="submit" disabled', false)
      ->assertSee(
        'action="' . route('ip-head-dashboard.disclosures.attachments.review', $disclosure) . '"',
        false
      )
      ->assertSee('Review Needed')
      ->assertSee('ip-head-file-card--revised', false)
      ->assertSee('data-bs-target="#attachment-preview-fallback-' . $attachment->id . '"', false)
      ->assertSee('data-bs-target="#attachment-preview-fallback-' . $imageAttachment->id . '"', false)
      ->assertSee(
        'src="' . route('ip-head-dashboard.attachments.preview', [$disclosure, $attachment]) . '"',
        false
      )
      ->assertSee(
        'src="' . route('ip-head-dashboard.attachments.preview', [$disclosure, $imageAttachment]) . '"',
        false
      )
      ->assertDontSee(route('dashboard.submissions.attachments.download', [$disclosure, $attachment]));
    $this->assertSame(1, substr_count($reviewPage->getContent(), '>Save file review'));

    $this->actingAs($ipHead)
      ->post(route('ip-head-dashboard.attachments.review', $attachment), [
        'needs_revision' => '0',
      ])
      ->assertSessionHasErrors('needs_revision');

    $this->actingAs($ipHead)
      ->post(route('ip-head-dashboard.attachments.review', $attachment), [
        'needs_revision' => '1',
      ])
      ->assertSessionHasErrors('revision_notes');

    $this->actingAs($ipHead)
      ->post(route('ip-head-dashboard.attachments.review', $attachment), [
        'needs_revision' => '1',
        'revision_notes' => 'Upload a higher-resolution drawing.',
      ])
      ->assertRedirect(route('ip-head-dashboard.disclosures.show', [
        'disclosure' => $disclosure,
        'queue' => 'for-review',
        'page' => 1,
      ]))
      ->assertSessionHas('file_review_saved', true);

    $attachment->refresh();
    $this->assertTrue($attachment->needs_revision);
    $this->assertSame('Upload a higher-resolution drawing.', $attachment->revision_notes);
    $this->assertFalse($attachment->is_latest_submission);
    $this->assertNotSame($attachment->submitted_at->format('Y-m-d'), $attachment->updated_at->format('Y-m-d'));
    $this->assertDatabaseHas('disclosure_attachment_events', [
      'disclosure_attachment_id' => $attachment->id,
      'event_type' => 'revision_requested',
      'version' => $attachment->version,
      'original_name' => $attachment->original_name,
      'revision_notes' => 'Upload a higher-resolution drawing.',
    ]);
    $this->assertDatabaseHas('disclosure_attachment_events', [
      'disclosure_attachment_id' => $attachment->id,
      'event_type' => 'revision_requested',
      'revision_notes' => 'Upload a higher-resolution drawing.',
    ]);

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('data-file-review-saved="true"', false)
      ->assertSee($attachment->submitted_at->format('M j, Y'));

    $this->actingAs($ipHead)
      ->post(route('ip-head-dashboard.attachments.review', $attachment), [
        'needs_revision' => '1',
        'revision_notes' => 'Duplicate revision request.',
      ])
      ->assertStatus(409);

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('Rejected')
      ->assertSee('disabled', false)
      ->assertSee('Upload a higher-resolution drawing.')
      ->assertSee($attachment->submitted_at->format('M j, Y'))
      ->assertSee('Rejected');

    $this->actingAs($submitter)
      ->get(route('dashboard.submissions.status-updates'))
      ->assertOk()
      ->assertJsonPath('0.attachments.0.needs_revision', true)
      ->assertJsonPath('0.attachments.0.revision_notes', 'Upload a higher-resolution drawing.');
  }

  public function test_ip_head_file_review_returns_json_for_in_place_saves(): void
  {
    $submitter = $this->makeUser('json-file-owner');
    $ipHead = $this->makeUser('json-file-reviewer', true);
    $disclosure = $this->makeDisclosure($submitter);
    $attachment = $disclosure->attachments()->create([
      'type' => 'drawings',
      'original_name' => 'drawing.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/drawings/drawing.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
    ]);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.attachments.review', $attachment), [
        'needs_revision' => '1',
        'revision_notes' => 'Upload a higher-resolution drawing.',
        'queue' => 'for-review',
        'page' => 1,
      ])
      ->assertOk()
      ->assertJsonPath('message', 'File review saved.')
      ->assertJsonPath('attachment.id', $attachment->id)
      ->assertJsonPath('attachment.needs_revision', true)
      ->assertJsonPath('attachment.is_latest_submission', false);
  }

  public function test_ip_head_can_save_revision_requests_for_multiple_files_with_one_action(): void
  {
    $submitter = $this->makeUser('multi-file-owner');
    $ipHead = $this->makeUser('multi-file-ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);
    $drawing = $disclosure->attachments()->create([
      'type' => 'drawings',
      'original_name' => 'drawing.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/drawings/drawing.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'version' => 2,
      'is_latest_submission' => true,
    ]);
    $methodology = $disclosure->attachments()->create([
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/methodology/methodology.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 2048,
      'is_latest_submission' => true,
    ]);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.disclosures.attachments.review', $disclosure), [
        'attachments' => [
          $drawing->id => [
            'status' => 'revision_requested',
            'revision_notes' => 'Clarify the labels.',
          ],
          $methodology->id => [
            'status' => 'revision_requested',
            'revision_notes' => 'Add a materials list.',
          ],
        ],
      ])
      ->assertOk()
      ->assertJsonPath('message', 'File reviews saved.')
      ->assertJsonPath('reviewed_count', 2);

    $drawing->refresh();
    $methodology->refresh();
    $this->assertTrue($drawing->needs_revision);
    $this->assertSame('Clarify the labels.', $drawing->revision_notes);
    $this->assertFalse($drawing->is_latest_submission);
    $this->assertTrue($methodology->needs_revision);
    $this->assertSame('Add a materials list.', $methodology->revision_notes);
  }

  public function test_ip_head_can_set_technology_file_review_statuses(): void
  {
    $submitter = $this->makeUser('technology-file-owner');
    $ipHead = $this->makeUser('technology-file-reviewer', true);
    $disclosure = $this->makeDisclosure($submitter);
    $technology = $disclosure->technologies()->create([
      'technology_title' => 'Community water filter',
    ]);
    $file = $technology->files()->create([
      'narrative_key' => 'abstract_summary',
      'original_name' => 'abstract.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/technologies/{$technology->id}/abstract.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'version' => 2,
    ]);
    $file->versions()->create([
      'version' => 1,
      'original_name' => 'abstract-v1.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/technologies/{$technology->id}/abstract-v1.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 900,
      'review_status' => 'revision_requested',
      'revision_notes' => 'Please include the missing performance results.',
    ]);

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('1 updated file')
      ->assertSee('data-file-version="2"', false)
      ->assertSee('data-file-initial-revision-note="Please include the missing performance results."', false)
      ->assertSee('Revised')
      ->assertSee('ip-head-file-version-flag--revised')
      ->assertDontSee('ip-head-file-version-flag--latest')
      ->assertSee('data-file-preview-url="' . route('ip-head-dashboard.technology-files.preview', [
        $disclosure,
        $file,
        'v' => $file->version,
      ]) . '"', false)
      ->assertSee('Review Needed')
      ->assertSee('Requested Revision')
      ->assertSee('Approved')
      ->assertSee('name="technology_files[' . $file->id . '][status]"', false);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.disclosures.attachments.review', $disclosure), [
        'technology_files' => [
          $file->id => ['status' => 'revision_requested'],
        ],
      ])
      ->assertUnprocessable();

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.disclosures.attachments.review', $disclosure), [
        'technology_files' => [
          $file->id => [
            'status' => 'revision_requested',
            'revision_notes' => 'Please replace this PDF with a legible copy.',
          ],
        ],
      ])
      ->assertOk()
      ->assertJsonPath('reviewed_count', 1);

    $this->assertDatabaseHas('disclosure_technology_files', [
      'id' => $file->id,
      'review_status' => 'revision_requested',
      'revision_notes' => 'Please replace this PDF with a legible copy.',
    ]);

    $this->actingAs($submitter)
      ->get(route('dashboard.submissions.status-updates'))
      ->assertOk()
      ->assertJsonPath('0.technology_title', $disclosure->technology_title)
      ->assertJsonPath('0.technologies.0.technology_title', 'Community water filter')
      ->assertJsonPath('0.technologies.0.files.0.original_name', 'abstract.pdf')
      ->assertJsonPath('0.technologies.0.files.0.review_status', 'revision_requested')
      ->assertJsonPath('0.technologies.0.files.0.updated_at', $file->fresh()->updated_at->toJSON());

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('ip-head-file-revision-note--inline')
      ->assertSee('Please replace this PDF with a legible copy.')
      ->assertSee('name="technology_files[' . $file->id . '][revision_notes]"', false)
      ->assertSee('id="technology-file-notes-' . $file->id . '"', false)
      ->assertSee('class="ip-head-technology-file-notes d-none"', false)
      ->assertSee('data-file-revision-note="Please replace this PDF with a legible copy."', false)
      ->assertDontSee('updated file')
      ->assertSee('data-file-category="Abstract / Summary"', false);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.review', $disclosure), [
        'status' => 'endorsed',
      ])
      ->assertUnprocessable()
      ->assertJsonValidationErrors('status');

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.disclosures.attachments.review', $disclosure), [
        'technology_files' => [
          $file->id => ['status' => 'accepted'],
        ],
      ])
      ->assertOk();

    $this->assertDatabaseHas('disclosure_technology_files', [
      'id' => $file->id,
      'review_status' => 'accepted',
      'revision_notes' => null,
    ]);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.review', $disclosure), [
        'status' => 'endorsed',
      ])
      ->assertOk()
      ->assertJsonPath('disclosure.status', 'endorsed');

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.disclosures.attachments.review', $disclosure), [
        'technology_files' => [
          $file->id => ['status' => 'review_needed'],
        ],
      ])
      ->assertStatus(409);
  }

  public function test_ip_head_can_preview_pdf_inline_and_non_ip_heads_are_forbidden(): void
  {
    Storage::fake('local');
    $submitter = $this->makeUser('preview-owner');
    $ipHead = $this->makeUser('preview-ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);
    $storedPath = "innovation-disclosures/{$disclosure->id}/drawings/drawing.pdf";
    Storage::disk('local')->put($storedPath, '%PDF-1.4 preview fixture');
    $attachment = $disclosure->attachments()->create([
      'type' => 'drawings',
      'original_name' => 'drawing.pdf',
      'stored_path' => $storedPath,
      'mime_type' => 'application/pdf',
      'size_bytes' => 24,
    ]);
    $route = route('ip-head-dashboard.attachments.preview', [$disclosure, $attachment]);

    $this->actingAs($submitter)
      ->get($route)
      ->assertForbidden();

    $response = $this->actingAs($ipHead)->get($route);
    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $this->assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
  }

  public function test_approved_file_statuses_are_locked_for_ip_head(): void
  {
    $submitter = $this->makeUser('approved-file-owner');
    $ipHead = $this->makeUser('approved-file-reviewer', true);
    $disclosure = $this->makeDisclosure($submitter);
    $attachment = $disclosure->attachments()->create([
      'type' => 'drawings',
      'original_name' => 'approved-drawing.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/drawings/approved-drawing.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'review_status' => 'accepted',
      'is_latest_submission' => true,
    ]);
    $technology = $disclosure->technologies()->create([
      'technology_title' => 'Approved water filter',
    ]);
    $technologyFile = $technology->files()->create([
      'narrative_key' => 'abstract_summary',
      'original_name' => 'approved-abstract.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/technologies/{$technology->id}/approved-abstract.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'review_status' => 'accepted',
    ]);

    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('Approved')
      ->assertSee('name="technology_files[' . $technologyFile->id . '][status]"', false)
      ->assertSee('disabled', false)
      ->assertDontSee('name="attachments[' . $attachment->id . '][status]"', false);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.disclosures.attachments.review', $disclosure), [
        'attachments' => [
          $attachment->id => ['status' => 'review_needed'],
        ],
      ])
      ->assertStatus(409);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.disclosures.attachments.review', $disclosure), [
        'technology_files' => [
          $technologyFile->id => ['status' => 'review_needed'],
        ],
      ])
      ->assertStatus(409);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.attachments.review', $attachment), [
        'needs_revision' => '1',
        'revision_notes' => 'Please update the drawing.',
      ])
      ->assertStatus(409);

    $this->assertDatabaseHas('disclosure_attachments', [
      'id' => $attachment->id,
      'review_status' => 'accepted',
      'needs_revision' => false,
    ]);
    $this->assertDatabaseHas('disclosure_technology_files', [
      'id' => $technologyFile->id,
      'review_status' => 'accepted',
    ]);
  }

  public function test_ip_head_can_attach_a_noted_pdf_that_is_visible_to_the_inventor(): void
  {
    Storage::fake('local');
    $submitter = $this->makeUser('noted-pdf-owner');
    $ipHead = $this->makeUser('noted-pdf-ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);
    $technology = $disclosure->technologies()->create([
      'technology_title' => 'Water filtration patent',
    ]);
    $file = $technology->files()->create([
      'narrative_key' => 'abstract_summary',
      'original_name' => 'abstract.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/technologies/{$technology->id}/abstract.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'review_status' => 'review_needed',
    ]);
    Storage::disk('local')->put($file->stored_path, '%PDF-1.4 submitted document');
    $notedPdf = UploadedFile::fake()->create('annotated-abstract.pdf', 5, 'application/pdf');

    $this->actingAs($ipHead)
      ->post(route('ip-head-dashboard.disclosures.attachments.review', $disclosure), [
        'technology_files' => [
          $file->id => [
            'status' => 'revision_requested',
            'revision_notes' => 'Please clarify the testing results.',
            'revision_guidance_pdf' => $notedPdf,
          ],
        ],
      ], ['Accept' => 'application/json'])
      ->assertOk()
      ->assertJsonPath('reviewed_count', 1);

    $savedFile = $file->fresh();
    $this->assertSame('Please clarify the testing results.', $savedFile->revision_notes);
    $this->assertSame('annotated-abstract.pdf', $savedFile->revision_guidance_name);
    Storage::disk('local')->assertExists($savedFile->revision_guidance_path);

    $ipHeadGuidancePreviewUrl = route('ip-head-dashboard.technology-files.preview', [
      $disclosure,
      $file,
      'guidance' => 1,
    ]);
    $inventorGuidancePreviewUrl = route('dashboard.submissions.technology-files.preview', [
      $disclosure,
      $file,
      'guidance' => 1,
    ]);
    $this->actingAs($ipHead)
      ->get(route('ip-head-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('data-file-guidance-url="' . $ipHeadGuidancePreviewUrl . '"', false)
      ->assertSee('data-file-guidance-name="annotated-abstract.pdf"', false);

    $this->actingAs($submitter)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('data-file-guidance-url="' . $inventorGuidancePreviewUrl . '"', false)
      ->assertSee('data-file-guidance-name="annotated-abstract.pdf"', false)
      ->assertSee('Please clarify the testing results.');

    $this->actingAs($submitter)
      ->get($inventorGuidancePreviewUrl)
      ->assertOk()
      ->assertHeader('Content-Type', 'application/pdf');
  }

  public function test_ip_head_login_redirects_to_ip_head(): void
  {
    $ipHead = $this->makeUser('ipheaddemo', true);

    $this->post(route('login.submit'), [
      'username' => $ipHead->username,
      'password' => self::TEST_IP_HEAD_PASSWORD,
    ])
      ->assertRedirect(route('ip-head-dashboard.index'));
  }

  private function makeUser(string $username, bool $isIpHead = false): User
  {
    return User::factory()->create([
      'name' => ucfirst($username),
      'username' => $username . '-' . fake()->unique()->numerify('####'),
      'email' => $username . '@example.com',
      'password' => Hash::make(self::TEST_IP_HEAD_PASSWORD),
      'is_ip_head' => $isIpHead,
    ]);
  }

  private function makeDisclosure(User $user): InnovationDisclosure
  {
    return $user->innovationDisclosures()->create([
      'email' => $user->email,
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
