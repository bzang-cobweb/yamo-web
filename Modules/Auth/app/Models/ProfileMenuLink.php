<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Auth\Enums\ProfileMenuKey;

/**
 * Un lien du menu du profil : l'écran qu'il ouvre (`key`), sa `position` et son
 * activation, réglées dans le back-office. Les lignes sont créées par migration,
 * une par écran (décision 2026-11-17-tri-regions-menu-profil).
 *
 * @property ProfileMenuKey $key
 * @property int $position
 * @property bool $active
 */
#[Fillable(['key', 'position', 'active'])]
class ProfileMenuLink extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'key' => ProfileMenuKey::class,
            'position' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
