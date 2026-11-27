<?php

namespace Modules\Offer\Forms;

use Bza\Form\Enums\SuccessAction;
use Bza\Form\Support\Field;
use Bza\Form\Support\Form;
use Bza\Form\Support\Submission;
use Illuminate\Support\Facades\Gate;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Wishlist;

/**
 * Renommer une liste de favoris (décision 2026-11-27-listes-de-favoris), ouvert par « Renommer » du menu ⋮ de la liste :
 * `?record=` est l'identifiant de la liste ; son nom déjà rempli. Envoyé à `PATCH /api/v1/wishlists/{wishlist}`.
 */
class WishlistForm extends Form
{
    private ?Wishlist $wishlist = null;

    public static function name(): string
    {
        return 'offer.wishlist';
    }

    public static function isAvailable(): bool
    {
        return app(DomainRegistry::class)->active() !== null;
    }

    public function forRecord(string $key): static
    {
        $wishlist = Wishlist::query()->whereKey($key)->first() ?? abort(404);
        Gate::forUser($this->user)->authorize('update', $wishlist);
        $this->wishlist = $wishlist;

        return $this;
    }

    public function fields(): array
    {
        return [
            Field::text('name')
                ->label(__('offer::offer.wishlists.name'))
                ->default($this->wishlist?->name)
                ->required()
                ->maxLength(Wishlist::NAME_MAX_LENGTH),
        ];
    }

    public function title(): ?string
    {
        return null;
    }

    /** « Renommer la liste », dans la barre du haut. */
    public function navigationTitle(): ?string
    {
        return __('offer::offer.wishlists.rename_title');
    }

    public function submitLabel(): string
    {
        return __('offer::offer.wishlists.save');
    }

    /** Sans liste (`?record=` absent), aucun envoi. */
    public function submission(): ?Submission
    {
        return $this->wishlist === null ? null : Submission::toRoute(
            'api.v1.wishlists.update',
            SuccessAction::BACK,
            __('offer::offer.wishlists.renamed'),
            parameters: ['wishlist' => $this->wishlist->getKey()],
        );
    }
}
