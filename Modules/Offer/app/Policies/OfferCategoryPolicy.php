<?php

namespace Modules\Offer\Policies;

use Filament\Facades\Filament;
use Modules\Auth\Enums\PermissionEnum;
use Modules\Auth\Models\User;
use Modules\Auth\Policies\Concerns\HasBeforeAbility;
use Modules\Offer\Models\OfferCategory;

/**
 * Catégories d'offres : gérées dans le back-office seul, sous `offers.*`.
 */
class OfferCategoryPolicy
{
    use HasBeforeAbility;

    public function viewAny(User $user): bool
    {
        return Filament::isServing() ? $user->hasPermissionTo(PermissionEnum::VIEW_OFFERS) : true;
    }

    public function view(User $user, OfferCategory $category): bool
    {
        return Filament::isServing() ? $user->hasPermissionTo(PermissionEnum::VIEW_OFFERS) : true;
    }

    public function create(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::UPDATE_OFFERS);
    }

    public function update(User $user, OfferCategory $category): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::UPDATE_OFFERS);
    }

    public function delete(User $user, OfferCategory $category): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::UPDATE_OFFERS);
    }

    public function deleteAny(User $user): bool
    {
        return Filament::isServing() && $user->hasPermissionTo(PermissionEnum::UPDATE_OFFERS);
    }
}
