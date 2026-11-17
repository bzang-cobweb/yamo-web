<?php

namespace Modules\Location\Filament\Resources;

use App\Filament\AbstractResource;
use App\Filament\NavigationGroup;

/**
 * Base des ressources du module Location : rangées dans le groupe « Paramètres ».
 */
abstract class AbstractLocationResource extends AbstractResource
{
    public static function getNavigationGroup(): ?string
    {
        return NavigationGroup::Settings->label();
    }
}
