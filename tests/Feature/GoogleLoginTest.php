<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as GoogleUser;
use Mockery;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use DatabaseTransactions;

    public function test_google_sign_in_creates_an_account_for_a_verified_email(): void
    {
        $this->mockGoogleUser('google.user@example.com', true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard-analytics'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'google.user@example.com',
            'is_ip_head' => false,
        ]);
        $this->assertNotNull(User::query()->where('email', 'google.user@example.com')->firstOrFail()->email_verified_at);
    }

    public function test_google_sign_in_assigns_ip_head_role_to_verified_university_emails(): void
    {
        $this->mockGoogleUser('ip.head@southernleytestateu.edu.ph', true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('ip-head-dashboard.index'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'ip.head@southernleytestateu.edu.ph',
            'is_ip_head' => true,
        ]);
    }

    public function test_google_sign_in_promotes_existing_university_account_to_ip_head(): void
    {
        $user = User::factory()->create([
            'email' => 'existing.ip.head@southernleytestateu.edu.ph',
            'username' => 'existing-ip-head-domain',
            'is_ip_head' => false,
        ]);
        $this->mockGoogleUser('existing.ip.head@southernleytestateu.edu.ph', true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('ip-head-dashboard.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_ip_head' => true,
        ]);
    }

    public function test_google_sign_in_does_not_assign_ip_head_role_to_a_similar_domain(): void
    {
        $this->mockGoogleUser('user@southernleytestateu.edu.ph.attacker.com', true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard-analytics'));

        $this->assertDatabaseHas('users', [
            'email' => 'user@southernleytestateu.edu.ph.attacker.com',
            'is_ip_head' => false,
        ]);
    }

    public function test_google_sign_in_uses_an_existing_account_without_changing_its_role(): void
    {
        $user = User::factory()->create([
            'email' => 'ip.head@example.com',
            'username' => 'existing-ip-head',
            'is_ip_head' => true,
        ]);
        $this->mockGoogleUser('IP.HEAD@example.com', true);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('ip-head-dashboard.index'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame(
            1,
            User::query()->whereRaw('LOWER(email) = ?', ['ip.head@example.com'])->count()
        );
    }

    public function test_google_sign_in_rejects_an_unverified_email(): void
    {
        $this->mockGoogleUser('unverified@example.com', false);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('toast_message', 'Google did not provide a verified email address.');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'unverified@example.com',
        ]);
    }

    public function test_google_redirect_reports_missing_configuration(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('toast_message', 'Google sign-in is not configured. Please contact the site administrator.');
    }

    public function test_google_redirect_uses_the_configured_callback_url(): void
    {
        config([
            'services.google.client_id' => 'test-google-client-id',
            'services.google.client_secret' => 'test-google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $this->assertSame('test-google-client-id', $query['client_id'] ?? null);
        $this->assertSame('http://localhost/auth/google/callback', $query['redirect_uri'] ?? null);
    }

    public function test_google_sign_in_rejects_an_invalid_oauth_state(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException());

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('toast_message', 'Google sign-in could not be verified. Please try again.');
    }

    private function mockGoogleUser(string $email, bool $verified): void
    {
        $googleUser = GoogleUser::fake([
            'email' => $email,
            'name' => 'Google User',
        ]);
        $googleUser->setRaw(['email_verified' => $verified]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}
