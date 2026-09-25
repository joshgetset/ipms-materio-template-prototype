<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuthSignupTest extends TestCase
{
  use DatabaseTransactions;

  public function test_user_can_register_with_a_strong_password(): void
  {
    $response = $this->from('/signin')->post('/signin', [
      'name' => 'Juan Dela Cruz',
      'email' => 'juan@example.com',
      'username' => 'juan123',
      'password' => 'StrongPass!2026',
      'password_confirmation' => 'StrongPass!2026',
    ]);

    $response->assertRedirect('/login');
    $this->assertDatabaseHas('users', [
      'email' => 'juan@example.com',
      'username' => 'juan123',
    ]);
  }

  public function test_user_cannot_register_with_a_weak_password(): void
  {
    $response = $this->from('/signin')->post('/signin', [
      'name' => 'Weak User',
      'email' => 'weak@example.com',
      'username' => 'weakuser',
      'password' => 'weakpass',
      'password_confirmation' => 'weakpass',
    ]);

    $response->assertSessionHasErrors('password');
  }
}
