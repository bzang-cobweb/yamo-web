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
     * @return array{id: string, title: string, price: array{label: string, value: string}|null, location: string|null, address_description: string|null, photos: list<string>, description: string|null, facts: list<array{label: string, value: string}>, published_at: string|null, status: array{value: string, label: string}|null, favorite: array{active: bool, endpoint: string}|null, reviews: array{average: float|null, count: int, latest: list<array{id: string, author: string, rating: int, comment: string, created_at: string|null}>, mine: array{rating: int, comment: string}|null, can_review: bool, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: string, message: string}}|null}|null}
     */
    public function toArray(Request $request): array
    {
        $offer = $this->resource;
        $domain = app(DomainRegistry::class)->require();
        $locale = app()->getLocale();
        $owner = (int) $request->user('sanctum')?->getAuthIdentifier() === (int) $offer->user_id;

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
            'address_description' => $offer->address_description,
            /** Toutes les photos, dans l'ordre, sur l'hôte de la requête. */
            'photos' => $this->photos($offer),
            'description' => $offer->getTranslation('description', $locale) ?: null,
            /** Caractéristiques lisibles : celles que le domaine place en tête (`leadingFacts`), puis la catégorie, la ville et les attributs renseignés du domaine. */
            'facts' => $this->facts($offer, $domain, $locale),
            'published_at' => $offer->published_at?->toIso8601String(),
            /** Statut, servi au seul propriétaire. */
            'status' => $owner ? ['value' => $offer->status->value, 'label' => $offer->status->label()] : null,
            /** Les avis (décision 2026-11-19-contrat-avis-offres) : moyenne, nombre, 5 derniers, le sien, s'il peut en donner un, et le retrait du sien ; `null` pour une offre non publiée. */
            'reviews' => $this->reviews($offer, $request),
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
        foreach ($offer->reviews()->with('user')->latest('updated_at')->latest('id')->limit(5)->get() as $review) {
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
        // Par nom : la catégorie, la ville (décision 2026-11-19-fiche-offre-ordre), puis les attributs.
        $facts = [];
        if ($offer->category !== null) {
            $facts['category_id'] = ['label' => __('offer::offer.category'), 'value' => $offer->category->path()];
        }
        if ($domain->hasAddress() && filled($offer->address?->city)) {
            $facts['city'] = ['label' => __('offer::offer.city'), 'value' => (string) $offer->address->city];
        }

        // Un montant prend le symbole de la devise de l'offre, sinon de son pays (« 5 000 FCFA / mois »).
        $symbol = Currency::symbol($offer->currency ?? $offer->country?->currency, $locale);

        foreach ($domain->attributes() as $field) {
            $field->inCurrency($symbol);

            // Un attribut masqué par sa condition (les charges d'un meublé) n'est pas affiché.
            if (! $field->isVisibleFor($offer->details ?? [])) {
                continue;
            }
            $value = $field->displayValue($offer->detail($field->name), $locale);

            if ($value !== null) {
                $facts[$field->name] = ['label' => (string) $field->getLabel(), 'value' => $value];
            }
        }

        // Celles que le domaine place en tête, puis les autres dans leur ordre.
        $leading = array_intersect_key(array_flip($domain->leadingFacts()), $facts);

        return array_values([...array_replace($leading, array_intersect_key($facts, $leading)), ...array_diff_key($facts, $leading)]);
    }
}
