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

  public function test_authenticated_user_can_view_the_disclosure_dashboard(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);

    $this->actingAs($user)
      ->get('/dashboard')
      ->assertOk()
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
      ->assertSee('Innovation Disclosure')
      ->assertSee('name="technology_type"', false)
      ->assertSee('name="primary_inventor"', false)
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
      ->assertSee('href="' . route('dashboard.submissions.show', $disclosure) . '"', false)
      ->assertDontSee('submission-details-modal');

    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('Submission Review')
      ->assertSeeInOrder(['Home', 'Submissions', 'Review Submission'])
      ->assertSee('<hr class="page-content__divider">', false)
      ->assertSee('methodology.pdf')
      ->assertSee('Description')
      ->assertSee('Substantial SLSU support')
      ->assertSee('Ownership consent')
      ->assertSee('data-bs-target="#background-panel-' . $disclosure->id . '"', false)
      ->assertSee('class="accordion-collapse collapse"', false)
      ->assertDontSee('Read more')
      ->assertSee('Update file')
      ->assertSee('class="disclosure-delete-form', false)
      ->assertSee('data-confirm-title="Are you sure to delete this submission?"', false);

    $this->actingAs($user)
      ->get(route('dashboard.submissions.attachments.download', [$disclosure, $attachment]))
      ->assertDownload('methodology.pdf');
  }

  public function test_user_can_replace_a_file_and_cannot_access_another_users_disclosure(): void
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
      'needs_revision' => true,
      'revision_notes' => 'Upload a clearer methodology file.',
    ]);

    $this->actingAs($user)
      ->post(route('dashboard.submissions.attachments.store', $disclosure), [
        'type' => 'methodology',
        'file' => UploadedFile::fake()->create('methodology-revised.pdf', 15, 'application/pdf'),
      ])
      ->assertRedirect(route('dashboard.submissions.index'))
      ->assertSessionHas('toast_type', 'success');

    $attachment->refresh();
    $this->assertSame('methodology-revised.pdf', $attachment->original_name);
    $this->assertSame(2, $attachment->version);
    $this->assertFalse($attachment->needs_revision);
    $this->assertNull($attachment->revision_notes);
    $this->assertSame(1, $disclosure->attachments()->count());
    $this->assertFalse(Storage::disk('local')->exists($oldPath));
    $this->assertTrue(Storage::disk('local')->exists($attachment->stored_path));

    $this->actingAs($user)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertOk()
      ->assertSee('V2');

    $otherUser = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $this->actingAs($otherUser)
      ->get(route('dashboard.submissions.attachments.download', [$disclosure, $attachment]))
      ->assertNotFound();

    $this->actingAs($otherUser)
      ->get(route('dashboard.submissions.show', $disclosure))
      ->assertNotFound();
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
    $data['attachments'] = [
      'methodology' => UploadedFile::fake()->create('methodology.pdf', 20, 'application/pdf'),
    ];

    $response = $this->actingAs($user)->post('/dashboard/innovation-disclosures', $data);

    $response->assertRedirect(route('dashboard.submissions.index'));
    $this->assertDatabaseHas('innovation_disclosures', [
      'user_id' => $user->id,
      'email' => 'inventor@example.com',
      'technology_title' => 'Low-cost water filtration device',
      'status' => 'submitted',
    ]);
    $this->assertDatabaseHas('inventors', [
      'innovation_disclosure_id' => DB::table('innovation_disclosures')->where('technology_title', 'Low-cost water filtration device')->value('id'),
      'first_name' => 'Juan',
      'is_primary' => true,
    ]);
    $this->assertDatabaseHas('disclosure_attachments', [
      'type' => 'methodology',
      'original_name' => 'methodology.pdf',
    ]);
    $storedPath = DB::table('disclosure_attachments')
      ->where('original_name', 'methodology.pdf')
      ->value('stored_path');
    $this->assertTrue(Storage::disk('local')->exists($storedPath));

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

  public function test_disclosure_rules_require_research_details_only_for_not_applicable_ownership(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $data['university_relationship'] = 'employee';
    $data['has_substantial_support'] = '1';
    $data['ownership_declaration'] = 'not_applicable';
    unset($data['research_title'], $data['research_approval_date']);

    $this->actingAs($user)
      ->from('/dashboard')
      ->post('/dashboard/innovation-disclosures', $data)
      ->assertSessionHasErrors(['research_title', 'research_approval_date']);

    $data = $this->validDisclosureData();
    unset($data['research_title'], $data['research_approval_date']);

    $this->actingAs($user)
      ->post('/dashboard/innovation-disclosures', $data)
      ->assertRedirect(route('dashboard.submissions.index'));
    $this->assertDatabaseHas('innovation_disclosures', [
      'user_id' => $user->id,
      'ownership_declaration' => 'retain_ownership',
      'research_title' => null,
      'research_approval_date' => null,
      'ownership_consent' => true,
      'has_substantial_support' => null,
    ]);

    $data = $this->validDisclosureData();
    $data['university_relationship'] = 'employee';
    $data['has_substantial_support'] = '0';
    $data['ownership_declaration'] = 'not_applicable';
    $data['research_title'] = 'Community water systems';
    $data['research_approval_date'] = '2026-09-01';

    $this->actingAs($user)
      ->from('/dashboard')
      ->post('/dashboard/innovation-disclosures', $data)
      ->assertSessionHasErrors('ownership_declaration');

    $data = $this->validDisclosureData();
    $data['ownership_declaration'] = 'not_applicable';
    $data['research_title'] = 'Community water systems';
    $data['research_approval_date'] = '2026-09-01';

    $this->actingAs($user)
      ->from('/dashboard')
      ->post('/dashboard/innovation-disclosures', $data)
      ->assertSessionHasErrors('ownership_declaration');

    $data = $this->validDisclosureData();
    $data['university_relationship'] = 'employee';
    unset($data['has_substantial_support']);

    $this->actingAs($user)
      ->from('/dashboard')
      ->post('/dashboard/innovation-disclosures', $data)
      ->assertSessionHasErrors('has_substantial_support');
  }

  public function test_ownership_consent_is_required(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    unset($data['ownership_consent']);

    $this->actingAs($user)
      ->from('/dashboard')
      ->post('/dashboard/innovation-disclosures', $data)
      ->assertSessionHasErrors('ownership_consent');
  }

  public function test_slsu_funded_employee_disclosure_is_slsu_owned_without_assignment(): void
  {
    Storage::fake('local');
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $data['university_relationship'] = 'employee';
    $data['funding_source'] = 'slsu_funded';
    $data['ownership_declaration'] = 'not_applicable';
    $data['research_title'] = 'Community water systems';
    $data['research_approval_date'] = '2026-09-01';

    $this->actingAs($user)
      ->post('/dashboard/innovation-disclosures', $data)
      ->assertRedirect(route('dashboard.submissions.index'));

    $this->assertDatabaseHas('innovation_disclosures', [
      'user_id' => $user->id,
      'ownership_declaration' => 'not_applicable',
      'has_substantial_support' => true,
      'ownership_consent' => true,
      'research_title' => 'Community water systems',
    ]);

    $data['ownership_declaration'] = 'retain_ownership';
    $this->actingAs($user)
      ->from('/dashboard')
      ->post('/dashboard/innovation-disclosures', $data)
      ->assertSessionHasErrors('ownership_declaration');
  }

  public function test_disclosure_requires_valid_mobile_and_minimum_description_lengths(): void
  {
    $user = User::factory()->create([
      'username' => fake()->unique()->userName(),
    ]);
    $data = $this->validDisclosureData();
    $data['mobile_no'] = '12345';
    $data['background_summary'] = 'Too short';
    $data['detailed_description'] = 'Too short';

    $this->actingAs($user)
      ->from('/dashboard')
      ->post('/dashboard/innovation-disclosures', $data)
      ->assertSessionHasErrors(['mobile_no', 'background_summary', 'detailed_description']);
  }

  private function validDisclosureData(): array
  {
    return [
      'email' => 'inventor@example.com',
      'mobile_no' => '09171234567',
      'technology_title' => 'Low-cost water filtration device',
      'technology_type' => 'mechanical',
      'university_relationship' => 'student',
      'funding_source' => 'personal',
      'ownership_declaration' => 'retain_ownership',
      'ownership_consent' => '1',
      'background_summary' => str_repeat('A compact filtration device for community water systems. ', 3),
      'detailed_description' => str_repeat('The device uses replaceable filtration media in a hand-operated housing. ', 3),
      'drawings_description' => null,
      'inventors' => [[
        'last_name' => 'Dela Cruz',
        'first_name' => 'Juan',
        'suffix' => null,
        'middle_initial' => 'P',
        'country_of_citizenship' => 'Philippines',
        'affiliation' => 'student',
      ]],
      'primary_inventor' => '0',
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
