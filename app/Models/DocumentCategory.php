<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'sort_order',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function label(): string
    {
        $this->loadMissing('parent');

        return $this->parent
            ? $this->parent->name.' — '.$this->name
            : $this->name;
    }

    /**
     * @return list<array{name: string, description: string, children: list<array{name: string, description: string}>}>
     */
    public static function catalog(): array
    {
        return [
            [
                'name'        => 'Legislative Documents',
                'description' => 'Ordinances, resolutions, and other legislative records.',
                'children'    => [],
            ],
            [
                'name'        => 'Executive and Administrative Documents',
                'description' => 'Executive orders, memoranda, and administrative records.',
                'children'    => [],
            ],
            [
                'name'        => 'Financial and Budget Documents',
                'description' => 'Budgets, vouchers, and financial reports.',
                'children'    => [],
            ],
            [
                'name'        => 'Constituent and Public Service Documents',
                'description' => 'Requests, complaints, and public service records.',
                'children'    => [],
            ],
            [
                'name'        => 'Project and Program Documents',
                'description' => 'Project plans, program files, and related reports.',
                'children'    => [],
            ],
            [
                'name'        => 'Meeting Minutes',
                'description' => 'Minutes of committee meetings and public hearings.',
                'children'    => [
                    [
                        'name'        => 'Committee Meetings',
                        'description' => 'Minutes of committee meetings.',
                    ],
                    [
                        'name'        => 'Public Hearing',
                        'description' => 'Minutes of public hearings.',
                    ],
                ],
            ],
            [
                'name'        => 'Session Minutes',
                'description' => 'Minutes of regular and special sessions.',
                'children'    => [
                    [
                        'name'        => 'Regular Sessions',
                        'description' => 'Minutes of regular sessions.',
                    ],
                    [
                        'name'        => 'Special Sessions',
                        'description' => 'Minutes of special sessions.',
                    ],
                ],
            ],
        ];
    }

    public static function syncCatalog(): void
    {
        $keepIds = [];
        $sort    = 0;

        foreach (static::catalog() as $item) {
            $sort++;
            $parent = static::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'description' => $item['description'],
                    'parent_id'   => null,
                    'sort_order'  => $sort,
                ]
            );
            $keepIds[] = $parent->id;

            $childSort = 0;
            foreach ($item['children'] as $child) {
                $childSort++;
                $row = static::query()->updateOrCreate(
                    ['name' => $child['name']],
                    [
                        'description' => $child['description'],
                        'parent_id'   => $parent->id,
                        'sort_order'  => $childSort,
                    ]
                );
                $keepIds[] = $row->id;
            }
        }

        $legacyMap = [
            'Memoranda'               => 'Executive and Administrative Documents',
            'Resolutions'             => 'Legislative Documents',
            'Reports'                 => 'Executive and Administrative Documents',
            'Administrative Records'  => 'Executive and Administrative Documents',
        ];

        foreach ($legacyMap as $oldName => $newName) {
            $old = static::query()->where('name', $oldName)->first();
            $new = static::query()->where('name', $newName)->whereNull('parent_id')->first();

            if ($old && $new && $old->id !== $new->id) {
                Document::where('category_id', $old->id)->update(['category_id' => $new->id]);
            }
        }

        static::query()
            ->whereNotIn('id', $keepIds)
            ->whereDoesntHave('documents')
            ->whereDoesntHave('children')
            ->delete();

        $validIds = static::query()->pluck('id')->all();
        SystemSetting::query()->get()->each(function (SystemSetting $settings) use ($validIds) {
            $allowed = array_values(array_intersect($settings->public_search_categories ?? [], $validIds));
            if ($allowed !== ($settings->public_search_categories ?? [])) {
                $settings->update(['public_search_categories' => $allowed]);
            }
        });
    }

    /**
     * @return list<int>
     */
    public static function idsForFilter(?int $categoryId): array
    {
        if (! $categoryId) {
            return [];
        }

        $category = static::query()->with('children')->find($categoryId);

        if (! $category) {
            return [$categoryId];
        }

        return array_merge(
            [$category->id],
            $category->children->pluck('id')->map(fn ($id) => (int) $id)->all()
        );
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<int>
     */
    public static function expandIds(array $ids): array
    {
        $expanded = [];

        foreach ($ids as $id) {
            $expanded = array_merge($expanded, static::idsForFilter((int) $id));
        }

        return array_values(array_unique($expanded));
    }

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public static function treeForForms()
    {
        return static::query()
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (self $category) => [
                'id'          => $category->id,
                'name'        => $category->name,
                'description' => $category->description,
                'children'    => $category->children->map(fn (self $child) => [
                    'id'   => $child->id,
                    'name' => $child->name,
                ])->values(),
            ])
            ->values();
    }

    /**
     * @param  array<int, int|string>  $countsById
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public static function treeWithCounts(array $countsById)
    {
        return static::treeForForms()->map(function (array $category) use ($countsById) {
            $children = collect($category['children'])->map(function (array $child) use ($countsById) {
                $child['count'] = (int) ($countsById[$child['id']] ?? 0);

                return $child;
            });

            $own = (int) ($countsById[$category['id']] ?? 0);

            $category['children'] = $children->values();
            $category['count']    = $own + $children->sum('count');

            return $category;
        });
    }
}
