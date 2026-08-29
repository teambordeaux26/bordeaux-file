<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_number',
        'requester_name',
        'requester_email',
        'requester_phone',
        'requester_address',
        'request_type',
        'request_type_id',
        'purpose',
        'details',
        'status',
        'response_file_path',
        'response_file_name',
        'certificate_id',
        'processed_by',
        'processed_at',
        'email_sent_at',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'email_sent_at' => 'datetime',
    ];

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(RequestType::class, 'request_type_id');
    }

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    public function issuesCertificate(): bool
    {
        $this->loadMissing('type');

        if ($this->type) {
            return $this->type->issues_certificate;
        }

        return strcasecmp((string) $this->request_type, 'Certificate of Appearance') === 0;
    }

    public function connectedPurpose(): string
    {
        $this->loadMissing('type');

        $purpose = trim((string) ($this->purpose ?: $this->type?->purpose ?: $this->details ?: $this->request_type));

        return $purpose !== '' ? $purpose : 'Office request';
    }
}
