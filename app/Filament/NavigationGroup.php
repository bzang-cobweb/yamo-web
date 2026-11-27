<?php

namespace App\Filament;

/**
 * Groupes du menu du back-office partagés entre l'application et les modules.
 */
enum NavigationGroup: string
{
    case Settings = 'settings';

    /** Le schéma de la base : migrations et tables. */
    case DataModels = 'data_models';

    public function label(): string
    {
        return match ($this) {
            self::Settings => __('settings.navigation_group'),
            self::DataModels => __('navigation.data_models'),
        };
    }
}
