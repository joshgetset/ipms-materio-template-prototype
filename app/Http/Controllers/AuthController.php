<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class AuthController extends Controller
{
    protected function strongPasswordRules(): array
    {
        return [
            'required',
            'string',
            'min:8',
            'confirmed',
            function (string $attribute, mixed $value, \Closure $fail) {
                $password = (string) $value;

                if (mb_strlen($password) <= 8) {
                    $fail('Password must be more than 8 characters long.');
                    return;
                }

                if (! preg_match('/[a-z]/', $password)) {
                    $fail('Password must include at least one lowercase letter.');
                    return;
                }

                if (! preg_match('/[A-Z]/', $password)) {
                    $fail('Password must include at least one uppercase letter.');
                    return;
                }

                if (! preg_match('/[^A-Za-z0-9]/', $password)) {
                    $fail('Password must include at least one special character.');
                }
            },
        ];
    }

    public function showLogin()
    {
        return view('personal_layouts.login');
    }

    public function showSignin()
    {
        return view('personal_layouts.login');
    }

    public function redirectToGoogle()
    {
        if (! filled(config('services.google.client_id')) || ! filled(config('services.google.client_secret'))) {
            return redirect()->route('login')
                ->with('toast_type', 'error')
                ->with('toast_message', 'Google sign-in is not configured. Please contact the site administrator.');
        }

        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        if ($request->filled('error')) {
            return redirect()->route('login')
                ->with('toast_type', 'error')
                ->with('toast_message', 'Google sign-in was cancelled.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (InvalidStateException $exception) {
            Log::warning('Google sign-in callback rejected due to invalid OAuth state.', [
                'exception' => $exception,
            ]);

            return redirect()->route('login')
                ->with('toast_type', 'error')
                ->with('toast_message', 'Google sign-in could not be verified. Please try again.');
        }

        $email = $googleUser->getEmail();
        $googleData = $googleUser->getRaw();
        $emailVerified = filter_var(
            $googleData['email_verified'] ?? $googleData['verified_email'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! $emailVerified) {
            return redirect()->route('login')
                ->with('toast_type', 'error')
                ->with('toast_message', 'Google did not provide a verified email address.');
        }

        $normalizedEmail = mb_strtolower($email);
        $isIpHead = str_ends_with($normalizedEmail, '@southernleytestateu.edu.ph');
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
            ->first();

        if (! $user) {
            $name = trim((string) $googleUser->getName()) ?: Str::before($normalizedEmail, '@');
            $usernameBase = Str::slug($name) ?: 'google-user';
            $usernameBase = mb_substr($usernameBase, 0, 240);

            do {
                $username = $usernameBase.'-'.Str::lower(Str::random(8));
            } while (User::query()->where('username', $username)->exists());

            $user = User::create([
                'name' => $name,
                'email' => $normalizedEmail,
                'username' => $username,
                'password' => Str::random(64),
                'email_verified_at' => now(),
            ]);
        }

        if ($isIpHead && ! $user->is_ip_head) {
            $user->forceFill(['is_ip_head' => true])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->flash('toast_type', 'success');
        $request->session()->flash('toast_message', 'Signed in with Google successfully. Redirecting to your workspace...');

        return redirect()->route($user->is_ip_head ? 'ip-head-dashboard.index' : 'dashboard-analytics');
    }

    public function login(Request $request)
    {
        try {
            $credentials = $request->validate([
                'username' => ['required', 'string'],
                'password' => ['required', 'string'],
            ]);
        } catch (ValidationException $e) {
            // Deterministic: always land on /login, never on wherever
            // the combined page happened to be mounted
            return redirect()
                ->route('login')
                ->withInput($request->only('username'))
                ->withErrors($e->errors())
                ->with('toast_type', 'error')
                ->with('toast_message', 'Please enter both your username and password.');
        }

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $request->session()->flash('toast_type', 'success');
            $request->session()->flash('toast_message', 'Signed in successfully. Redirecting to your workspace...');

            return redirect()->route($request->user()->is_ip_head ? 'ip-head-dashboard.index' : 'dashboard-analytics');
        }

        return redirect()
            ->route('login')
            ->withInput($request->only('username'))
            ->with('toast_type', 'error')
            ->with('toast_message', 'Sign in failed. Please check your username and password.');
    }

    public function signin(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'username' => ['required', 'string', 'max:255', 'unique:users,username', 'alpha_dash'],
                'password' => $this->strongPasswordRules(),
            ]);
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())
                ->flatten()
                ->first() ?? 'Account creation failed. Please review your details and try again.';

            return redirect()->route('signin')
                ->withInput()
                ->withErrors($e->errors())
                ->with('toast_type', 'error')
                ->with('toast_message', $firstError);
        }

        // Belt-and-braces: guard against the race where two
        // submissions pass validation before either inserts. The
        // DB unique indexes are the real enforcement.
        if (
            User::where('email', $validated['email'])->exists()
            || User::where('username', $validated['username'])->exists()
        ) {
            return redirect()->route('signin')
                ->withInput()
                ->with('toast_type', 'error')
                ->with('toast_message', 'An account with this email or username already exists.');
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->flash('toast_type', 'success');
        $request->session()->flash('toast_message', 'Account created successfully. Welcome to IPMS!');

        return redirect()->route('dashboard-analytics');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
