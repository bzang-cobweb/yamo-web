<?php

namespace Modules\Offer\Lists;

use App\Support\Locales;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Form\Support\Submission;
use Modules\Location\Support\CurrentCountry;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Forms\OfferForm;
use Modules\Offer\Models\Offer;

/**
 * Les offres du compte (« Mes logements »), ouvertes depuis le menu du profil :
 * titre, statut et prix ; recherche dans le titre ; création, édition et retrait
 * (décision 2026-11-18-offres-et-domaines). Sans domaine choisi, la liste
 * n'existe pas.
 */
class OfferListing extends Listing
{
    public static function name(): string
    {
        return 'offer.mine';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function title(): string
    {
        return app(DomainRegistry::class)->require()->mineLabel();
    }

    public function emptyText(): string
    {
        return __('offer::offer.list.empty');
    }

    public function create(): ?array
    {
        $domain = app(DomainRegistry::class)->require();

        // Pas de création proposée à qui ne peut pas créer, ni au-delà d'une offre quand le domaine l'impose.
        if ($this->user === null || ! $this->user->can('create', Offer::class)
            || ($domain->onePerAccount() && Offer::query()->where('user_id', $this->user->getAuthIdentifier())->where('country_id', CurrentCountry::require()->getKey())->exists())) {
            return null;
        }

        return ['label' => $domain->addLabel(), 'form' => OfferForm::name()];
    }

    public function searchPlaceholder(): string
    {
        return __('offer::offer.list.search');
    }

    /**
     * @return Builder<Offer>
     */
    public function query(): Builder
    {
        // Les siennes, dans le pays courant (décision 2026-11-18-espace-pays).
        return Offer::query()
            ->with('media')
            ->where('user_id', $this->user?->getAuthIdentifier())
            ->where('country_id', CurrentCountry::require()->getKey())
            ->orderBy('id');
    }

    protected function search(Builder $query, string $term): void
    {
        // Le titre, toutes langues confondues.
        $this->orWhereContains($query, array_map(fn (string $locale): string => "title->{$locale}", Locales::values()), $term);
    }

    public function item(Model $record): ListItem
    {
        /** @var Offer $record */
        $price = $record->price === null ? null : Number::currency((float) $record->price, (string) $record->currency, app()->getLocale());

        $locale = $this->user instanceof User ? $this->user->preferredLocale() : app()->getLocale();

        return ListItem::make($record->getKey(), $record->getTranslation('title', $locale), implode(' · ', array_filter([$record->status->label(), $price])))
            ->image(MediaUrl::onRequestHost($record->photoUrl()))
            ->edit(OfferForm::name())
            ->delete(
                __('offer::offer.list.remove'),
                __('offer::offer.list.confirm'),
                Submission::toRoute(
                    'api.v1.offers.destroy',
                    SuccessAction::BACK,
                    __('offer::offer.list.removed'),
                    destructive: true,
                    parameters: ['offer' => $record->getKey()],
                ),
            );
    }
}
