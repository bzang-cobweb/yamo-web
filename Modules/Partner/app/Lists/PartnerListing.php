<?php

namespace Modules\Partner\Lists;

use App\Support\MediaUrl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;

/**
 * La page « Partenaires » de l'app (décisions 2026-11-23-partenaires-liste-detail et
 * 2026-11-23-partenaires-recherche) : tous les partenaires visibles, dans l'ordre du back-office, page après page
 * comme toutes les listes ; cherchables par nom ou description. Pour chacun : son logo, son nom, son slogan (sinon sa description) ;
 * la carte ouvre sa page. Ne compte pas de vue : les vues sont celles de l'accueil.
 */
class PartnerListing extends Listing
{
    public static function name(): string
    {
        return 'partner.all';
    }

    /** Sans compte, comme le détail d'un partenaire et la vitrine des logements. */
    public function canBeViewedBy(?Authenticatable $user): bool
    {
        return true;
    }

    public function title(): string
    {
        return __('partner::partner.plural');
    }

    /**
     * @return Builder<Partner>
     */
    public function query(): Builder
    {
        return Partner::query()->visible()->with('media')->orderBy('position')->orderBy('id');
    }

    public function searchPlaceholder(): string
    {
        // « Rechercher… », le même partout (décision 2026-11-22-recherche-placeholder-unique).
        return __('form::mobile.form.search');
    }

    protected function search(Builder $query, string $term): void
    {
        PartnerService::search($query, $term);
    }

    public function item(Model $record): ListItem
    {
        /** @var Partner $record */
        $name = (string) $record->getTranslation('name', app()->getLocale());

        // Sous le nom, le slogan, sinon la description (décision 2026-11-24-mes-partenaires-slogan).
        return ListItem::make($record->getKey(), $name, $record->cardLine(app()->getLocale()))
            ->image(MediaUrl::onRequestHost($record->logoUrl('list')))
            ->link('/partners/'.$record->getKey());
    }
}
