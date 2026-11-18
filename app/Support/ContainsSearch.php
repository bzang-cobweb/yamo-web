<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * La recherche « contient » : sans casse, `%` et `_` cherchés tels quels, de la
 * même façon sur MariaDB et sur SQLite (tests). Seul domicile de cette règle :
 * listes de l'app (`Listing::orWhereContains()`) et suggestions de rue du
 * back-office (`AddressService::suggest()`).
 */
final class ContainsSearch
{
    /** Caractère d'échappement du `LIKE` : `\` n'est pas compris pareil par SQLite. */
    private const ESCAPE = '!';

    /**
     * Ajoute à `$query`, en « ou », une condition par colonne : la colonne
     * contient `$term`. À grouper dans un `where()` pour ne pas défaire les
     * autres conditions.
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
     * Le motif `LIKE` de `$term` : en minuscules, jokers échappés, entre `%`.
     */
    public static function pattern(string $term): string
    {
        return '%'.preg_replace('/['.self::ESCAPE.'%_]/', self::ESCAPE.'$0', Str::lower($term)).'%';
    }
}
