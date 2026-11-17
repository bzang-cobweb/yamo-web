<?php

namespace App\Filament\Pages\Concerns;

use Illuminate\Contracts\Support\Htmlable;

/**
 * Fil d'Ariane d'une page de fiche (édition, consultation) : la liste de la
 * ressource, puis le titre de la fiche. Exemple : `Utilisateurs > Jeanne Dupont`.
 */
trait HasRecordTitleBreadcrumbs
{
    /**
     * @return array<string|int, string|Htmlable>
     */
    public function getBreadcrumbs(): array
    {
        return [
            ...$this->getResourceBreadcrumbs(),
            $this->getRecordTitle(),
        ];
    }
}
