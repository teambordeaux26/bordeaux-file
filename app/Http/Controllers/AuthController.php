<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin(): \Inertia\Response
    {
        return Inertia::render('Login', [
            'turnstileSiteKey' => config('services.turnstile.site_key'),
        ]);
    }

    public function login(Request $request)
    {
        $rules = [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];

        if (config('services.turnstile.secret_key')) {
            $rules['cf-turnstile-response'] = ['required', 'string', new \App\Rules\Turnstile];
        }

        $request->validate($rules);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = $request->user();
            if ($user) {
                $user->forceFill([
                    'last_login_at' => now(),
                ])->save();
            }

            AuditLog::create([
                'user_id'     => Auth::id(),
                'action'      => 'Login',
                'description' => "{$user->name} logged in successfully.",
                'ip_address'  => $request->ip(),
            ]);

            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        Auth::logout();

        if ($user) {
            $user->forceFill(['remember_token' => null])->save();

            AuditLog::create([
                'user_id'     => $user->id,
                'action'      => 'Logout',
                'description' => "{$user->name} logged out. The session was ended.",
                'ip_address'  => $request->ip(),
            ]);
        }

        if ($user && config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flash('success', 'You have been logged out. Your session has ended.');

        return Inertia::location(route('login'));
    }
}
