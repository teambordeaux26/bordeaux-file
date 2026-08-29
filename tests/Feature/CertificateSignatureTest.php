<?php

use App\Models\Certificate;
use App\Models\User;
use App\Models\VisitorLog;
use App\Services\CertificateSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function sampleSignaturePng(string $seed = 'old'): string
{
    $binary = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);

    return 'data:image/png;base64,'.base64_encode($binary.$seed);
}

it('keeps the original signatory on a certificate after the user changes their e-signature', function () {
    Storage::fake('public');

    $service = app(CertificateSignatureService::class);

    $user = User::factory()->create([
        'name' => 'Sche O. Ruivivar',
        'position' => 'Office Administrator',
        'role' => 'admin',
        'status' => 'active',
    ]);

    $service->saveUserSignature(
        $user,
        sampleSignaturePng('old-sign'),
        'Sche O. Ruivivar',
        'Office Administrator'
    );

    $visitor = VisitorLog::query()->create([
        'visitor_name' => 'Juan Dela Cruz',
        'purpose' => 'Committee hearing',
        'address' => 'Brgy. Ilaor Norte, Oas, Albay',
        'time_in' => now(),
    ]);

    $certificate = Certificate::query()->create([
        'visitor_log_id' => $visitor->id,
        'certificate_no' => 'COA-2026-001',
        'issued_by' => $user->id,
        'issued_at' => now(),
    ]);

    $service->snapshotOntoCertificate($certificate, $user->fresh(), true);
    $certificate->refresh();

    $originalPath = $certificate->signer_signature_path;
    $originalBytes = Storage::disk('public')->get($originalPath);

    $service->saveUserSignature(
        $user->fresh(),
        sampleSignaturePng('new-sign'),
        'New Secretary',
        'Acting Secretary'
    );

    $user->update([
        'name' => 'New Secretary',
        'status' => 'inactive',
    ]);

    $certificate->refresh();

    expect($certificate->signer_name)->toBe('Sche O. Ruivivar')
        ->and($certificate->signer_title)->toBe('Office Administrator')
        ->and($certificate->signer_signature_path)->toBe($originalPath)
        ->and(Storage::disk('public')->get($originalPath))->toBe($originalBytes);

    $payload = $service->signerPayload($certificate);

    expect($payload['name'])->toBe('Sche O. Ruivivar')
        ->and($payload['title'])->toBe('Office Administrator');

    $html = view('pdf.certificate_of_appearance', [
        'visitor_name'     => 'Juan Dela Cruz',
        'address'          => 'Brgy. Ilaor Norte, Oas, Albay',
        'purpose'          => 'Committee hearing',
        'day'              => '29',
        'month_year'       => 'August 2026',
        'certificate_no'   => $certificate->certificate_no,
        'qr_code'          => base64_encode('<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
        'signer_name'      => $payload['name'],
        'signer_title'     => $payload['title'],
        'signer_signature' => $service->signatureDataUri($certificate->signer_signature_path),
    ])->render();

    expect($html)
        ->toContain('Sche O. Ruivivar')
        ->toContain('Office Administrator')
        ->not->toContain('New Secretary')
        ->toContain('data:image/png;base64,');
});

it('accepts an uploaded jpeg signature and stores it as png', function () {
    Storage::fake('public');

    $jpeg = imagecreatetruecolor(8, 8);
    imagefilledrectangle($jpeg, 0, 0, 7, 7, imagecolorallocate($jpeg, 20, 20, 20));
    ob_start();
    imagejpeg($jpeg, null, 90);
    $binary = ob_get_clean();
    imagedestroy($jpeg);

    $user = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'name' => 'Office Administrator',
        'position' => 'Office Administrator',
    ]);

    app(CertificateSignatureService::class)->saveUserSignature(
        $user,
        'data:image/jpeg;base64,'.base64_encode($binary),
        'Sche O. Ruivivar',
        'Office Administrator'
    );

    $user->refresh();

    expect($user->signature_path)->not->toBeNull()
        ->and(Storage::disk('public')->get($user->signature_path))->toStartWith("\x89PNG");
});

it('lets staff save an e-signature from their account page', function () {
    Storage::fake('public');
    $this->withoutVite();

    $user = User::factory()->create([
        'name' => 'Office Administrator',
        'role' => 'admin',
        'status' => 'active',
        'position' => 'Office Administrator',
    ]);

    $this->actingAs($user)
        ->get('/account')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Account/Index')
            ->where('signer.signing_name', 'Office Administrator')
            ->where('signer.has_signature', false)
        );

    $this->actingAs($user)
        ->put('/account/signature', [
            'signing_name' => 'Sche O. Ruivivar',
            'signing_title' => 'Office Administrator',
            'signature' => sampleSignaturePng('account'),
        ])
        ->assertRedirect();

    $user->refresh();

    expect($user->signing_name)->toBe('Sche O. Ruivivar')
        ->and($user->signing_title)->toBe('Office Administrator')
        ->and($user->signature_path)->not->toBeNull();
});

it('requires an e-signature when generating a certificate', function () {
    Storage::fake('public');
    $this->withoutVite();

    $user = User::factory()->create([
        'role' => 'admin',
        'status' => 'active',
        'name' => 'Office Administrator',
        'position' => 'Office Administrator',
    ]);

    $visitor = VisitorLog::query()->create([
        'visitor_name' => 'Maria Santos',
        'purpose' => 'Session',
        'address' => 'Brgy. Ilaor Sur, Oas, Albay',
        'time_in' => now(),
    ]);

    $this->actingAs($user)
        ->from('/certificates')
        ->post('/certificates', [
            'visitor_log_id' => $visitor->id,
            'signing_name' => 'Sche O. Ruivivar',
            'signing_title' => 'Office Administrator',
        ])
        ->assertRedirect('/certificates')
        ->assertSessionHasErrors('signature');

    $this->actingAs($user)
        ->post('/certificates', [
            'visitor_log_id' => $visitor->id,
            'signing_name' => 'Sche O. Ruivivar',
            'signing_title' => 'Office Administrator',
            'signature' => sampleSignaturePng('issue'),
        ])
        ->assertRedirect();

    $certificate = Certificate::query()->first();

    expect($certificate)->not->toBeNull()
        ->and($certificate->signer_name)->toBe('Sche O. Ruivivar')
        ->and($certificate->signer_title)->toBe('Office Administrator')
        ->and($certificate->signer_signature_path)->not->toBeNull();
});

