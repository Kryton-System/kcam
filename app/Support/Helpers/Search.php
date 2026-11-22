<?php

declare(strict_types=1);

namespace App\Support\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class Search
{
    private const ESCAPE = '!';

    /**
     * Escape LIKE wildcards so a user's "%" or "_" is matched literally.
     */
    public static function escapeLike(string $term): string
    {
        return str_replace(
            [self::ESCAPE, '%', '_'],
            [self::ESCAPE.self::ESCAPE, self::ESCAPE.'%', self::ESCAPE.'_'],
            $term,
        );
    }

    /**
     * Add a "contains" search across the given columns (developer supplied, never user input).
     *
     * ESCAPE '!' is used because it behaves the same on MySQL and SQLite, unlike backslash.
     *
     * @param  Builder<Model>  $query
     * @param  list<string>  $columns
     * @return Builder<Model>
     */
    public static function contains(Builder $query, array $columns, string $term): Builder
    {
        $pattern = '%'.self::escapeLike($term).'%';
        $grammar = $query->getQuery()->getGrammar();

        return $query->where(function (Builder $group) use ($columns, $pattern, $grammar): void {
            foreach ($columns as $column) {
                $group->orWhereRaw($grammar->wrap($column).' LIKE ? ESCAPE \''.self::ESCAPE.'\'', [$pattern]);
            }
        });
    }
}
