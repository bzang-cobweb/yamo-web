<?php

namespace Modules\Partner\Services;

use App\Models\Setting;
use Bza\Form\Support\ContainsSearch;
use Bza\Form\Support\Locales;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
    /** Les textes d'un partenaire, saisis par langue. */
    private const TEXT_FIELDS = ['name', 'slogan', 'description', 'website', 'cta_label'];

    /**
     * Des partenaires visibles tirés au hasard, autant que réglé dans « Application » (décision
     * 2026-11-23-accueil-nombres-reglables), puis rangés dans l'ordre du back-office (décision
     * 2026-11-23-partenaires-triables), les vedettes d'abord (décision 2026-11-25-vedettes-d-abord) ; chacun compte une vue, sauf pour ses propres comptes (décision
     * 2026-11-25-compteurs-partenaires).
     *
     * @return Collection<int, Partner>
     */
    public function shown(?Authenticatable $viewer = null): Collection
    {
        // Les vedettes d'abord, tirées au hasard, puis les autres (décision 2026-11-25-vedettes-d-abord).
        $count = Setting::homePartners();
        $featured = Partner::query()->visible()->featured()->with('media')->inRandomOrder()->limit($count)->get();
        $others = $featured->count() >= $count ? collect() : Partner::query()->visible()->whereKeyNot($featured->modelKeys())->with('media')->inRandomOrder()->limit($count - $featured->count())->get();
        // Rangés : les vedettes, la date la plus lointaine d'abord, puis l'ordre du back-office.
        $partners = $featured->sortBy([['featured_until', 'desc'], ['position', 'asc'], ['id', 'asc']])
            ->concat($others->sortBy([['position', 'asc'], ['id', 'asc']]))
            ->values();
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
     * La page d'un partenaire visible ouverte, par n'importe quel lien : un clic de plus, sauf pour ses propres comptes
     * (décisions 2026-11-25-compteurs-partenaires et 2026-11-25-clic-a-l-ouverture). Faux s'il n'est pas visible.
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
        $before = $this->values($partner, $locale);
        $this->fill($partner, $data, $locale);

        // Seulement ce qui a changé, avant et après, gardé sur le partenaire (décisions 2026-11-26-partenaire-modifications
        // et 2026-11-26-partenaire-dernieres-modifications) ; rien de changé : personne n'est prévenu.
        $changes = [];
        foreach ($this->values($partner, $locale) as $field => $value) {
            if ($value !== $before[$field]) {
                $changes[] = ['field' => $field, 'old' => $before[$field], 'new' => $value];
            }
        }
        if ($changes !== []) {
            $partner->last_changes = ['changes' => $changes, 'by' => $actor->getKey(), 'at' => now()->toIso8601String()];
        }
        $partner->save();

        if ($changes !== []) {
            PartnerSubmitted::dispatch($partner, $actor->getKey(), false, $changes);
        }
    }

    /**
     * Les champs qu'un membre modifie, dans la langue `$locale` pour les textes, la description en dernier ; `''` pour un
     * champ vide.
     *
     * @return array<string, string>
     */
    private function values(Partner $partner, string $locale): array
    {
        $values = [];
        foreach (self::TEXT_FIELDS as $key) {
            $values[$key] = (string) $partner->getTranslation($key, $locale, false);
        }
        $values = [...$values, 'contact_email' => (string) $partner->contact_email, 'contact_phone' => (string) $partner->contact_phone];

        // La description, le texte le plus long, toujours en dernier (décision 2026-11-26-description-en-dernier) : ses
        // changements suivent ceux des autres champs, sur la page comme dans l'e-mail.
        $description = $values['description'];
        unset($values['description']);

        return [...$values, 'description' => $description];
    }

    /**
     * Fait ou défait un administrateur du partenaire (décision 2026-11-27-membre-administrateur), par un autre
     * administrateur : le compte doit lui être rattaché (404 sinon) et ne pas être celui qui agit (erreur de validation sur
     * `is_admin`). Le partenaire garde ainsi toujours un administrateur : en défaire un demande d'en être un autre.
     */
    public function setAdmin(Partner $partner, User $member, bool $admin, User $actor): void
    {
        abort_unless($partner->isAdministeredBy($member->getKey()), 404);

        if ($member->is($actor)) {
            throw ValidationException::withMessages(['is_admin' => __('partner::partner.member.own_admin')]);
        }

        $partner->users()->updateExistingPivot($member->getKey(), ['is_admin' => $admin]);
    }

    /**
     * Retire un compte du partenaire (décision 2026-11-27-membre-retirer), par un administrateur : le compte doit lui être
     * rattaché (404 sinon) et ne pas être celui qui agit (erreur de validation). Il ne lit plus son fil ni ne le modifie.
     */
    public function removeMember(Partner $partner, User $member, User $actor): void
    {
        abort_unless($partner->isAdministeredBy($member->getKey()), 404);

        if ($member->is($actor)) {
            throw ValidationException::withMessages(['member' => __('partner::partner.member.own_remove')]);
        }

        $partner->users()->detach($member->getKey());
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
        foreach (self::TEXT_FIELDS as $key) {
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
