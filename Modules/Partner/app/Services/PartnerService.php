<?php

namespace Modules\Partner\Services;

use App\Models\Setting;
use App\Support\ContainsSearch;
use App\Support\Locales;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;
use Modules\Auth\Support\PhoneNumbers;
use Modules\Location\Support\CurrentCountry;
use Modules\Partner\Enums\PartnerStatusEnum;
use Modules\Partner\Events\PartnerSubmitted;
use Modules\Partner\Models\Partner;

/**
 * Les partenaires de l'accueil (décision 2026-11-23-module-partenaires) : ceux qui sont visibles, tirés au
 * hasard, et leurs compteurs de vues et de clics.
 */
class PartnerService
{
    /**
     * Des partenaires visibles tirés au hasard, autant que réglé dans « Application » (décision
     * 2026-11-23-accueil-nombres-reglables), puis rangés dans l'ordre du back-office (décision
     * 2026-11-23-partenaires-triables) ; chacun compte une vue, sauf pour ses propres comptes (décision
     * 2026-11-25-compteurs-partenaires).
     *
     * @return Collection<int, Partner>
     */
    public function shown(?Authenticatable $viewer = null): Collection
    {
        $partners = Partner::query()->visible()->with('media')->inRandomOrder()->limit(Setting::homePartners())->get()
            ->sortBy([['position', 'asc'], ['id', 'asc']])->values();
        $this->countViews($partners, $viewer);

        return $partners;
    }

    /**
     * Une vue de plus pour chaque partenaire affiché : dans le carrousel de l'accueil, dans la page « Partenaires »
     * (décision 2026-11-25-compteurs-partenaires). Pas pour ceux auxquels `$viewer` est rattaché (`partner_user`).
     *
     * @param  iterable<Model>  $partners
     */
    public function countViews(iterable $partners, ?Authenticatable $viewer): void
    {
        $ids = [];
        foreach ($partners as $partner) {
            $ids[] = (int) $partner->getKey();
        }
        $ids = array_values(array_diff($ids, $this->attachedTo($viewer)));
        if ($ids === []) {
            return;
        }

        Partner::query()->whereKey($ids)->increment('views_count');
    }

    /**
     * Les partenaires auxquels le compte est rattaché, qui ne comptent ni ses vues ni ses clics.
     *
     * @return list<int>
     */
    private function attachedTo(?Authenticatable $viewer): array
    {
        if ($viewer === null) {
            return [];
        }

        return DB::table('partner_user')->where('user_id', $viewer->getAuthIdentifier())->pluck('partner_id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    /**
     * Le nom ou la description, dans une de leurs langues, contient `$term` (la règle commune `ContainsSearch`) :
     * la recherche de la page « Partenaires » et du back-office (décision 2026-11-23-partenaires-recherche).
     *
     * @param  Builder<Partner>  $query
     */
    public static function search(Builder $query, string $term): void
    {
        ContainsSearch::orWhere($query, array_merge(...array_map(fn (string $locale): array => ["name->{$locale}", "description->{$locale}"], Locales::values())), $term);
    }

    /**
     * Un lien qui ouvre la fiche d'un partenaire visible, depuis le carrousel de l'accueil ou la page « Partenaires » :
     * un clic de plus, sauf pour ses propres comptes (décision 2026-11-25-compteurs-partenaires). Faux s'il n'est pas
     * visible.
     */
    public function click(Partner $partner, ?Authenticatable $viewer = null): bool
    {
        if (! Partner::query()->visible()->whereKey($partner->getKey())->exists()) {
            return false;
        }
        if (in_array((int) $partner->getKey(), $this->attachedTo($viewer), true)) {
            return true;
        }

        $partner->increment('clicks_count');

        return true;
    }

    /**
     * Un partenaire proposé par un membre (décision 2026-11-24-partenaires-par-les-membres) : dans le pays courant,
     * ses textes dans la langue `$locale`, en validation ; le membre y est rattaché en administrateur (`is_admin`).
     *
     * @param  array{name: string, slogan?: string|null, description?: string|null, website?: string|null, cta_label?: string|null, contact_email?: string|null, contact_phone?: string|null}  $data
     */
    public function propose(User $user, array $data, string $locale): Partner
    {
        $partner = new Partner(['country_id' => CurrentCountry::require()->getKey(), 'status' => PartnerStatusEnum::PENDING]);
        $this->fill($partner, $data, $locale);
        $partner->save();
        $partner->users()->attach($user->getKey(), ['is_admin' => true]);
        // L'auteur est prévenu qu'il est en validation (décision 2026-11-24-partenaires-validation-notifications).
        PartnerSubmitted::dispatch($partner, $user->getKey(), true);

        return $partner;
    }

    /**
     * Un partenaire modifié par un de ses administrateurs : seuls les textes de `$locale` changent ; il repasse en
     * validation (décision 2026-11-24-partenaires-par-les-membres) ; ses comptes, l'auteur `$actor` compris, sont
     * prévenus (décision 2026-11-24-partenaires-validation-notifications).
     *
     * @param  array{name: string, slogan?: string|null, description?: string|null, website?: string|null, cta_label?: string|null, contact_email?: string|null, contact_phone?: string|null}  $data
     */
    public function change(Partner $partner, array $data, string $locale, User $actor): void
    {
        // Le statut ne change pas : affiché, il reste en ligne avec ses nouvelles données (décision
        // 2026-11-24-partenaire-modification-sans-validation).
        $this->fill($partner, $data, $locale);
        $partner->save();
        PartnerSubmitted::dispatch($partner, $actor->getKey(), false);
    }

    /**
     * Validé au back-office : il passe en « Publié » ; le changement de statut prévient ses comptes (« Partenaire
     * accepté », décision 2026-11-24-partenaires-validation-back-office).
     */
    public function accept(Partner $partner): void
    {
        $partner->status = PartnerStatusEnum::PUBLISHED;
        $partner->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fill(Partner $partner, array $data, string $locale): void
    {
        foreach (['name', 'slogan', 'description', 'website', 'cta_label'] as $key) {
            $value = trim((string) ($data[$key] ?? ''));
            if ($value === '') {
                $partner->forgetTranslation($key, $locale);

                continue;
            }
            $partner->setTranslation($key, $locale, $value);
        }
        $partner->contact_email = filled($data['contact_email'] ?? null) ? trim((string) $data['contact_email']) : null;
        // Au format international, avec l'indicatif du pays du partenaire (décision 2026-11-24-partenaire-telephone-pays).
        $partner->contact_phone = null;
        if (filled($data['contact_phone'] ?? null)) {
            $partner->contact_phone = PhoneNumbers::toE164((string) $data['contact_phone'], (string) ($data['contact_phone_country'] ?? ''));
        }
    }
}
