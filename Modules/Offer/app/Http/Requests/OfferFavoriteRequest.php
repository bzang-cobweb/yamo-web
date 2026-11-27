<?php

namespace Modules\Offer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Offer\Models\Wishlist;

/**
 * Ranger une offre (décision 2026-11-27-listes-de-favoris) : dans l'une de ses listes nommées (`wishlist`), ou dans une
 * nouvelle liste (`name`) ; sans l'un ni l'autre, dans sa première liste (une app d'avant les listes).
 */
class OfferFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'wishlist' => [
                'nullable',
                'integer',
                // Seulement l'une de ses listes nommées.
                Rule::exists('wishlists', 'id')->where('user_id', $this->user()?->getAuthIdentifier())->where('kind', 'custom'),
            ],
            'name' => ['nullable', 'string', 'max:'.Wishlist::NAME_MAX_LENGTH, 'prohibits:wishlist'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['name' => (string) __('offer::offer.wishlists.name'), 'wishlist' => (string) __('offer::offer.wishlists.list')];
    }
}
