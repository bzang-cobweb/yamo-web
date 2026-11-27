<?php

namespace Bza\Form\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * The « contains » search: case-insensitive, `%` and `_` matched literally, the
 * same way on MariaDB and on SQLite (tests). The only home of this rule:
 * the app's lists (`Listing::orWhereContains()`) and the back office's street
 * suggestions (`AddressService::suggest()`).
 */
final class ContainsSearch
{
    /** Escape character of the `LIKE`: `\` is not understood the same way by SQLite. */
    private const ESCAPE = '!';

    /**
     * Adds to `$query`, with OR, one condition per column: the column
     * contains `$term`. To be grouped in a `where()` so as not to undo the
     * other conditions.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $columns
     */
    public static function orWhere(Builder $query, array $columns, string $term): void
    {
        $pattern = self::pattern($term);
        $grammar = $query->getQuery()->getGrammar();

        foreach ($columns as $column) {
            $query->orWhereRaw('LOWER('.$grammar->wrap($column).") LIKE ? ESCAPE '".self::ESCAPE."'", [$pattern]);
        }
    }

    /**
     * The `LIKE` pattern of `$term`: lowercased, wildcards escaped, between `%`.
     */
    public static function pattern(string $term): string
    {
        return '%'.preg_replace('/['.self::ESCAPE.'%_]/', self::ESCAPE.'$0', Str::lower($term)).'%';
    }
}
