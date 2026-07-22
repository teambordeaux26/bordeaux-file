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

    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }
}
