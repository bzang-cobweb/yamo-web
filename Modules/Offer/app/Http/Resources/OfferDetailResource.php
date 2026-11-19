<?php

namespace Modules\Offer\Http\Resources;

use App\Support\Currency;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Submission;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferReview;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * La fiche d'une offre, telle que l'app l'affiche sans connaître le domaine
 * (décision 2026-11-18-fiche-offre). Textes dans la langue de la requête. La rue
 * n'est jamais servie : la ville seulement.
 *
 * @property Offer $resource
 */
class OfferDetailResource extends JsonResource
{
    /**
     * @return array{id: string, title: string, price: array{label: string, value: string}|null, location: string|null, address: string|null, indication: string|null, photos: list<string>, description: string|null, facts: list<array{label: string, value: string}>, rules: list<array{label: string, value: string}>, information: string|null, sections: list<array{key: string, open: bool}>, published_at: string|null, status: array{value: string, label: string}|null, favorite: array{active: bool, endpoint: string}|null, reviews: array{average: float|null, count: int, latest: list<array{id: string, author: string, rating: int, comment: string, created_at: string|null}>, mine: array{rating: int, comment: string}|null, can_review: bool, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: string, message: string}}|null}|null}
     */
    public function toArray(Request $request): array
    {
        $offer = $this->resource;
        $domain = app(DomainRegistry::class)->require();
        $locale = app()->getLocale();
        $owner = (int) $request->user('sanctum')?->getAuthIdentifier() === (int) $offer->user_id;
        $address = $this->address($offer);
        $facts = $this->facts($offer, $domain, $locale);
        $rules = array_values($this->displayed($offer, $domain, $locale, fn (string $name): bool => in_array($name, $domain->ruleAttributes(), true)));
        $information = $this->information($offer, $domain, $locale);
        $reviews = $this->reviews($offer, $request);

        return [
            'id' => (string) $offer->getKey(),
            'title' => (string) $offer->getTranslation('title', $locale),
            /** Prix formaté avec son unité (« 60 000 FCFA / jour ») et son libellé (« Loyer ») ; `null` sans prix. */
            'price' => $offer->price === null ? null : [
                'label' => (string) $domain->priceLabel(),
                'value' => (string) $domain->formattedPrice($offer, $locale),
            ],
            /** La ville ; jamais la rue. */
            'location' => $offer->address?->city,
            /** Indications sur l'adresse écrites par le propriétaire, ou `null`. */
            // L'adresse complète, rue comprise (décision 2026-11-19-fiche-localisation).
            'address' => $address,
            // L'indication propre à l'offre (décision 2026-11-19-contrat-adresse-offre-table).
            'indication' => $offer->offerAddress?->indication,
            /** Toutes les photos, dans l'ordre, sur l'hôte de la requête. */
            'photos' => $this->photos($offer),
            'description' => $offer->getTranslation('description', $locale) ?: null,
            /** Caractéristiques lisibles : celles que le domaine place en tête (`leadingFacts`), puis la catégorie, la ville et les attributs renseignés du domaine. */
            'facts' => $facts,
            /** Règles du logement renseignées, lisibles (décision 2026-11-19-contrat-regles-informations), dans l'ordre du domaine. */
            'rules' => $rules,
            /** Autres informations pratiques, texte libre dans la langue demandée (retours à la ligne gardés), ou `null`. */
            'information' => $information,
            'published_at' => $offer->published_at?->toIso8601String(),
            /** Statut, servi au seul propriétaire. */
            'status' => $owner ? ['value' => $offer->status->value, 'label' => $offer->status->label()] : null,
            /** Les avis (décision 2026-11-19-contrat-avis-offres) : moyenne, nombre, 5 derniers, le sien, s'il peut en donner un, et le retrait du sien ; `null` pour une offre non publiée. */
            'reviews' => $reviews,
            /** Les sections repliables à afficher, dans l'ordre, et si elles sont ouvertes à l'arrivée (configuration `offer.sections`) ; seulement celles qui ont un contenu. */
            'sections' => $this->sections([
                'location' => $address !== null,
                'facts' => $facts !== [],
                'rules' => $rules !== [] || $information !== null,
                'reviews' => $reviews !== null,
            ]),
            /** Cœur de favori d'un membre connecté : plein si `active` ; PUT/DELETE sur `endpoint` ; `null` pour un visiteur, sa propre offre ou une offre non publiée. */
            'favorite' => $this->favorite($offer, $request),
        ];
    }

    /**
     * @return array{average: float|null, count: int, latest: list<array{id: string, author: string, rating: int, comment: string, created_at: string|null}>, mine: array{rating: int, comment: string}|null, can_review: bool, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: string, message: string}}|null}|null
     */
    private function reviews(Offer $offer, Request $request): ?array
    {
        if (! $offer->showsReviews()) {
            return null;
        }

        $user = $request->user('sanctum');
        $count = $offer->reviews()->count();
        $average = $offer->reviews()->avg('rating');
        $mine = $user === null ? null : $offer->reviews()->where('user_id', $user->getAuthIdentifier())->first();
        $latest = [];

        // Même forme que la liste complète (`OfferReviewResource`).
        foreach ($offer->reviews()->with('user')->latest('updated_at')->latest('id')->limit((int) config('offer.reviews_shown', 1))->get() as $review) {
            /** @var OfferReview $review */
            $latest[] = (new OfferReviewResource($review))->toArray($request);
        }

        return [
            'average' => $count === 0 ? null : round((float) $average, 1),
            'count' => $count,
            'latest' => $latest,
            'mine' => $mine === null ? null : ['rating' => $mine->rating, 'comment' => $mine->comment],
            'can_review' => $user !== null && Gate::forUser($user)->allows('review', $offer),
            /** Retirer son avis : libellé, confirmation et envoi ; `null` sans avis. */
            'delete' => $mine === null ? null : [
                'label' => __('offer::review.delete'),
                'confirm' => __('offer::review.delete_confirm'),
                ...Submission::toRoute('api.v1.offers.review.destroy', SuccessAction::BACK, __('offer::review.deleted'), destructive: true, parameters: ['offer' => $offer->getKey()])->toArray(),
            ],
        ];
    }

