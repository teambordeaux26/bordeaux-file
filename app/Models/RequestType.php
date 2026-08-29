<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RequestType extends Model
{
    protected $fillable = [
        'name',
        'purpose',
        'issues_certificate',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'issues_certificate' => 'boolean',
        'is_active'          => 'boolean',
        'sort_order'         => 'integer',
    ];

    /**
     * @return list<array{name: string, purpose: string, issues_certificate: bool, sort_order: int}>
     */
    public static function defaults(): array
    {
        return [
            [
                'name'                => 'Certificate of Appearance',
                'purpose'             => 'Official appearance at the Office of the Vice Mayor',
                'issues_certificate'  => true,
                'sort_order'          => 1,
            ],
            [
                'name'                => 'Document Copy',
                'purpose'             => 'Request for a document copy',
                'issues_certificate'  => false,
                'sort_order'          => 2,
            ],
            [
                'name'                => 'Meeting Request',
                'purpose'             => 'Meeting with the Office of the Vice Mayor',
                'issues_certificate'  => false,
                'sort_order'          => 3,
            ],
            [
                'name'                => 'Other',
                'purpose'             => 'Other office request',
                'issues_certificate'  => false,
                'sort_order'          => 4,
            ],
        ];
    }

    public static function syncDefaults(): void
    {
        if (static::query()->exists()) {
            return;
        }

        foreach (static::defaults() as $row) {
            static::query()->create([
                'name'               => $row['name'],
                'purpose'            => $row['purpose'],
                'issues_certificate' => $row['issues_certificate'],
                'is_active'          => true,
                'sort_order'         => $row['sort_order'],
            ]);
        }
    }

    public static function nameIsTaken(string $name, ?int $ignoreId = null): bool
    {
        return static::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])
            ->exists();
    }

    public function isLastVisibleActive(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return ! static::query()
            ->where('is_active', true)
            ->where('id', '!=', $this->id)
            ->exists();
    }

    public static function booleanValue(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public static function syncFromSettings(array $rows): void
    {
        $keepIds = [];

        foreach (array_values($rows) as $index => $row) {
            $payload = [
                'name'               => trim((string) $row['name']),
                'purpose'            => trim((string) $row['purpose']),
                'issues_certificate' => static::booleanValue($row['issues_certificate'] ?? false),
                'is_active'          => static::booleanValue($row['is_active'] ?? false),
                'sort_order'         => $index + 1,
            ];

            $type = ! empty($row['id'])
                ? static::query()->find($row['id'])
                : null;

            if ($type) {
                $type->update($payload);
            } else {
                $type = static::query()->create($payload);
            }

            $keepIds[] = $type->id;
        }

        static::query()
            ->whereNotIn('id', $keepIds ?: [0])
            ->get()
            ->each(function (self $type) {
                if ($type->requests()->exists()) {
                    $type->update(['is_active' => false]);

                    return;
                }

                $type->delete();
            });
    }

    /**
     * @return list<array{id: int, name: string, purpose: string, issues_certificate: bool}>
     */
    public static function activeOptions(): array
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'purpose', 'issues_certificate'])
            ->map(fn (self $type) => [
                'id'                 => $type->id,
                'name'               => $type->name,
                'purpose'            => $type->purpose,
                'issues_certificate' => $type->issues_certificate,
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, purpose: string, issues_certificate: bool, is_active: bool}>
     */
    public static function settingsPayload(): array
    {
        return static::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'purpose', 'issues_certificate', 'is_active'])
            ->map(fn (self $type) => [
                'id'                 => $type->id,
                'name'               => $type->name,
                'purpose'            => $type->purpose,
                'issues_certificate' => $type->issues_certificate,
                'is_active'          => $type->is_active,
            ])
            ->all();
    }

    public function requests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class);
    }
}
