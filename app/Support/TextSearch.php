<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class TextSearch
{
    public static function contains(Builder $query, string $column, string $term, bool $or = false): Builder
    {
        $method = $or ? 'orWhereRaw' : 'whereRaw';

        return $query->{$method}('LOWER('.$column.') LIKE ?', ['%'.self::needle($term).'%']);
    }

    /**
     * Match an address that begins with the term, including "Brgy. B..." barangay labels.
     */
    public static function addressStartsWith(Builder $query, string $column, string $term, bool $or = false): Builder
    {
        $needle = self::needle($term);
        $method = $or ? 'orWhere' : 'where';

        return $query->{$method}(function (Builder $builder) use ($column, $needle) {
            $builder->whereRaw('LOWER('.$column.') LIKE ?', ['brgy. '.$needle.'%'])
                ->orWhere(function (Builder $custom) use ($column, $needle) {
                    $custom->whereRaw('LOWER('.$column.') LIKE ?', [$needle.'%'])
                        ->whereRaw('LOWER('.$column.') NOT LIKE ?', ['brgy. %']);
                });

            if (str_starts_with($needle, 'brgy')) {
                $builder->orWhereRaw('LOWER('.$column.') LIKE ?', [$needle.'%']);
            }
        });
    }

    private static function needle(string $term): string
    {
        return mb_strtolower(trim($term));
    }
}
