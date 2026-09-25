<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

            return redirect()->route('home');
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

        return redirect('/login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
