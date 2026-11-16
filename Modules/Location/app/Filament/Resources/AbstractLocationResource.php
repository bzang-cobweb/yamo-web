<?php

namespace Modules\Location\Filament\Resources;

use App\Filament\AbstractResource;

/**
 * Base des ressources du module Location : groupe de navigation du module.
 */
abstract class AbstractLocationResource extends AbstractResource
{
    public static function getNavigationGroup(): ?string
    {
        return __('location::navigation.group');
    }
}
