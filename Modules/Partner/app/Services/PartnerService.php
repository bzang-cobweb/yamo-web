<?php

namespace Modules\Partner\Services;

use App\Models\Setting;
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
     * 2026-11-23-accueil-nombres-reglables) ; chacun compte une vue.
     *
     * @return Collection<int, Partner>
     */
    public function shown(): Collection
    {
        $partners = Partner::query()->visible()->with('media')->inRandomOrder()->limit(Setting::homePartners())->get();

        if ($partners->isNotEmpty()) {
            Partner::query()->whereKey($partners->modelKeys())->increment('views_count');
        }

        return $partners;
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
