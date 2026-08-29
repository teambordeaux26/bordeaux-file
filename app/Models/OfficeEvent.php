<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeEvent extends Model
{
    protected $fillable = [
        'title',
        'type',
        'starts_at',
        'ends_at',
        'description',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'meeting'  => 'Meeting',
            'deadline' => 'Deadline',
            default    => 'Event',
        };
    }
}
