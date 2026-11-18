<?php

namespace Modules\Offer\Http\Resources;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Models\Offer;
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
     * @return array{id: string, title: string, price: array{label: string, value: string}|null, location: string|null, photos: list<string>, description: string|null, facts: list<array{label: string, value: string}>, published_at: string|null, status: array{value: string, label: string}|null}
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
            /** Prix formaté avec son unité (« 60 000 FCFA / nuit ») et son libellé (« Loyer ») ; `null` sans prix. */
            'price' => $offer->price === null ? null : [
                'label' => (string) $domain->priceLabel(),
                'value' => (string) $domain->formattedPrice($offer, $locale),
            ],
            /** La ville ; jamais la rue. */
            'location' => $offer->address?->city,
            /** Toutes les photos, dans l'ordre, sur l'hôte de la requête. */
            'photos' => $this->photos($offer),
            'description' => $offer->getTranslation('description', $locale) ?: null,
            /** Caractéristiques lisibles : la catégorie, puis les attributs renseignés du domaine. */
            'facts' => $this->facts($offer, $domain, $locale),
            'published_at' => $offer->published_at?->toIso8601String(),
            /** Statut, servi au seul propriétaire. */
            'status' => $owner ? ['value' => $offer->status->value, 'label' => $offer->status->label()] : null,
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
        $facts = $offer->category === null ? [] : [['label' => __('offer::offer.category'), 'value' => $offer->category->path()]];

        foreach ($domain->attributes() as $field) {
            $value = $field->displayValue($offer->detail($field->name), $locale);

            if ($value !== null) {
                $facts[] = ['label' => (string) $field->getLabel(), 'value' => $value];
            }
        }

        return $facts;
    }
}
