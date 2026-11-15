<?php

namespace Modules\Auth\Filament\Resources;

use App\Filament\AbstractResource;

/**
 * Base des ressources du module Auth : groupe de navigation du module.
 */
abstract class AbstractAuthResource extends AbstractResource
{
    public static function getNavigationGroup(): ?string
    {
        return __('auth::navigation.group');
    }
}
