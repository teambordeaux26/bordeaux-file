<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_number',
        'title',
        'description',
        'category_id',
        'status',
        'priority',
        'reference_no',
        'file_path',
        'retention_days',
        'expires_at',
        'submitted_by',
        'reviewed_by',
        'approved_at',
        'returned_at',
        'archived_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'returned_at' => 'datetime',
        'archived_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function statusUpdates(): HasMany
    {
        return $this->hasMany(DocumentStatusUpdate::class);
    }
}
