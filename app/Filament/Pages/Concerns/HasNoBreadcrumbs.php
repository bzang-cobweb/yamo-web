<?php

namespace App\Filament\Pages\Concerns;

/**
 * Page sans fil d'Ariane : une liste, point d'entrée de sa ressource, n'en a pas
 * besoin.
 */
trait HasNoBreadcrumbs
{
    /**
     * @return array<string, string>
     */
    public function getBreadcrumbs(): array
    {
        return [];
    }
}
