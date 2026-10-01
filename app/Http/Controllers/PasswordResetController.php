<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetOtpMail;
use App\Models\AuditLog;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    public function create()
    {
        return Inertia::render('ForgotPassword/Email');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contact_email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($data['contact_email']));
        $limitKey = 'password-otp:'.$request->ip().':'.$email;

        if (RateLimiter::tooManyAttempts($limitKey, 3)) {
            return back()->withErrors([
                'contact_email' => 'Too many codes were requested. Please wait a few minutes and try again.',
            ])->withInput();
        }

        $user = $this->findByContactEmail($email);

        if (! $user) {
            RateLimiter::hit($limitKey, 600);

            return back()->withErrors([
                'contact_email' => 'No account uses that contact email.',
            ])->withInput();
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PasswordResetOtp::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->delete();

        $otp = PasswordResetOtp::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::mailer('smtp')->to($user->contact_email)->send(new PasswordResetOtpMail($user, $code));
        } catch (\Throwable $e) {
            $otp->delete();
            report($e);

            return back()->withErrors([
                'contact_email' => 'The verification code could not be sent. Check the mail server and try again.',
            ])->withInput();
        }

        RateLimiter::hit($limitKey, 600);

        $request->session()->put('password_reset', [
            'user_id' => $user->id,
            'email' => $user->contact_email,
            'verified' => false,
        ]);

        return redirect()->route('password.otp');
    }

    public function verifyForm(Request $request)
    {
        $reset = $this->pendingReset($request);

        if (! $reset) {
            return redirect()->route('password.request');
        }

        return Inertia::render('ForgotPassword/Otp', [
            'maskedEmail' => $this->maskEmail($reset['email']),
        ]);
    }

    public function verify(Request $request)
    {
        $reset = $this->pendingReset($request);

        if (! $reset || $reset['verified']) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'code' => 'required|digits:6',
        ]);

        $otp = PasswordResetOtp::query()
            ->where('user_id', $reset['user_id'])
            ->whereNull('consumed_at')
            ->latest()
            ->first();

        if (! $otp || $otp->isExpired()) {
            $otp?->delete();

            return back()->withErrors([
                'code' => 'This code has expired. Request a new one.',
            ]);
        }

        if ($otp->attempts >= 5) {
            $otp->delete();

            return back()->withErrors([
                'code' => 'Too many incorrect codes. Request a new one.',
            ]);
        }

        if (! Hash::check($data['code'], $otp->code_hash)) {
            $otp->increment('attempts');

            return back()->withErrors([
                'code' => 'That code is incorrect.',
            ]);
        }

        $otp->update(['consumed_at' => now()]);

        $reset['verified'] = true;
        $request->session()->put('password_reset', $reset);

        return redirect()->route('password.reset.form');
    }

    public function resetForm(Request $request)
    {
        $reset = $this->pendingReset($request);

        if (! $reset || ! $reset['verified']) {
            return redirect()->route('password.request');
        }

        return Inertia::render('ForgotPassword/Reset');
    }

    public function reset(Request $request)
    {
        $reset = $this->pendingReset($request);

        if (! $reset || ! $reset['verified']) {
            return redirect()->route('password.request');
        }

        $data = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::query()->find($reset['user_id']);

        if (! $user) {
            $request->session()->forget('password_reset');

            return redirect()->route('password.request')->withErrors([
                'contact_email' => 'That account is no longer available.',
            ]);
        }

        $user->forceFill([
            'password' => $data['password'],
            'remember_token' => null,
        ])->save();

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->delete();
        }

        PasswordResetOtp::query()->where('user_id', $user->id)->delete();

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Password Reset',
            'description' => "{$user->name} reset the account password using a contact-email code.",
            'ip_address' => $request->ip(),
        ]);

        $request->session()->forget('password_reset');
        $request->session()->flash('success', 'Your password has been updated. Sign in with the new password.');

        return redirect()->route('login');
    }

    private function findByContactEmail(string $email): ?User
    {
        return User::query()
            ->whereNotNull('contact_email')
            ->whereRaw('LOWER(contact_email) = ?', [$email])
            ->latest('id')
            ->first();
    }

    /**
     * @return array{user_id: int, email: string, verified: bool}|null
     */
    private function pendingReset(Request $request): ?array
    {
        $reset = $request->session()->get('password_reset');

        if (! is_array($reset) || empty($reset['user_id']) || empty($reset['email'])) {
            return null;
        }

        return [
            'user_id' => (int) $reset['user_id'],
            'email' => (string) $reset['email'],
            'verified' => (bool) ($reset['verified'] ?? false),
        ];
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = substr($name, 0, 1);

        return $visible.str_repeat('*', max(strlen($name) - 1, 1)).'@'.$domain;
    }
}
