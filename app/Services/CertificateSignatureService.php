<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CertificateSignatureService
{
    public const FALLBACK_NAME = 'Sche O. Ruivivar';

    public const FALLBACK_TITLE = 'Office Administrator';

    public function payloadForUser(?User $user): array
    {
        return [
            'signing_name'  => $user?->signing_name ?: $user?->name ?: self::FALLBACK_NAME,
            'signing_title' => $user?->signing_title ?: $user?->position ?: self::FALLBACK_TITLE,
            'has_signature' => $this->hasSavedSignature($user),
            'signature_url' => $this->signatureDataUri($user?->signature_path),
        ];
    }

    public function saveUserSignature(User $user, string $dataUrl, ?string $signingName = null, ?string $signingTitle = null): void
    {
        $binary = $this->decodePngDataUrl($dataUrl);
        $path = 'signatures/users/'.$user->id.'.png';

        Storage::disk('public')->put($path, $binary);

        $user->update([
            'signature_path' => $path,
            'signing_name'   => $this->resolveName($user, $signingName),
            'signing_title'  => $this->resolveTitle($user, $signingTitle),
        ]);
    }

    public function updateSigningIdentity(User $user, ?string $signingName, ?string $signingTitle): void
    {
        $user->update([
            'signing_name'  => $this->resolveName($user, $signingName),
            'signing_title' => $this->resolveTitle($user, $signingTitle),
        ]);
    }

    public function clearUserSignature(User $user): void
    {
        if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $user->update(['signature_path' => null]);
    }

    public function captureForCertificate(
        Certificate $certificate,
        User $user,
        ?string $signatureDataUrl,
        ?string $signingName,
        ?string $signingTitle,
        bool $requireSignature = true
    ): void {
        $dataUrl = $this->normalizeDataUrl($signatureDataUrl);

        if ($dataUrl) {
            $this->saveUserSignature($user, $dataUrl, $signingName, $signingTitle);
        } else {
            $this->updateSigningIdentity($user, $signingName, $signingTitle);
        }

        $this->snapshotOntoCertificate($certificate, $user->fresh(), $requireSignature);
    }

    public function snapshotOntoCertificate(Certificate $certificate, ?User $user, bool $requireSignature = false): void
    {
        $user = $user ?? $this->officialSigner();

        if ($requireSignature && ! $this->hasSavedSignature($user)) {
            throw ValidationException::withMessages([
                'signature' => 'Draw or save your e-signature before issuing a certificate. Previously issued certificates keep their original signature.',
            ]);
        }

        $name = $this->resolveName($user, $user?->signing_name);
        $title = $this->resolveTitle($user, $user?->signing_title);
        $snapPath = null;

        if ($this->hasSavedSignature($user)) {
            $snapPath = 'signatures/certificates/'.$certificate->id.'.png';
            Storage::disk('public')->put(
                $snapPath,
                Storage::disk('public')->get($user->signature_path)
            );
        }

        $certificate->update([
            'issued_by'             => $certificate->issued_by ?: $user?->id,
            'signer_name'           => $name,
            'signer_title'          => $title,
            'signer_signature_path' => $snapPath,
        ]);
    }

    public function officialSigner(): ?User
    {
        $settings = SystemSetting::current();

        if ($settings->official_signer_user_id) {
            $designated = User::query()
                ->where('id', $settings->official_signer_user_id)
                ->where('status', 'active')
                ->first();

            if ($designated) {
                return $designated;
            }
        }

        return User::query()
            ->where('status', 'active')
            ->whereNotNull('signature_path')
            ->orderByRaw("CASE WHEN role = 'admin' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->first()
            ?? User::query()->where('role', 'admin')->where('status', 'active')->orderBy('id')->first();
    }

    public function hasSavedSignature(?User $user): bool
    {
        return $user
            && filled($user->signature_path)
            && Storage::disk('public')->exists($user->signature_path);
    }

    public function signatureDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(Storage::disk('public')->get($path));
    }

    public function signaturePublicUrl(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $version = Storage::disk('public')->lastModified($path);

        return asset('storage/'.$path).'?v='.$version;
    }

    public function decodePngDataUrl(string $dataUrl): string
    {
        if (! preg_match('#^data:image/(png|jpeg|jpg|webp);base64,#i', $dataUrl)) {
            throw ValidationException::withMessages([
                'signature' => 'The signature must be a PNG, JPG, or WEBP image.',
            ]);
        }

        $binary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);

        if ($binary === false || strlen($binary) < 50) {
            throw ValidationException::withMessages([
                'signature' => 'The signature drawing is empty or invalid.',
            ]);
        }

        if (strlen($binary) > 2 * 1024 * 1024) {
            throw ValidationException::withMessages([
                'signature' => 'The signature image is too large.',
            ]);
        }

        if (str_starts_with(strtolower($dataUrl), 'data:image/png')) {
            return $binary;
        }

        return $this->convertImageBinaryToPng($binary);
    }

    private function convertImageBinaryToPng(string $binary): string
    {
        if (! function_exists('imagecreatefromstring')) {
            throw ValidationException::withMessages([
                'signature' => 'The signature must be a PNG image.',
            ]);
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            throw ValidationException::withMessages([
                'signature' => 'The signature image could not be read. Try a PNG or JPG file.',
            ]);
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        if ($png === false || strlen($png) < 50) {
            throw ValidationException::withMessages([
                'signature' => 'The signature image could not be saved.',
            ]);
        }

        if (strlen($png) > 1024 * 1024) {
            throw ValidationException::withMessages([
                'signature' => 'The signature image is too large.',
            ]);
        }

        return $png;
    }

    public function signerPayload(Certificate $certificate): array
    {
        return [
            'name'          => $certificate->signer_name ?: self::FALLBACK_NAME,
            'title'         => $certificate->signer_title ?: self::FALLBACK_TITLE,
            'signature_url' => $this->signatureDataUri($certificate->signer_signature_path),
        ];
    }

    private function resolveName(?User $user, ?string $override): string
    {
        $name = trim((string) ($override ?: $user?->signing_name ?: $user?->name));

        return $name !== '' ? $name : self::FALLBACK_NAME;
    }

    private function resolveTitle(?User $user, ?string $override): string
    {
        $title = trim((string) ($override ?: $user?->signing_title ?: $user?->position));

        return $title !== '' ? $title : self::FALLBACK_TITLE;
    }

    private function normalizeDataUrl(?string $dataUrl): ?string
    {
        if (! is_string($dataUrl) || ! preg_match('#^data:image/(png|jpeg|jpg|webp);base64,#i', $dataUrl)) {
            return null;
        }

        return $dataUrl;
    }
}
