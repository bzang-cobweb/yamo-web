<?php

namespace Modules\Partner\Lists;

use App\Models\Setting;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Listing;
use Modules\Form\Support\ListItem;
use Modules\Form\Support\Submission;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Forms\PartnerForm;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;

/**
 * « Mes partenaires » (décision 2026-11-24-partenaires-par-les-membres) : ceux qu'administre le compte, dans le
 * pays courant ; pour chacun, son logo, son nom, son statut (« En validation », « Refusé », « Archivé »,
 * et, publié, « Affiché » ou « Pas affiché ») sur le logo ; la carte ouvre sa page ; le menu ⋮ : « Modifier », et
 * « Retirer » pour un administrateur (`is_admin`) (décision 2026-11-24-mes-partenaires-cartes). En tête,
 * « 3 partenaires » ; en bas, « Proposer un partenaire ».
 */
class MyPartnerListing extends Listing
{
    private ?bool $paymentRequired = null;

    public static function name(): string
    {
        return 'partner.mine';
    }

    public function title(): string
    {
        return __('partner::partner.member.mine');
    }

    /**
     * @return Builder<Partner>
     */
    public function query(): Builder
    {
        return Partner::query()->administeredBy($this->user?->getAuthIdentifier())
            ->with(['media', 'users' => fn ($users) => $users->whereKey($this->user?->getAuthIdentifier())])
            ->latest('id');
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

    /** « 3 partenaires », en tête des cartes (décision 2026-11-24-mes-partenaires-cartes). */
    public function headingFor(array $filters, ?string $search = null): array
    {
        $count = $this->countFor($search, $filters);

        return ['title' => trans_choice('partner::partner.member.count', $count, ['count' => $count]), 'subtitle' => null];
    }

    /** Modifier et Retirer dans un menu ⋮ ; toucher la carte ouvre la page du partenaire. */
    public function actionsMenu(): bool
    {
        return true;
    }

    public function emptyText(): string
    {
        return __('partner::partner.member.empty');
    }

    /** « Proposer un partenaire », dans la barre fixe en bas, comme « Ajouter un logement ». */
    public function footer(): ?array
    {
        if ($this->user === null) {
            return null;
        }
        if (! $this->user->can('create', Partner::class)) {
            return null;
        }

        return ['label' => __('partner::partner.member.add'), 'form' => PartnerForm::name(), 'link' => null];
    }

    public function item(Model $record): ListItem
    {
        /** @var Partner $record */
        $this->paymentRequired ??= Setting::partnerPaymentRequired();
        // Le statut (décision 2026-11-24-partenaires-statuts) ; publié, s'il est affiché maintenant ou pas (campagne).
        [$status, $tone] = match (true) {
            $record->status !== PartnerStatusEnum::PUBLISHED => [$record->status->label(), $record->status->tone()],
            $record->isShown($this->paymentRequired) => [__('partner::partner.member.status.shown'), 'success'],
            default => [__('partner::partner.member.status.hidden'), 'neutral'],
        };
        $locale = app()->getLocale();
        $description = (string) $record->getTranslation('description', $locale);

        // Le statut flottant sur le logo, en haut à gauche ; la description sur une ligne ; la carte ouvre la page
        // du partenaire, « Modifier » et « Retirer » sont dans le menu (décision 2026-11-24-mes-partenaires-cartes).
        $item = ListItem::make($record->getKey(), (string) $record->getTranslation('name', $locale), $description === '' ? null : $description)
            ->image(MediaUrl::onRequestHost($record->logoUrl()))
            ->tag($status, $tone, onImage: true)
            ->subtitleLines(1)
            ->link('/partners/'.$record->getKey())
            ->edit(PartnerForm::name());

        // « Retirer » : pour un administrateur seulement.
        if (! (bool) $record->users->firstWhere('id', $this->user?->getAuthIdentifier())?->pivot?->is_admin) {
            return $item;
        }

        return $item->delete(
            __('partner::partner.member.remove'),
            __('partner::partner.member.remove_confirm'),
            Submission::toRoute('api.v1.partners.destroy', SuccessAction::BACK, __('partner::partner.member.removed'), destructive: true, parameters: ['partner' => $record->getKey()]),
        );
    }
}
