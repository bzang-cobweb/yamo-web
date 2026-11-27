<?php

namespace Modules\Partner\Lists;

use App\Support\MediaUrl;
use Bza\Form\Support\Listing;
use Bza\Form\Support\ListItem;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Partner\Forms\PartnerForm;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;

/**
 * La page « Partenaires » de l'app (décisions 2026-11-23-partenaires-liste-detail et
 * 2026-11-23-partenaires-recherche) : tous les partenaires visibles, dans l'ordre du back-office, page après page
 * comme toutes les listes ; cherchables par nom ou description. Pour chacun : son logo, son nom, son slogan (sinon sa description) ;
 * la carte ouvre sa page. Chaque partenaire d'une page servie compte une vue, sauf pour ses propres comptes (décision
 * 2026-11-25-compteurs-partenaires).
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

    /**
     * « Devenir partenaire », dans la barre fixe en bas, pour tous : connecté, le formulaire d'ajout ; visiteur, la
     * connexion (décision 2026-11-24-devenir-partenaire).
     *
     * @return array{label: string, form: string|null, link: string|null}|null
     */
    public function footer(): ?array
    {
        $label = __('partner::partner.member.become');
        if ($this->user === null) {
            return ['label' => $label, 'form' => null, 'link' => '/sign-in'];
        }
        if (! $this->user->can('create', Partner::class)) {
            return null;
        }

        return ['label' => $label, 'form' => PartnerForm::name(), 'link' => null];
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
        // Les vedettes, épinglées en tête (`pinned()`), sont exclues ici (décision 2026-11-25-vedettes-d-abord).
        return Partner::query()->visible()->where(fn (Builder $notFeatured) => $notFeatured->whereNull('featured_until')->orWhereDate('featured_until', '<', today()))
            ->with('media')->orderBy('position')->orderBy('id');
    }

    /**
     * Les partenaires en vedette, en tête de la première page : la date la plus lointaine d'abord, puis l'ordre du
     * back-office (décision 2026-11-25-vedettes-d-abord).
     *
     * @return Builder<Partner>
     */
    protected function pinned(): Builder
    {
        return Partner::query()->visible()->featured()->with('media')->orderByDesc('featured_until')->orderBy('position')->orderBy('id');
    }

    /** Toutes les vedettes, sans limite. */
    public function pinnedLimit(): int
    {
        return PHP_INT_MAX;
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

    protected function served(Collection $records): void
    {
        app(PartnerService::class)->countViews($records, $this->user);
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
