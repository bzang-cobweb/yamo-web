<?php

namespace Modules\Offer\Http\Resources;

use App\Models\Setting;
use App\Support\Currency;
use App\Support\DisplayTime;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Models\User;
use Modules\Form\Enums\SuccessAction;
use Modules\Form\Support\Submission;
use Modules\Notification\Support\NotificationThreads;
use Modules\Offer\Domains\Domain;
use Modules\Offer\Domains\DomainRegistry;
use Modules\Offer\Forms\OfferBookingForm;
use Modules\Offer\Forms\OfferForm;
use Modules\Offer\Forms\OfferMessageForm;
use Modules\Offer\Lists\OfferCardTitle;
use Modules\Offer\Models\Offer;
use Modules\Offer\Models\OfferInformation;
use Modules\Offer\Models\OfferReview;
use Modules\Offer\Notifications\OfferMessageReceived;

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
     * Les sections ajoutées par `addSection()`, dans l'ordre de `offer.sections`.
     *
     * @var list<array<string, mixed>>
     */
    private array $sections = [];

    /**
     * @return array{id: string, title: string, composed_title: string|null, price: array{label: string, value: string}|null, location: string|null, address: string|null, indication: string|null, photos: list<string>, description: string|null, facts: list<array{label: string, value: string}>, rules: list<array{label: string, value: string}>, informations: list<array{id: string, title: string, description: string}>, sections: list<array{key: string, open: bool}>, published_at: string|null, status: array{value: string, label: string, tone: string}|null, favorite: array{active: bool, endpoint: string}|null, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: string, message: string}}|null, reviews: array{average: float|null, count: int, latest: list<array{id: string, author: string, rating: float, comment: string, created_at: string|null}>, mine: array{rating: float, comment: string}|null, can_review: bool, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: string, message: string}}|null}|null}
     */
    public function toArray(Request $request): array
    {
        $offer = $this->resource;
        $domain = app(DomainRegistry::class)->require();
        $locale = app()->getLocale();
        $owner = (int) $request->user('sanctum')?->getAuthIdentifier() === (int) $offer->user_id;
        // Adresse masquée par le propriétaire : ni adresse, ni ville, ni indication, donc pas de bloc « Localisation » ;
        // lui voit toujours tout (décisions 2026-11-21-afficher-adresse et 2026-11-24-adresse-masquee-sans-ville).
        $located = $owner || $offer->show_address;
        $address = $located ? $this->address($offer) : null;
        $indication = $located ? $this->indication($offer, $locale) : null;
        $facts = $this->facts($offer, $domain, $locale);
        $rules = [...$this->minimumAge(), ...array_values($this->displayed($offer, $domain, $locale, fn (string $name): bool => in_array($name, $domain->ruleAttributes(), true)))];
        $informations = $this->informations($offer, $locale);
        $reviews = $this->reviews($offer, $request);
        $description = $offer->getTranslation('description', $locale) ?: null;
        $publisher = $this->publisher($offer, $locale);

        // Les sections de la fiche, dans l'ordre et l'ouverture de `offer.sections` ; une section sans contenu (`null`)
        // n'est pas ajoutée.
        $this->sections = [];
        foreach ((array) config('offer.sections', []) as $group => $open) {
            $open = (bool) $open;
            match ((string) $group) {
                // Qui publie l'offre (décision 2026-11-25-fiche-publiee-par), et son bouton « Envoyer un message », ou
                // `null` (décision 2026-11-26-messages-editeur).
                'publisher' => $this->addSection('publisher', 'rows', $open, $publisher === [] ? null : ['rows' => $publisher, 'action' => $this->publisherAction($offer, $owner, $request->user('sanctum'))]),
                'facts' => $this->addSection('facts', 'rows', $open, $facts === [] ? null : ['rows' => $facts]),
                'location' => $this->addSection('location', 'text', $open, $address === null ? null : ['text' => $address, 'note' => $indication]),
                // La description, une section à elle (décision 2026-11-22-fiche-sections-ordre).
                'description' => $this->addSection('description', 'text', $open, $description === null ? null : ['text' => $description, 'note' => null]),
                'rules' => $this->addSection('rules', 'rows', $open, $rules === [] ? null : ['rows' => $rules]),
                'reviews' => $this->addSection('reviews', 'reviews', $open, $reviews === null ? null : [], $offer->reviewsTitle($locale)),
                // Un bloc d'informations, une section à lui, avec l'ouverture réglée pour `informations`.
                'informations' => $this->addInformations($informations, $open),
                default => $this,
            };
        }

        return [
            'id' => (string) $offer->getKey(),
            'title' => (string) $offer->getTranslation('title', $locale),
            /** Le slug, tiré du titre (décision 2026-11-25-slug-url-offres-partenaires) ; `null` sans titre. */
            'url' => $offer->getTranslation('url', $locale) ?: null,
            /** Le titre composé, la ligne du type (« Location meublée · Studio · Douala »), sous le titre (décision 2026-11-22-fiche-titres-centres) ; `null` : aucune. */
            'composed_title' => OfferCardTitle::of($offer, $locale)[1],
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
            'indication' => $indication,
            /** Toutes les photos, dans l'ordre, sur l'hôte de la requête. */
            'photos' => $this->photos($offer),
            /** Les mêmes photos, dans le même ordre, en grand : la galerie plein écran (décision 2026-11-25-galerie-plein-ecran). */
            'gallery' => $this->photos($offer, 'web'),
            'description' => $description,
            /** Caractéristiques lisibles : celles que le domaine place en tête (`leadingFacts`), puis la catégorie, la ville et les attributs renseignés du domaine. */
            'facts' => $facts,
            /** Règles lisibles : l'âge minimum de l'application s'il est réglé (décision 2026-11-19-age-minimum-de-l-app), puis les règles renseignées de l'offre, dans l'ordre du domaine (décision 2026-11-19-contrat-regles-informations). */
            'rules' => $rules,
            /** Blocs d'informations, dans l'ordre (décision 2026-11-19-contrat-offer-informations) : titre et description dans la langue demandée, retours à la ligne gardés. */
            'informations' => $informations,
            'published_at' => $offer->published_at?->toIso8601String(),
            /** La date de publication, formatée (« 18 novembre 2026 », décision 2026-11-25-dates-formatees-serveur). */
            'published_on' => DisplayTime::date($offer->published_at, 'LL', $locale),
            /** Statut, servi au seul propriétaire. */
            // Pour le propriétaire, avec son ton : une pastille sur la photo, à la place du cœur
            // (décision 2026-11-21-statut-a-la-place-du-coeur).
            'status' => $owner ? ['value' => $offer->status->value, 'label' => $offer->status->label(), 'tone' => $offer->status->tone()] : null,
            /** Les avis (décision 2026-11-19-contrat-avis-offres) : moyenne, nombre, 5 derniers, le sien, s'il peut en donner un, et le retrait du sien ; `null` pour une offre non publiée. */
            'reviews' => $reviews,
            /**
             * Les sections repliables de la fiche, construites par Laravel (décision
             * 2026-11-19-sections-construites-laravel) : dans l'ordre et l'ouverture de
             * `offer.sections`, seulement celles qui ont un contenu. Chacune porte son
             * type (`rows` : lignes « libellé — valeur » ; `text` : un texte et une note ;
             * `reviews` : les avis de `reviews`), son titre et son contenu ; un bloc
             * d'informations est une section `text` à lui seul.
             */
            'sections' => $this->sections(),
            /** Bouton de la barre fixe (décision 2026-11-19-contrat-reservations) : « Réserver » (séjour) ou « Demander une visite », et le formulaire à ouvrir avec l'offre ; `null` pour son auteur, ou si le domaine ne le propose pas. Un visiteur non connecté le voit aussi : l'app le mène d'abord à la connexion. */
            'booking' => $this->booking($offer, $domain, $owner),
            /** Bouton de la barre fixe pour son auteur (décision 2026-11-19-fiche-bouton-modifier) : « Modifier » et le formulaire de l'offre, à ouvrir lié à elle ; `null` pour les autres. */
            'edit' => $owner && $request->user('sanctum')?->can('update', $offer) ? ['label' => (string) __('offer::offer.edit'), 'form' => OfferForm::name()] : null,
            /** « Retirer », pour son auteur, à la place du prix dans la barre fixe (décision 2026-11-22-retirer-depuis-la-fiche) : le libellé, la confirmation et l'envoi ; `null` pour les autres. */
            'delete' => $owner && $request->user('sanctum')?->can('delete', $offer) ? [
                'label' => (string) __('offer::offer.list.remove'),
                'confirm' => (string) __('offer::offer.list.confirm'),
                ...Submission::toRoute('api.v1.offers.destroy', SuccessAction::BACK, __('offer::offer.list.removed'), destructive: true, parameters: ['offer' => $offer->getKey()])->toArray(),
            ] : null,
            /** Cœur de favori d'un membre connecté : plein si `active` ; PUT/DELETE sur `endpoint` ; `null` pour un visiteur, sa propre offre ou une offre non publiée. */
            'favorite' => $this->favorite($offer, $request),
        ];
    }

    /**
     * @return array{average: float|null, count: int, latest: list<array{id: string, author: string, rating: float, comment: string, created_at: string|null}>, mine: array{rating: float, comment: string}|null, can_review: bool, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: string, message: string}}|null}|null
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
        return $offer->fullAddress();
    }

    /**
     * @return list<string>
     */
    private function photos(Offer $offer, string $conversion = 'detail'): array
    {
        // La fiche : ses photos à la taille du carrousel (décision 2026-11-24-avatar-photos-offre-conversions) ; la
        // galerie plein écran, à celle du web (décision 2026-11-25-galerie-plein-ecran).
        return array_map(fn (string $url): string => (string) MediaUrl::onRequestHost($url), $offer->photoUrls($conversion));
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
        $apart = $domain->ruleAttributes();
        $facts = [...$facts, ...$this->displayed($offer, $domain, $locale, fn (string $name): bool => ! in_array($name, $apart, true))];
        // Ce que le domaine y ajoute ou en retire (le loyer mensuel d'un logement, décision 2026-11-20-fiche-loyer-caution).
        $facts = $domain->adjustFacts($offer, $facts, $locale);

        // Celles que le domaine place en tête, puis les autres dans leur ordre.
        $leading = array_intersect_key(array_flip($domain->leadingFacts()), $facts);

        return array_values([...array_replace($leading, array_intersect_key($facts, $leading)), ...array_diff_key($facts, $leading)]);
    }

    /**
     * @return array{type: string, label: string, form: string}|null
     */
    private function booking(Offer $offer, Domain $domain, bool $owner): ?array
    {
        $type = $domain->bookingType($offer);

        if ($owner || $type === null || ! $offer->isVisibleToOthers()) {
            return null;
        }

        return ['type' => $type->value, 'label' => $type->actionLabel(), 'form' => OfferBookingForm::name()];
    }

    /**
     * L'âge minimum de l'application (réglage du back-office), en tête des règles de
     * toute offre ; rien s'il n'est pas réglé.
     *
     * @return list<array{label: string, value: string}>
     */
    private function minimumAge(): array
    {
        $age = Setting::minimumAge();

        return $age === null ? [] : [['label' => (string) __('settings.minimum_age'), 'value' => $age.' '.__('settings.years')]];
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
        $symbol = Currency::symbol($offer->currency, $locale);
        $values = [];

        foreach ($domain->attributes() as $field) {
            // Un attribut masqué par sa condition (les charges d'un meublé) n'est pas affiché.
            if (! $keep($field->name) || ! $field->isVisibleFor($offer->details ?? [])) {
                continue;
            }
            // Les cases d'une catégorie : une ligne « Oui » par case cochée, sous son nom (décision 2026-11-25-cases-par-categories).
            if (array_key_exists($field->name, $domain->flagGroups())) {
                $names = $field->optionLabels();
                // Les équipements : une ligne, leurs noms à la suite (décision 2026-11-25-equipements-en-categories).
                if (in_array($field->name, $domain->flagsAsList(), true)) {
                    $checked = array_map(fn (string $id): string => (string) ($names[$id] ?? $id), $domain->checkedFlags($offer, $field->name));
                    if ($checked !== []) {
                        $values[$field->name] = ['label' => (string) $field->getLabel(), 'value' => implode(', ', $checked)];
                    }

                    continue;
                }
                foreach ($domain->checkedFlags($offer, $field->name) as $id) {
                    $values['flag-'.$id] = ['label' => (string) ($names[$id] ?? $id), 'value' => (string) __('offer::rental.rules.yes', [], $locale)];
                }

                continue;
            }
            // Sans « / mois » : le libellé le dit (« Charges mensuelles », décision 2026-11-19-charges-sans-par-mois).
            $value = $field->inCurrency($symbol, withPer: false)->displayValue($offer->detail($field->name), $locale);

            if ($value !== null) {
                $values[$field->name] = ['label' => (string) $field->getLabel(), 'value' => $value];
            }
        }

        return $values;
    }

    /**
     * Ajoute une section de la fiche : sa clé, son type (`rows`, `text`, `reviews`), son ouverture, son contenu et son
     * titre (par défaut celui de `offer::offer.section_titles.<clé>`). Sans contenu (`null`), rien n'est ajouté.
     *
     * @param  array<string, mixed>|null  $content
     */
    private function addSection(string $key, string $type, bool $open, ?array $content, ?string $title = null): static
    {
        if ($content === null) {
            return $this;
        }

        $this->sections[] = [
            'key' => $key,
            'type' => $type,
            'title' => $title ?? (string) __('offer::offer.section_titles.'.$key),
            'open' => $open,
            ...$content,
        ];

        return $this;
    }

    /**
     * Ajoute les blocs d'informations, une section `text` chacun (clé `information-{id}`, son titre), avec l'ouverture
     * réglée pour `informations` (décision 2026-11-19-contrat-offer-informations).
     *
     * @param  list<array{id: string, title: string, description: string}>  $informations
     */
    private function addInformations(array $informations, bool $open): static
    {
        foreach ($informations as $information) {
            $this->addSection('information-'.$information['id'], 'text', $open, ['text' => $information['description'], 'note' => null], $information['title']);
        }

        return $this;
    }

    /**
     * Les sections ajoutées (décisions 2026-11-19-sections-fiche-laravel et 2026-11-19-sections-construites-laravel) ;
     * si aucune n'est ouverte, la première l'est.
     *
     * @return list<array{key: string, type: string, title: string, open: bool, rows?: list<array{label: string, value: string}>, text?: string, note?: string|null, action?: array{label: string, link: string}|null}>
     */
    private function sections(): array
    {
        $sections = $this->sections;

        if ($sections !== [] && ! in_array(true, array_column($sections, 'open'), true)) {
            $sections[0]['open'] = true;
        }

        return $sections;
    }

    /**
     * « Éditeur » (décision 2026-11-25-fiche-publiee-par) : le prénom de l'auteur seul, depuis quand il est membre, au
     * format court des avis (« 19 nov. 2026 », décision 2026-11-25-editeur-date-comme-avis), et s'il est identifié par l'équipe (décision 2026-11-25-identification). Sans auteur : rien.
     *
     * @return list<array{label: string, value: string}>
     */
    private function publisher(Offer $offer, string $locale): array
    {
        $author = $offer->user;
        if ($author === null) {
            return [];
        }

        return array_values(array_filter([
            filled($author->first_name) ? ['label' => __('offer::offer.publisher.name', [], $locale), 'value' => (string) $author->first_name] : null,
            $author->created_at === null ? null : ['label' => __('offer::offer.publisher.since', [], $locale), 'value' => (string) DisplayTime::date($author->created_at, 'll', $locale)],
            ['label' => __('offer::offer.publisher.identified', [], $locale), 'value' => __($author->isIdentified() ? 'offer::offer.publisher.yes' : 'offer::offer.publisher.no', [], $locale)],
        ]));
    }

    /**
     * Le bouton de la section « Éditeur » (décision 2026-11-26-messages-editeur) : « Envoyer un message » et le formulaire
     * `offer.message` lié à l'offre, pour tout lecteur sauf l'éditeur, visiteur non connecté compris (le formulaire
     * demande la connexion, comme « Réserver ») ; `null` pour l'éditeur. Un membre qui a déjà écrit à l'éditeur au sujet
     * de cette offre retrouve leur conversation : la page de sa dernière notification (décision
     * 2026-11-27-message-vers-conversation).
     *
     * @return array{label: string, link: string}|null
     */
    private function publisherAction(Offer $offer, bool $owner, mixed $reader): ?array
    {
        if ($owner) {
            return null;
        }

        $conversation = $reader instanceof User
            ? app(NotificationThreads::class)->visibleTo($reader)
                ->where('thread', OfferMessageReceived::thread((int) $offer->getKey(), (int) $reader->getKey()))
                ->latest()
                ->value('id')
            : null;

        return [
            'label' => (string) __('offer::message.action'),
            'link' => $conversation !== null
                ? '/notifications/'.$conversation
                : '/profile/'.OfferMessageForm::name().'?record='.$offer->getKey(),
        ];
    }

    /**
     * Les blocs d'informations, dans l'ordre, dans la langue `$locale`.
     *
     * @return list<array{id: string, title: string, description: string}>
     */
    private function informations(Offer $offer, string $locale): array
    {
        $informations = [];

        foreach ($offer->informations as $information) {
            /** @var OfferInformation $information */
            $informations[] = [
                'id' => (string) $information->getKey(),
                'title' => (string) $information->getTranslation('title', $locale),
                'description' => (string) $information->getTranslation('description', $locale),
            ];
        }

        return $informations;
    }

    /**
     * L'indication de l'adresse, traduite : dans la langue demandée, sinon d'origine
     * (décision 2026-11-19-indication-traduisible) ; `null` sans indication.
     */
    private function indication(Offer $offer, string $locale): ?string
    {
        return $offer->indication($locale);
    }
}
