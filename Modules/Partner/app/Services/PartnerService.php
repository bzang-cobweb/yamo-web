<?php

namespace Modules\Partner\Services;

use App\Models\Setting;
use App\Support\ContainsSearch;
use App\Support\Locales;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Modules\Partner\Models\Partner;

/**
 * Les partenaires de l'accueil (décision 2026-11-23-module-partenaires) : ceux qui sont visibles, tirés au
 * hasard, et leurs compteurs de vues et de clics.
 */
class PartnerService
{
    /**
     * Des partenaires visibles tirés au hasard, autant que réglé dans « Application » (décision
     * 2026-11-23-accueil-nombres-reglables), puis rangés dans l'ordre du back-office (décision
     * 2026-11-23-partenaires-triables) ; chacun compte une vue.
     *
     * @return Collection<int, Partner>
     */
    public function shown(): Collection
    {
        $partners = Partner::query()->visible()->with('media')->inRandomOrder()->limit(Setting::homePartners())->get()
            ->sortBy([['position', 'asc'], ['id', 'asc']])->values();

        if ($partners->isNotEmpty()) {
            Partner::query()->whereKey($partners->modelKeys())->increment('views_count');
        }

        return $partners;
    }

    /**
     * Le nom ou la description, dans une de leurs langues, contient `$term` (la règle commune `ContainsSearch`) :
     * la recherche de la page « Partenaires » et du back-office (décision 2026-11-23-partenaires-recherche).
     *
     * @param  Builder<Partner>  $query
     */
    public static function search(Builder $query, string $term): void
    {
        ContainsSearch::orWhere($query, array_merge(...array_map(fn (string $locale): array => ["name->{$locale}", "description->{$locale}"], Locales::values())), $term);
    }

    /**
     * Un partenaire, s'il est visible (décision 2026-11-23-partenaires-liste-detail) ; sinon `null`.
     */
    public function find(int $id): ?Partner
    {
        return Partner::query()->visible()->with('media')->find($id);
    }

    /**
     * Un toucher sur un partenaire visible : un clic de plus. Faux s'il n'est pas visible.
     */
    public function click(Partner $partner): bool
    {
        if (! Partner::query()->visible()->whereKey($partner->getKey())->exists()) {
            return false;
        }

        $partner->increment('clicks_count');

        return true;
    }
}
