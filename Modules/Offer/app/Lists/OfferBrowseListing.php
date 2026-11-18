<?php

namespace Modules\Offer\Lists;

use App\Support\Locales;
use App\Support\MediaUrl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Enums\OfferStatusEnum;
use Modules\Offer\Models\Offer;

/**
 * Les offres publiées du pays courant, la vitrine de l'accueil (décision
 * 2026-11-18-accueil-offres-du-pays) : ouverte sans connexion, les plus récentes
 * d'abord ; titre construit par le domaine (« Studio meublé - Douala ») et prix ;
 * recherche dans le titre saisi et la ville. Ni édition ni retrait.
 * Sans domaine choisi, elle n'existe pas.
 */
class OfferBrowseListing extends Listing
{
    public static function name(): string
    {
        return 'offer.browse';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    /**
     * Ouverte à tous, visiteurs compris.
     */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }

    public function title(): string
    {
        return app(DomainRegistry::class)->require()->pluralLabel();
    }

    public function emptyText(): string
    {
        return __('offer::offer.browse.empty');
    }

    public function searchPlaceholder(): string
    {
        return __('offer::offer.browse.search');
    }

    /**
     * Publiées, dans le pays courant, les plus récentes d'abord.
     *
     * @return Builder<Offer>
     */
    public function query(): Builder
    {
        return Offer::query()
            ->with(['address', 'media'])
            ->where('country_id', CurrentCountry::require()->getKey())
            ->where('status', OfferStatusEnum::PUBLISHED)
            ->orderByDesc('id');
    }

    protected function search(Builder $query, string $term): void
    {
        $this->orWhereContains($query, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $term);
        // La ville, affichée dans le titre de la liste.
        $query->orWhereHas('address', fn (Builder $address) => $address->where(fn (Builder $columns) => $this->orWhereContains($columns, ['city'], $term)));
    }

    public function item(Model $record): ListItem
    {
        /** @var Offer $record */
        $domain = app(DomainRegistry::class)->require();
        // « 60 000 FCFA / nuit » : le prix et son unité, selon le domaine.
        $price = $domain->formattedPrice($record, app()->getLocale());

        // Le titre construit par le domaine (« Studio meublé - Douala »), sinon le titre saisi.
        return ListItem::make(
            $record->getKey(),
            $domain->itemTitle($record) ?? (string) $record->getTranslation('title', app()->getLocale()),
            $price,
        )->image(MediaUrl::onRequestHost($record->photoUrl()));
    }
}
