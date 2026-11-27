<?php

namespace Modules\Offer\Http\Requests;

use Bza\Form\Http\Requests\FormBackedRequest;
use Modules\Offer\Forms\WishlistForm;

/**
 * Le nouveau nom d'une liste de favoris, validé par le formulaire `offer.wishlist` (décision
 * 2026-11-27-listes-de-favoris) ; la garde `can:update` de la route a déjà vérifié la liste.
 */
class WishlistRenameRequest extends FormBackedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function form(): WishlistForm
    {
        return new WishlistForm($this->user());
    }
}
