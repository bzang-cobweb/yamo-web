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
use Modules\Partner\Forms\PartnerForm;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerService;

/**
 * « Mes partenaires » (décision 2026-11-24-partenaires-par-les-membres) : ceux qu'administre le compte, dans le
 * pays courant ; pour chacun, son logo, son nom, son statut (« En validation », « En attente de paiement », « Refusé »,
 * et, publié, « Affiché » ou « Pas affiché ») sur le logo ; la carte ouvre sa page ; le menu ⋮ : « Modifier », et
 * « Retirer » pour un administrateur (`is_admin`) (décision 2026-11-24-mes-partenaires-cartes). En tête,
 * « 3 partenaires » ; en bas, « Ajouter un partenaire ».
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

    /**
     * « Devenir partenaire », dans la barre fixe en bas : le formulaire d'ajout (décision
     * 2026-11-24-devenir-partenaire).
     */
    public function footer(): ?array
    {
        if ($this->user === null) {
            return null;
        }
        if (! $this->user->can('create', Partner::class)) {
            return null;
        }

        return ['label' => __('partner::partner.member.become'), 'form' => PartnerForm::name(), 'link' => null];
    }

    public function item(Model $record): ListItem
    {
        /** @var Partner $record */
        $this->paymentRequired ??= Setting::partnerPaymentRequired();
        // Le statut (décision 2026-11-24-partenaires-statuts) ; publié, s'il est affiché maintenant ou pas (campagne).
        ['label' => $status, 'tone' => $tone] = $record->statusTag($this->paymentRequired);
        $locale = app()->getLocale();

        // Comme la page « Partenaires » : le logo à gauche, le statut au-dessus du nom, puis une ligne (slogan, sinon
        // description) ; la carte ouvre la page du partenaire, « Modifier » et « Retirer » sont dans le menu (décisions
        // 2026-11-24-mes-partenaires-cartes et 2026-11-24-mes-partenaires-comme-partenaires).
        $item = ListItem::make($record->getKey(), (string) $record->getTranslation('name', $locale), $record->cardLine($locale))
            ->image(MediaUrl::onRequestHost($record->logoUrl('list')))
            ->imageSide()
            ->tag($status, $tone)
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
