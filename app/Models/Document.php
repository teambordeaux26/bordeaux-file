<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'handled_by',
        'approved_at',
        'returned_at',
        'archived_at',
        'submitted_at',
        'released_at',
    ];

    protected $casts = [
        'approved_at'  => 'datetime',
        'returned_at'  => 'datetime',
        'archived_at'  => 'datetime',
        'expires_at'   => 'datetime',
        'submitted_at' => 'datetime',
        'released_at'  => 'datetime',
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

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function allowedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'document_user')->withTimestamps();
    }

    public function statusUpdates(): HasMany
    {
        return $this->hasMany(DocumentStatusUpdate::class);
    }

    public function isRestricted(): bool
    {
        if ($this->relationLoaded('allowedUsers')) {
            return $this->allowedUsers->isNotEmpty();
        }

        return $this->allowedUsers()->exists();
    }

    public function canBeAccessedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->role === 'admin') {
            return true;
        }

        if ((int) $this->submitted_by === (int) $user->id) {
            return true;
        }

        if ($this->handled_by && (int) $this->handled_by === (int) $user->id) {
            return true;
        }

        if ($this->relationLoaded('allowedUsers')) {
            if ($this->allowedUsers->isEmpty()) {
                return true;
            }

            return $this->allowedUsers->contains('id', $user->id);
        }

        if (! $this->allowedUsers()->exists()) {
            return true;
        }

        return $this->allowedUsers()->where('users.id', $user->id)->exists();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role === 'admin') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user) {
            $builder->where('submitted_by', $user->id)
                ->orWhere('handled_by', $user->id)
                ->orWhereDoesntHave('allowedUsers')
                ->orWhereHas('allowedUsers', fn (Builder $rel) => $rel->where('users.id', $user->id));
        });
    }

    /**
     * @param  list<int|string>  $userIds
     */
    public function syncAllowedUsers(array $userIds): void
    {
        $ids = collect($userIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            $this->allowedUsers()->sync([]);

            return;
        }

        $ids->push((int) $this->submitted_by);

        if ($this->handled_by) {
            $ids->push((int) $this->handled_by);
        }

        $valid = User::query()
            ->whereIn('id', $ids->unique()->all())
            ->whereIn('role', ['admin', 'employee'])
            ->where('status', 'active')
            ->pluck('id')
            ->all();

        $this->allowedUsers()->sync($valid);
    }
}
