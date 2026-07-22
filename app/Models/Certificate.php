<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_log_id',
        'certificate_no',
        'verification_token',
        'issued_by',
        'issued_at',
        'file_path',
    ];

    protected static function booted(): void
    {
        static::creating(function (Certificate $certificate) {
            if (empty($certificate->verification_token)) {
                $certificate->verification_token = Str::uuid()->toString();
            }
        });
    }

    public function ensureVerificationToken(): void
    {
        if (! empty($this->verification_token)) {
            return;
        }

        $this->forceFill([
            'verification_token' => Str::uuid()->toString(),
        ])->save();
    }

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function visitorLog(): BelongsTo
    {
        return $this->belongsTo(VisitorLog::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
