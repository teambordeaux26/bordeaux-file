<?php

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

function resetAccount(): User
{
    return User::factory()->create([
        'email' => 'maria.santos@oas-dms.com',
        'contact_email' => 'maria.santos@gmail.com',
        'role' => 'employee',
        'status' => 'active',
        'password' => 'old-password',
    ]);
}

it('sends a code through smtp when the contact email exists', function () {
    Mail::fake();
    resetAccount();

    post('/forgot-password', [
        'contact_email' => 'Maria.Santos@gmail.com',
    ])->assertRedirect(route('password.otp'));

    Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) {
        return $mail->hasTo('maria.santos@gmail.com')
            && $mail->mailer === 'smtp'
            && preg_match('/^\d{6}$/', $mail->code);
    });
});

it('does not send a code when the contact email is unknown', function () {
    Mail::fake();

    post('/forgot-password', [
        'contact_email' => 'missing@example.com',
    ])->assertSessionHasErrors('contact_email');

    Mail::assertNothingSent();
});

it('lets the user set a new password after the correct code', function () {
    Mail::fake();
    $user = resetAccount();

    post('/forgot-password', [
        'contact_email' => 'maria.santos@gmail.com',
    ])->assertRedirect(route('password.otp'));

    $code = null;
    Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$code) {
        $code = $mail->code;

        return true;
    });

    post('/forgot-password/verify', [
        'code' => $code,
    ])->assertRedirect(route('password.reset.form'));

    post('/forgot-password/reset', [
        'password' => 'new-password-1',
        'password_confirmation' => 'new-password-1',
    ])->assertRedirect(route('login'));

    expect(Hash::check('new-password-1', $user->fresh()->password))->toBeTrue();

    config(['services.turnstile.secret_key' => null]);

    post('/login', [
        'email' => 'maria.santos@oas-dms.com',
        'password' => 'new-password-1',
    ])->assertRedirect(route('dashboard'));
});

it('rejects an incorrect code', function () {
    Mail::fake();
    resetAccount();

    post('/forgot-password', [
        'contact_email' => 'maria.santos@gmail.com',
    ]);

    post('/forgot-password/verify', [
        'code' => '000000',
    ])->assertSessionHasErrors('code');
});
