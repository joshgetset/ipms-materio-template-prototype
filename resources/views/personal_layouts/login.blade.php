<?php $skipTemplateAssets = true; ?>

@extends('layouts.blankLayout')

@section('title', 'Sign In')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet">

<link rel="stylesheet" href="{{ asset('css/login.css') }}">
<link rel="stylesheet" href="{{ asset('css/auth_animation.css') }}">
@endpush

@section('content')

@php
// Which card opens on load?
// Failures from POST /signin redirect back to /signin, so
// routeIs('signin') is the primary signal. The old('name') and
// old('password_confirmation') checks are a fallback: those
// fields exist only on the signup form, never on login.
$cameFromSignup = old('name') !== null
|| old('password_confirmation') !== null;

$showSignup = $cameFromSignup
|| request()->routeIs('signin')
|| request()->query('mode') === 'signup';
@endphp

<main class="login-page">

    @php
    $authMessage = session('toast_message');
    $authMessageType = session('toast_type', 'info');
    @endphp

    @if ($authMessage)
    <div
        class="auth-global-message auth-global-message-{{ $authMessageType }}"
        role="alert"
        aria-live="assertive">
        {{ $authMessage }}
    </div>
    @endif

    <div
        class="login-container auth-shell{{ $showSignup ? ' signup-mode' : '' }}"
        data-login-url="{{ route('login') }}"
        data-signup-url="{{ route('signin') }}">
        <a
            href="{{ route('home') }}"
            class="back-home"
            aria-label="Back to home">
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
        </a>


        <!-- =================================================
                 PANEL — gradient + illustration
                 ================================================= -->

        <section class="login-animation">

            <div class="animation-grid"></div>

            <div class="animation-glow"></div>

            <div class="brand-orb">
                <img
                    src="{{ asset('images/hero-carousel/slsu_logo.png') }}"
                    alt="SLSU logo"
                    class="auth-image auth-image-logo">
            </div>

            <div class="animation-caption">
                <span class="caption-line"></span>

                <span>
                    INTELLECTUAL PROPERTY
                </span>
            </div>

        </section>


        <!-- =================================================
                 FORM PANEL — login + signup cards
                 ================================================= -->

        <section class="login-form-panel auth-form-panel">

            <div
                class="login-card auth-card{{ $showSignup ? '' : ' is-active' }}"
                data-auth-card="login"
                @if ($showSignup) aria-hidden="true" @endif>

                <div
                    class="login-header fade-up"
                    style="--delay: 0.08s">
                    <img
                        src="{{ asset('images/hero-carousel/slsu_banner_monochrome.png') }}"
                        alt="SLSU"
                        class="login-banner">
                </div>

                <span
                    class="login-kicker fade-up"
                    style="--delay: 0.12s">
                    Welcome back
                </span>


                <form
                    method="POST"
                    action="{{ route('login.submit') }}"
                    class="login-form"
                    data-loading-form
                    data-loading-text="Signing in..."
                    novalidate>

                    @csrf

                    <div
                        class="floating-field fade-up"
                        style="--delay: 0.16s">

                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="{{ old('username') }}"
                            autocomplete="username"
                            placeholder=" "
                            required>

                        <label for="username">
                            Username
                        </label>

                    </div>

                    <div
                        class="floating-field password-field fade-up"
                        style="--delay: 0.22s">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            placeholder=" "
                            required>

                        <label for="password">
                            Password
                        </label>

                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                            data-password-toggle="password"
                            aria-label="Show password"
                            aria-controls="password">
                            <svg
                                class="icon-eye"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>

                            <svg
                                class="icon-eye-off"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-10.5-7-10.5-7a19.3 19.3 0 0 1 4.22-5.06"></path>
                                <path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c7 0 10.5 7 10.5 7a19.5 19.5 0 0 1-2.16 3.19"></path>
                                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>

                    </div>

                    <div
                        class="login-options fade-up"
                        style="--delay: 0.28s">

                        <label class="remember-option">

                            <input
                                type="checkbox"
                                name="remember">

                            <span>
                                Remember me
                            </span>

                        </label>

                        <a
                            href="#"
                            class="forgot-link">
                            Forgot password?
                        </a>

                    </div>

                    <button
                        type="submit"
                        class="login-button fade-up"
                        style="--delay: 0.34s">
                        <span>
                            Login
                        </span>

                        <span class="login-arrow">
                            →
                        </span>
                    </button>

                </form>

                <div
                    class="login-divider fade-up"
                    style="--delay: 0.4s">
                    <span>OR</span>
                </div>

                <div
                    class="social-login fade-up"
                    style="--delay: 0.44s">
                    <button type="button" class="google-button" disabled aria-disabled="true">
                        <svg viewBox="0 0 48 48" aria-hidden="true">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                        </svg>
                        <span>Login with Google</span>
                    </button>
                </div>

                <p
                    class="login-bottom fade-up"
                    style="--delay: 0.48s">
                    Don't have an account?

                    <a href="#" data-auth-toggle="signup">
                        Create an account
                    </a>
                </p>

            </div>

            <div
                class="login-card auth-card signup-card{{ $showSignup ? ' is-active' : '' }}"
                data-auth-card="signup"
                @if (! $showSignup) aria-hidden="true" @endif>

                <div
                    class="login-header fade-up"
                    style="--delay: 0.08s">
                    <img
                        src="{{ asset('images/hero-carousel/slsu_banner_monochrome.png') }}"
                        alt="SLSU"
                        class="login-banner">
                </div>

                <span
                    class="login-kicker fade-up"
                    style="--delay: 0.12s">
                    Create Account
                </span>

                <form
                    method="POST"
                    action="{{ route('signin.submit') }}"
                    class="login-form signup-form"
                    data-loading-form
                    data-loading-text="Creating account..."
                    novalidate>
                    @csrf

                    <div
                        class="floating-field fade-up"
                        style="--delay: 0.16s">
                        <input
                            type="text"
                            id="signup_name"
                            name="name"
                            value="{{ old('name') }}"
                            autocomplete="name"
                            placeholder=" "
                            required>
                        <label for="signup_name">Full name</label>
                    </div>

                    <div
                        class="floating-field fade-up"
                        style="--delay: 0.2s">
                        <input
                            type="email"
                            id="signup_email"
                            name="email"
                            value="{{ old('email') }}"
                            autocomplete="email"
                            placeholder=" "
                            required>
                        <label for="signup_email">Email address</label>
                    </div>

                    <div
                        class="floating-field fade-up"
                        style="--delay: 0.24s">
                        <input
                            type="text"
                            id="signup_username"
                            name="username"
                            value="{{ old('username') }}"
                            autocomplete="username"
                            placeholder=" "
                            required>
                        <label for="signup_username">Username</label>
                    </div>

                    <div
                        class="floating-field password-field fade-up"
                        style="--delay: 0.28s">
                        <input
                            type="password"
                            id="signup_password"
                            name="password"
                            autocomplete="new-password"
                            placeholder=" "
                            required>
                        <label for="signup_password">Password</label>
                        <button
                            type="button"
                            class="password-toggle"
                            data-password-toggle="signup_password"
                            aria-label="Show password"
                            aria-controls="signup_password">
                            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-10.5-7-10.5-7a19.3 19.3 0 0 1 4.22-5.06"></path>
                                <path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c7 0 10.5 7 10.5 7a19.5 19.5 0 0 1-2.16 3.19"></path>
                                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>

                    <div class="password-strength" data-password-strength="weak" aria-live="polite">
                        <div class="password-strength-bar">
                            <span class="password-strength-fill" data-password-strength-fill></span>
                        </div>
                        <div class="password-strength-meta">
                            <span>Password strength</span>
                            <span class="password-strength-status" data-password-strength-status>Weak</span>
                        </div>
                    </div>

                    <div
                        class="floating-field password-field fade-up"
                        style="--delay: 0.32s">
                        <input
                            type="password"
                            id="signup_password_confirmation"
                            name="password_confirmation"
                            autocomplete="new-password"
                            placeholder=" "
                            required>
                        <label for="signup_password_confirmation">Confirm password</label>
                        <button
                            type="button"
                            class="password-toggle"
                            data-password-toggle="signup_password_confirmation"
                            aria-label="Show password"
                            aria-controls="signup_password_confirmation">
                            <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7S1.5 12 1.5 12Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-10.5-7-10.5-7a19.3 19.3 0 0 1 4.22-5.06"></path>
                                <path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c7 0 10.5 7 10.5 7a19.5 19.5 0 0 1-2.16 3.19"></path>
                                <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>

                    <div
                        class="password-confirm-feedback"
                        data-password-confirmation-status
                        aria-live="polite"
                        hidden></div>

                    <button
                        type="submit"
                        class="login-button fade-up"
                        style="--delay: 0.38s">
                        <span>Create account</span>
                        <span class="login-arrow">→</span>
                    </button>
                </form>

                <div
                    class="login-divider fade-up"
                    style="--delay: 0.42s">
                    <span>OR</span>
                </div>

                <div
                    class="social-login fade-up"
                    style="--delay: 0.46s">
                    <button type="button" class="google-button" disabled aria-disabled="true">
                        <svg viewBox="0 0 48 48" aria-hidden="true">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                        </svg>
                        <span>Create account with Google</span>
                    </button>
                </div>

                <p
                    class="login-bottom fade-up"
                    style="--delay: 0.5s">
                    Already have an account?

                    <a href="#" data-auth-toggle="login">
                        Login
                    </a>
                </p>
            </div>

        </section>

    </div>

</main>

<div
    id="loadingModal"
    class="loading-modal"
    aria-live="polite"
    aria-hidden="true">
    <div class="loading-dialog" role="status" aria-live="polite">
        <div class="loading-spinner" aria-hidden="true"></div>
        <p id="loadingText">Loading...</p>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/login.js') }}" defer></script>
@endpush