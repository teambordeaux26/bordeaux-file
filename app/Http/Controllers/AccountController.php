<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Services\CertificateSignatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function show(CertificateSignatureService $signatures)
    {
        $user = Auth::user();

        return Inertia::render('Account/Index', [
            'account' => [
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => ucfirst($user->role ?? 'employee'),
                'department' => $user->department ?? '—',
                'position'   => $user->position ?? '—',
            ],
            'signer' => $signatures->payloadForUser($user),
        ]);
    }

    public function updateSignature(Request $request, CertificateSignatureService $signatures)
    {
        $user = $request->user();
        $hasSaved = $signatures->hasSavedSignature($user);

        $validated = $request->validate([
            'signing_name'  => 'required|string|max:255',
            'signing_title' => 'required|string|max:255',
            'signature'     => [$hasSaved ? 'nullable' : 'required', 'string'],
        ]);

        if (! empty($validated['signature'])) {
            $signatures->saveUserSignature(
                $user,
                $validated['signature'],
                $validated['signing_name'],
                $validated['signing_title']
            );
        } else {
            $signatures->updateSigningIdentity(
                $user,
                $validated['signing_name'],
                $validated['signing_title']
            );
        }

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'E-Signature Updated',
            'description' => "{$user->name} saved their certificate signing name and e-signature.",
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', 'Your signing name and e-signature have been saved. Certificates already issued will keep their original signature.');
    }

    public function destroySignature(Request $request, CertificateSignatureService $signatures)
    {
        $user = $request->user();
        $signatures->clearUserSignature($user);

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'E-Signature Removed',
            'description' => "{$user->name} removed their saved e-signature. Previously issued certificates were not changed.",
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', 'Your saved e-signature was removed. Certificates already issued still show the old signature.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->update([
            'password' => $validated['password'],
        ]);

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'Password Changed',
            'description' => "{$user->name} changed their own password.",
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', 'Your password has been updated.');
    }
}