    /**
     * @return array{active: bool, endpoint: string}|null
     */
    private function favorite(Offer $offer, Request $request): ?array
    {
        $user = $request->user('sanctum');

        if ($user === null || ! Gate::forUser($user)->allows('favorite', $offer)) {
            return null;
        }

        return [
            'active' => $offer->favorites()->where('user_id', $user->getAuthIdentifier())->exists(),
            'endpoint' => $offer->favoriteEndpoint(),
        ];
    }

    /**
     * « Rue du Lac 4, 1200 Genève, Suisse », le pays toujours, dans la langue de la
     * requête (décision 2026-11-19-fiche-localisation-accordeon) ; sans adresse, `null`.
     */
    private function address(Offer $offer): ?string
    {
        $address = $offer->address;

        if ($address === null) {
            return null;
        }

        return implode(', ', array_filter([trim((string) $address->street), $address->subtitle()], fn (string $part): bool => $part !== '')) ?: null;
    }

    /**
     * @return list<string>
     */
    private function photos(Offer $offer): array
    {
        $urls = [];

        foreach ($offer->getMedia(Offer::PHOTOS) as $media) {
            /** @var Media $media */
            $urls[] = (string) MediaUrl::onRequestHost($media->getUrl());
        }

        return $urls;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function facts(Offer $offer, Domain $domain, string $locale): array
    {
        // Par nom : la catégorie, puis les attributs. La ville n'y est plus : elle est dans
        // l'adresse du bloc « Localisation » (décision 2026-11-19-fiche-sans-ville).
        $facts = [];
        if ($offer->category !== null) {
            $facts['category_id'] = ['label' => __('offer::offer.category'), 'value' => $offer->category->path()];
        }

        // Les attributs, sans les règles ni le texte libre, servis à part (décision 2026-11-19-contrat-regles-informations).
        $apart = [...$domain->ruleAttributes(), $domain->informationAttribute()];
        $facts = [...$facts, ...$this->displayed($offer, $domain, $locale, fn (string $name): bool => ! in_array($name, $apart, true))];

        // Celles que le domaine place en tête, puis les autres dans leur ordre.
        $leading = array_intersect_key(array_flip($domain->leadingFacts()), $facts);

        return array_values([...array_replace($leading, array_intersect_key($facts, $leading)), ...array_diff_key($facts, $leading)]);
    }

    /**
     * Les attributs renseignés et visibles dont le nom passe `$keep`, lisibles, par nom.
     *
     * @param  callable(string): bool  $keep
     * @return array<string, array{label: string, value: string}>
     */
    private function displayed(Offer $offer, Domain $domain, string $locale, callable $keep): array
    {
        // Un montant prend le symbole de la devise de l'offre, sinon de son pays (« 5 000 FCFA / mois »).
        $symbol = Currency::symbol($offer->currency ?? $offer->country?->currency, $locale);
        $values = [];

        foreach ($domain->attributes() as $field) {
            // Un attribut masqué par sa condition (les charges d'un meublé) n'est pas affiché.
            if (! $keep($field->name) || ! $field->isVisibleFor($offer->details ?? [])) {
                continue;
            }
            $value = $field->inCurrency($symbol)->displayValue($offer->detail($field->name), $locale);

            if ($value !== null) {
                $values[$field->name] = ['label' => (string) $field->getLabel(), 'value' => $value];
            }
        }

        return $values;
    }

    /**
     * Le texte libre des informations pratiques, ou `null`.
     */
    private function information(Offer $offer, Domain $domain, string $locale): ?string
    {
        $name = $domain->informationAttribute();
        $text = $name === null ? null : $offer->detailIn($name, $locale);

        return is_string($text) && trim($text) !== '' ? $text : null;
    }

    /**
     * Les sections de la fiche (décision 2026-11-19-sections-fiche-laravel) : dans
     * l'ordre de `offer.sections`, celles qui ont un contenu, ouvertes ou non ; si
     * aucune n'est ouverte, la première l'est.
     *
     * @param  array<string, bool>  $present
     * @return list<array{key: string, open: bool}>
     */
    private function sections(array $present): array
    {
        $sections = [];

        foreach ((array) config('offer.sections', []) as $key => $open) {
            if ($present[$key] ?? false) {
                $sections[] = ['key' => (string) $key, 'open' => (bool) $open];
            }
        }

        if ($sections !== [] && ! in_array(true, array_column($sections, 'open'), true)) {
            $sections[0]['open'] = true;
        }

        return $sections;
    }
}
