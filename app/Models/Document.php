<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
        'owner_remarks',
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
        'access_roles',
        'approved_at',
        'returned_at',
        'archived_at',
        'restored_at',
        'restored_by',
        'submitted_at',
        'released_at',
    ];

    protected $casts = [
        'approved_at'  => 'datetime',
        'returned_at'  => 'datetime',
        'archived_at'  => 'datetime',
        'restored_at'  => 'datetime',
        'expires_at'   => 'datetime',
        'submitted_at' => 'datetime',
        'released_at'  => 'datetime',
        'access_roles' => 'array',
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

    public function restorer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'restored_by');
    }

    public function statusUpdates(): HasMany
    {
        return $this->hasMany(DocumentStatusUpdate::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    /**
     * Keep the current file as an old version and record the uploaded file as the revised version.
     */
    public function keepFileAsRevision(string $newPath, string $originalName, ?int $uploadedBy): void
    {
        $next = (int) $this->versions()->max('version_number') + 1;

        if ($this->file_path) {
            $this->versions()->where('label', 'Revised version')->update(['label' => 'Old version']);

            $alreadyStored = $this->versions()->where('file_path', $this->file_path)->exists();

            if (! $alreadyStored) {
                $this->versions()->create([
                    'file_path'      => $this->file_path,
                    'original_name'  => basename($this->file_path),
                    'label'          => 'Old version',
                    'version_number' => $next,
                    'uploaded_by'    => $this->submitted_by,
                ]);
                $next++;
            }
        }

        $this->versions()->create([
            'file_path'      => $newPath,
            'original_name'  => $originalName,
            'label'          => 'Revised version',
            'version_number' => $next,
            'uploaded_by'    => $uploadedBy,
        ]);
    }

    /**
     * @return list<array{id: int, label: string, name: string, uploaded_by: string, uploaded_at: string, preview_url: string, download_url: string}>
     */
    public function versionPayload(): array
    {
        $versions = $this->relationLoaded('versions')
            ? $this->versions
            : $this->versions()->with('uploader')->get();

        return $versions->map(fn (DocumentVersion $version) => [
            'id'           => $version->id,
            'label'        => $version->label,
            'name'         => $version->displayName(),
            'uploaded_by'  => $version->uploader?->name ?? '—',
            'uploaded_at'  => \App\Support\OfficeTime::exact($version->created_at),
            'preview_url'  => route('documents.versions.file', [
                'document' => $this->id,
                'version'  => $version->id,
                'inline'   => 1,
            ]),
            'download_url' => route('documents.versions.file', [$this, $version]),
        ])->values()->all();
    }

    /**
     * @return list<string>
     */
    public function accessRoleList(): array
    {
        $roles = is_array($this->access_roles) ? $this->access_roles : [];

        return array_values(array_unique(array_filter(
            $roles,
            fn ($role) => is_string($role) && array_key_exists($role, User::documentAccessRoles())
        )));
    }

    public function isRestricted(): bool
    {
        return $this->accessRoleList() !== [];
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

        $roles = $this->accessRoleList();

        if ($roles === []) {
            return true;
        }

        return in_array($user->role, $roles, true);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role === 'admin') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user) {
            $builder->where('submitted_by', $user->id)
                ->orWhere('handled_by', $user->id)
                ->orWhereNull('access_roles')
                ->orWhereJsonLength('access_roles', 0)
                ->orWhereJsonContains('access_roles', $user->role);
        });
    }

    /**
     * @param  list<string>  $roles
     */
    public function syncAccessRoles(array $roles): void
    {
        $allowed = array_keys(User::documentAccessRoles());
        $clean = collect($roles)
            ->map(fn ($role) => (string) $role)
            ->filter(fn ($role) => in_array($role, $allowed, true))
            ->unique()
            ->values()
            ->all();

        sort($clean);

        $this->update([
            'access_roles' => $clean === [] ? null : $clean,
        ]);
    }
}
