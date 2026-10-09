<?php

namespace Tests\Feature;

use App\Models\InnovationDisclosure;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IpHeadDashboardTest extends TestCase
{
  use DatabaseTransactions;

  private const TEST_IP_HEAD_PASSWORD = 'TestIpHeadPass!';

  public function test_only_ip_heads_can_access_the_review_dashboard_and_update_reviews(): void
  {
    $submitter = $this->makeUser('submitter');
    $ipHead = $this->makeUser('ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);
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
      ->assertSee('href="' . route('ip-head-dashboard.index') . '"', false)
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
      ->assertSee('data-review-section="files"', false)
      ->assertSee('data-review-section="decision"', false)
      ->assertSee('Creators and contributions')
      ->assertSee('Supporting files')
      ->assertSee('Review decision')
      ->assertSee('ri-file-list-3-line', false)
      ->assertSee('ri-team-line', false)
      ->assertSee('ri-attachment-2', false)
      ->assertSee('ri-checkbox-circle-line', false)
      ->assertSee('Back to submissions')
      ->assertSee('Submission overview')
      ->assertSee('Low-cost water filtration device')
      ->assertSee('Background summary')
      ->assertSee('Research title')
      ->assertSee('Overview')
      ->assertSee('Technologies')
      ->assertSee('Costing')
      ->assertDontSee('Contact email')
      ->assertDontSee('Mobile number')
      ->assertDontSee('disabled readonly', false)
      ->assertSee('Substantial SLSU support')
      ->assertSee('Ownership consent')
      ->assertSee('ip-head-review-form')
      ->assertDontSee('data-save-as-xml', false)
      ->assertDontSee('class="modal fade"', false);
  }

  public function test_ip_head_revision_notes_and_endorsement_update_submitter_status(): void
  {
    $submitter = $this->makeUser('submitter');
    $ipHead = $this->makeUser('ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);

    $this->actingAs($ipHead)
      ->post(route('ip-head-dashboard.review', $disclosure), [
        'status' => 'returned',
      ])
      ->assertSessionHasErrors('review_notes');

    $this->actingAs($ipHead)
      ->post(route('ip-head-dashboard.review', $disclosure), [
        'status' => 'returned',
        'review_notes' => 'Please provide a clearer drawing of the filter housing.',
      ])
      ->assertRedirect(route('ip-head-dashboard.submissions', ['queue' => 'for-review']))
      ->assertSessionHas('toast_type', 'success');

    $disclosure->refresh();
    $this->assertSame('returned', $disclosure->status);
    $this->assertSame('Please provide a clearer drawing of the filter housing.', $disclosure->review_notes);

    $this->actingAs($submitter)
      ->get(route('dashboard.submissions.status-updates'))
      ->assertOk()
      ->assertJsonPath('0.status', 'returned')
      ->assertJsonPath('0.review_notes', 'Please provide a clearer drawing of the filter housing.');

    $this->actingAs($ipHead)
      ->post(route('ip-head-dashboard.review', $disclosure), [
        'status' => 'endorsed',
      ])
      ->assertRedirect(route('ip-head-dashboard.submissions', ['queue' => 'for-review']));

    $this->assertDatabaseHas('innovation_disclosures', [
      'id' => $disclosure->id,
      'status' => 'endorsed',
      'review_notes' => null,
    ]);
    $this->assertNotNull($disclosure->refresh()->endorsed_at);
  }

  public function test_submission_with_file_revisions_cannot_be_endorsed(): void
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
      ->assertSee('data-has-pending-file-revisions="true"', false);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.review', $disclosure), [
        'status' => 'endorsed',
      ])
      ->assertUnprocessable()
      ->assertJsonValidationErrors([
        'status' => 'This submission cannot be endorsed while any files still need revision.',
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

  public function test_ip_head_revision_notes_ajax_request_returns_json_instead_of_redirecting(): void
  {
    $submitter = $this->makeUser('ajax-submit-owner');
    $ipHead = $this->makeUser('ajax-ip-head', true);
    $disclosure = $this->makeDisclosure($submitter);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.review', $disclosure), [
        'status' => 'returned',
        'review_notes' => 'Please clarify the proposed changes.',
        'queue' => 'for-review',
        'page' => 1,
      ])
      ->assertOk()
      ->assertJsonPath('message', 'Disclosure review updated.')
      ->assertJsonPath('disclosure.id', $disclosure->id)
      ->assertJsonPath('disclosure.status', 'returned')
      ->assertJsonPath('disclosure.review_notes', 'Please clarify the proposed changes.');
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
      ->assertSee('Supporting files')
      ->assertSee('drawing.pdf')
      ->assertSee('class="btn btn-primary ip-head-file-card__save" type="submit" disabled', false)
      ->assertSee(
        'action="' . route('ip-head-dashboard.disclosures.attachments.review', $disclosure) . '"',
        false
      )
      ->assertSee('Latest')
      ->assertSee('ip-head-file-card--revised', false)
      ->assertSee('data-bs-target="#attachment-preview-' . $attachment->id . '"', false)
      ->assertSee('data-bs-target="#attachment-preview-' . $imageAttachment->id . '"', false)
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
      ->assertSee('name="attachments[' . $attachment->id . '][needs_revision]"', false)
      ->assertSee('data-attachment-id="' . $attachment->id . '"', false)
      ->assertSee('checked', false)
      ->assertSee('disabled', false)
      ->assertSee('Upload a higher-resolution drawing.')
      ->assertSee($attachment->submitted_at->format('M j, Y'))
      ->assertSee('Revision requested');

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
    ]);

    $this->actingAs($ipHead)
      ->postJson(route('ip-head-dashboard.disclosures.attachments.review', $disclosure), [
        'attachments' => [
          $drawing->id => [
            'needs_revision' => '1',
            'revision_notes' => 'Clarify the labels.',
          ],
          $methodology->id => [
            'needs_revision' => '1',
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
