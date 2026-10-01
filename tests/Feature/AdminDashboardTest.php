<?php

namespace Tests\Feature;

use App\Models\InnovationDisclosure;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
  use DatabaseTransactions;

  private const TEST_ADMIN_PASSWORD = 'TestAdminPass!';

  public function test_only_admins_can_access_the_review_dashboard_and_update_reviews(): void
  {
    $submitter = $this->makeUser('submitter');
    $admin = $this->makeUser('admin', true);
    $disclosure = $this->makeDisclosure($submitter);
    $reviewedDisclosure = $this->makeDisclosure($submitter);
    $reviewedDisclosure->update(['status' => 'endorsed']);

    $this->actingAs($submitter)
      ->get(route('admin-dashboard.index'))
      ->assertForbidden();

    $this->actingAs($submitter)
      ->get(route('admin-dashboard.disclosures.show', $disclosure))
      ->assertForbidden();

    $this->actingAs($submitter)
      ->post(route('admin-dashboard.review', $disclosure), [
        'status' => 'endorsed',
      ])
      ->assertForbidden();

    $this->actingAs($admin)
      ->get(route('admin-dashboard.index'))
      ->assertOk()
      ->assertSee('Admin Dashboard')
      ->assertSeeInOrder(['Home', 'Admin Dashboard', 'For Review', $disclosure->technology_title, 'Reviewed Submissions', $reviewedDisclosure->technology_title])
      ->assertSee('Files')
      ->assertSee('Low-cost water filtration device')
      ->assertSee('submitter@example.com')
      ->assertSee('admin_dashboard/disclosures/' . $disclosure->id . '?queue=for-review&amp;page=1', false)
      ->assertDontSee('admin-review-' . $disclosure->id);

    $this->actingAs($admin)
      ->get(route('admin-dashboard.disclosures.show', ['disclosure' => $disclosure, 'queue' => 'for-review', 'page' => 1]))
      ->assertOk()
      ->assertSeeInOrder(['Home', 'Admin Dashboard', 'Review Submission'])
      ->assertSee('Low-cost water filtration device')
      ->assertSee('Background summary')
      ->assertSee('Inventors')
      ->assertSee('Substantial SLSU support')
      ->assertSee('Ownership consent')
      ->assertSee('admin-review-form')
      ->assertDontSee('class="modal fade"', false);
  }

  public function test_admin_revision_notes_and_endorsement_update_submitter_status(): void
  {
    $submitter = $this->makeUser('submitter');
    $admin = $this->makeUser('admin', true);
    $disclosure = $this->makeDisclosure($submitter);

    $this->actingAs($admin)
      ->post(route('admin-dashboard.review', $disclosure), [
        'status' => 'returned',
      ])
      ->assertSessionHasErrors('review_notes');

    $this->actingAs($admin)
      ->post(route('admin-dashboard.review', $disclosure), [
        'status' => 'returned',
        'review_notes' => 'Please provide a clearer drawing of the filter housing.',
      ])
      ->assertRedirect(route('admin-dashboard.index'))
      ->assertSessionHas('toast_type', 'success');

    $disclosure->refresh();
    $this->assertSame('returned', $disclosure->status);
    $this->assertSame('Please provide a clearer drawing of the filter housing.', $disclosure->review_notes);

    $this->actingAs($submitter)
      ->get(route('dashboard.submissions.status-updates'))
      ->assertOk()
      ->assertJsonPath('0.status', 'returned')
      ->assertJsonPath('0.review_notes', 'Please provide a clearer drawing of the filter housing.');

    $this->actingAs($admin)
      ->post(route('admin-dashboard.review', $disclosure), [
        'status' => 'endorsed',
      ])
      ->assertRedirect(route('admin-dashboard.index'));

    $this->assertDatabaseHas('innovation_disclosures', [
      'id' => $disclosure->id,
      'status' => 'endorsed',
      'review_notes' => null,
    ]);
  }

  public function test_admin_can_request_revision_for_a_specific_file_and_submitter_can_see_it(): void
  {
    $submitter = $this->makeUser('file-owner');
    $admin = $this->makeUser('file-reviewer', true);
    $disclosure = $this->makeDisclosure($submitter);
    $attachment = $disclosure->attachments()->create([
      'type' => 'drawings',
      'original_name' => 'drawing.pdf',
      'stored_path' => "innovation-disclosures/{$disclosure->id}/drawings/drawing.pdf",
      'mime_type' => 'application/pdf',
      'size_bytes' => 1024,
      'version' => 2,
    ]);

    $this->actingAs($admin)
      ->get(route('admin-dashboard.index'))
      ->assertOk()
      ->assertSee('Review files for Low-cost water filtration device', false)
      ->assertSee('ri-more-2-line', false)
      ->assertSee('V2')
      ->assertSee('Needs revision');

    $this->actingAs($admin)
      ->get(route('admin-dashboard.disclosures.show', $disclosure))
      ->assertOk()
      ->assertSee('V2');

    $this->actingAs($admin)
      ->post(route('admin-dashboard.attachments.review', $attachment), [
        'needs_revision' => '1',
      ])
      ->assertSessionHasErrors('revision_notes');

    $this->actingAs($admin)
      ->post(route('admin-dashboard.attachments.review', $attachment), [
        'needs_revision' => '1',
        'revision_notes' => 'Upload a higher-resolution drawing.',
      ])
      ->assertRedirect(route('admin-dashboard.index'));

    $attachment->refresh();
    $this->assertTrue($attachment->needs_revision);
    $this->assertSame('Upload a higher-resolution drawing.', $attachment->revision_notes);

    $this->actingAs($submitter)
      ->get(route('dashboard.submissions.status-updates'))
      ->assertOk()
      ->assertJsonPath('0.attachments.0.needs_revision', true)
      ->assertJsonPath('0.attachments.0.revision_notes', 'Upload a higher-resolution drawing.');
  }

  public function test_admin_login_redirects_to_admin_dashboard(): void
  {
    $admin = $this->makeUser('superadmingetes', true);

    $this->post(route('login.submit'), [
      'username' => $admin->username,
      'password' => self::TEST_ADMIN_PASSWORD,
    ])
      ->assertRedirect(route('admin-dashboard.index'));
  }

  private function makeUser(string $username, bool $isAdmin = false): User
  {
    return User::factory()->create([
      'name' => ucfirst($username),
      'username' => $username . '-' . fake()->unique()->numerify('####'),
      'email' => $username . '@example.com',
      'password' => Hash::make(self::TEST_ADMIN_PASSWORD),
      'is_admin' => $isAdmin,
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
