<?php

namespace Modules\Form\Support;

use Illuminate\Support\Str;

/**
 * Un item d'une liste (`Listing`) : titre, sous-titre, photo, note, phrase mise
 * en avant et message cité, et ce que l'app peut en
 * faire : l'ouvrir dans un formulaire lié à l'enregistrement (`edit`), le retirer
 * après confirmation (`delete`, envoi décrit comme celui d'un formulaire).
 */
final class ListItem
{
    /** @var array{form: string, record: string}|null */
    private ?array $edit = null;

    /** Photo de l'item (URL absolue), affichée au-dessus du titre ; aucune par défaut. */
    private ?string $image = null;

    /** Note moyenne et nombre de notes ; aucune par défaut. */
    private ?float $ratingAverage = null;

    private int $ratingCount = 0;

    /** Phrase mise en avant, sur sa ligne et en gras ; aucune par défaut. */
    private ?string $highlight = null;

    /** Message cité sur la carte (celui d'un membre) ; aucun par défaut. */
    private ?string $quote = null;

    /** Cœur de favori : état et point d'API (PUT ajoute, DELETE retire) ; aucun par défaut. */
    private ?bool $favoriteActive = null;

    private ?string $favoriteEndpoint = null;

    /** Écran de l'app ouvert en touchant l'item (« /offers/12 ») ; aucun par défaut. */
    private ?string $link = null;

    /** Retrait : libellé, question de confirmation et envoi (tous nuls sans retrait). */
    private ?string $deleteLabel = null;

    private ?string $deleteConfirm = null;

    private ?Submission $deleteSubmission = null;

    /** @var list<array{label: string, style: string, confirm: string|null, submission: Submission|null, form: array{form: string, record: string}|null, link: string|null, list?: string|null, filter?: array<string, string>}> */
    private array $actions = [];

    /** @var array{label: string, list: string, filter: array<string, string>, link: string|null}|null */
    private ?array $counter = null;

    /** @var list<array{label: string, list: string, filter: array<string, string>, link: string|null}> */
    private array $counters = [];

    /** @var array{label: string, tone: string, on_image?: bool}|null */
    private ?array $tag = null;

    private function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $subtitle,
    ) {}

    public static function make(string|int $id, string $title, ?string $subtitle = null): self
    {
        return new self((string) $id, $title, $subtitle);
    }

    /**
     * Photo de l'item, au-dessus du titre et du sous-titre (décision
     * 2026-11-18-liste-photo-barre-connecte).
     */
    public function image(?string $url): self
    {
        $this->image = filled($url) ? $url : null;

        return $this;
    }

    /**
     * Note moyenne (1 à 5) et nombre de notes, affichées sous le sous-titre
     * (décision 2026-11-19-contrat-avis-offres). Sans note (`$count` à 0), rien.
     */
    public function rating(float|string|null $average, int $count): self
    {
        $this->ratingCount = $average === null ? 0 : max(0, $count);
        $this->ratingAverage = $this->ratingCount === 0 ? null : round((float) $average, 1);

        return $this;
    }

    /**
     * Une phrase mise en avant (pour une offre : détails et prix, décision
     * 2026-11-19-item-titre-saisi-details), sur sa ligne et en gras ; tronquée à
     * `$limit` caractères.
     */
    public function highlight(?string $text, int $limit = 140): self
    {
        $text = $text === null ? '' : trim((string) preg_replace('/\s+/u', ' ', $text));
        $this->highlight = $text === '' ? null : Str::limit($text, $limit, '…');

        return $this;
    }

    /**
     * Un message saisi par un utilisateur, cité sur la carte sous le sous-titre (le
     * message d'un membre avec sa demande) ; tronqué à `$limit` caractères.
     */
    public function quote(?string $text, int $limit = 300): self
    {
        $text = $text === null ? '' : trim((string) preg_replace('/\s+/u', ' ', $text));
        $this->quote = $text === '' ? null : Str::limit($text, $limit, '…');

        return $this;
    }

    /**
     * Un cœur sur la photo (décision 2026-11-19-contrat-vedette-favoris) : plein si
     * `$active` ; `$endpoint` (relatif à l'API, `/v1/…`) reçoit PUT pour ajouter,
     * DELETE pour retirer. Sans appel, pas de cœur (visiteur, sa propre offre).
     */
    public function favorite(bool $active, string $endpoint): self
    {
        $this->favoriteActive = $active;
        $this->favoriteEndpoint = $endpoint;

        return $this;
    }

    /**
     * Toucher l'item ouvre cet écran de l'app (« /offers/12 »), quand il n'ouvre pas de formulaire.
     * Avec un formulaire (`edit()`) aussi, l'app propose « Voir » et « Éditer »
     * (décision 2026-11-19-nuit-voir-editer-retirer).
     */
    public function link(?string $path): self
    {
        $this->link = $path;

        return $this;
    }

    /**
     * Toucher l'item ouvre le formulaire `$form` lié à cet enregistrement.
     */
    public function edit(string $form, string|int|null $record = null): self
    {
        $this->edit = ['form' => $form, 'record' => (string) ($record ?? $this->id)];

        return $this;
    }

    /**
     * Un bouton sur la carte (décision 2026-11-19-contrat-reservations-logements-profil) :
     * il envoie `$submission` (après `$confirm`, s'il y en a une), ouvre le
     * formulaire `$form` lié à `$record`, ou ouvre l'écran de l'app `$link`
     * (« /offers/12/calendar »). Style : `primary`, `destructive` ou `default`. Avec
     * `$filter`, l'écran `$link` s'ouvre avec ces filtres déjà choisis, rangés sous le nom
     * `$list` (« notifications »), comme un compteur (décision 2026-11-21-lien-notifications).
     *
     * @param  array<string, string>|null  $filter
     */
    public function action(string $label, string $style = 'default', ?string $confirm = null, ?Submission $submission = null, ?string $form = null, string|int|null $record = null, ?string $link = null, ?array $filter = null, ?string $list = null): self
    {
        $this->actions[] = [
            'label' => $label,
            'style' => $style,
            'confirm' => $confirm,
            'submission' => $submission,
            'form' => $form === null ? null : ['form' => $form, 'record' => (string) ($record ?? $this->id)],
            'link' => $link,
            ...($filter === null ? [] : ['list' => $list, 'filter' => $filter]),
        ];

        return $this;
    }

    /**
     * Une pastille de couleur sur la carte (un statut : « En attente », « Acceptée »),
     * décision 2026-11-19-contrat-reservations-logements-profil. Ton : `warning`
     * (orange), `success` (vert), `danger` (rouge) ou `neutral` (gris).
     */
    public function tag(string $label, string $tone = 'neutral', bool $onImage = false): self
    {
        // `$onImage` : sur la photo, en haut à droite, à la place du cœur de favori (décision
        // 2026-11-21-statut-a-la-place-du-coeur).
        $this->tag = ['label' => $label, 'tone' => $tone, ...($onImage ? ['on_image' => true] : [])];

        return $this;
    }

    /**
     * Un compteur sur la carte (« 2 demandes en attente ») qui ouvre la liste `$list`
     * avec ces filtres déjà choisis ; rien sans compteur. Avec `$link`, il ouvre cet
     * écran de l'app (« /notifications ») et `$list` nomme ses filtres. Avec `$tone`
     * (`warning`, `success`, `danger`, `neutral`), une pastille de cette couleur, sous le
     * titre (décision 2026-11-21-pastille-demandes-en-attente).
     *
     * @param  array<string, string>  $filter
     */
    public function counter(string $label, string $list, array $filter = [], ?string $link = null, ?string $tone = null): self
    {
        // Plusieurs compteurs possibles, dans l'ordre d'appel (`counters`) ; `counter` reste le premier,
        // pour une app qui n'en connaît qu'un (décision 2026-11-21-compteur-non-lues).
        $this->counters[] = ['label' => $label, 'list' => $list, 'filter' => $filter, 'link' => $link, ...($tone === null ? [] : ['tone' => $tone])];
        $this->counter ??= $this->counters[0];

        return $this;
    }

    /**
     * Bouton de retrait : libellé, question de confirmation, et envoi.
     */
    public function delete(string $label, string $confirm, Submission $submission): self
    {
        $this->deleteLabel = $label;
        $this->deleteConfirm = $confirm;
        $this->deleteSubmission = $submission;

        return $this;
    }

    /**
     * @return array{id: string, title: string, subtitle: string|null, image: string|null, rating: array{average: float, count: int}|null, highlight: string|null, favorite: array{active: bool, endpoint: string}|null, link: string|null, edit: array{form: string, record: string}|null, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: string, message: string}}|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'image' => $this->image,
            /** Note moyenne (1 à 5, au dixième) et nombre de notes ; `null` sans note. */
            'rating' => $this->ratingAverage === null ? null : ['average' => $this->ratingAverage, 'count' => $this->ratingCount],
            /** Phrase mise en avant, affichée en gras sur sa ligne ; `null` sans. */
            'highlight' => $this->highlight,
            /** Message cité sur la carte, sous le sous-titre ; `null` sans. */
            'quote' => $this->quote,
            /** Cœur de favori : plein si `active` ; PUT sur `endpoint` ajoute, DELETE retire ; `null` : pas de cœur. */
            'favorite' => $this->favoriteEndpoint === null ? null : ['active' => (bool) $this->favoriteActive, 'endpoint' => $this->favoriteEndpoint],
            /** Écran de l'app ouvert en touchant l'item ; `null` : aucun. */
            'link' => $this->link,
            'edit' => $this->edit,
            /** Boutons de la carte : libellé, style, confirmation, et un envoi, un formulaire à ouvrir (lié à `record`) ou un écran de l'app (`link`). */
            'actions' => array_map(fn (array $action): array => [
                'label' => $action['label'],
                'style' => $action['style'],
                'confirm' => $action['confirm'],
                'submission' => $action['submission']?->toArray(),
                'form' => $action['form'],
                'link' => $action['link'],
                // Les filtres déjà choisis de l'écran ouvert, s'il en a (décision 2026-11-21-lien-notifications).
                ...(isset($action['filter']) ? ['list' => $action['list'], 'filter' => $action['filter']] : []),
            ], $this->actions),
            /** Pastille de couleur (un statut) : libellé et ton (`warning`, `success`, `danger`, `neutral`) ; `null` : aucune. */
            'tag' => $this->tag,
            /** Compteur de la carte : son libellé, la liste qu'il ouvre (ou l'écran `link`) et les filtres déjà choisis ; `null` : aucun. */
            'counter' => $this->counter,
            'counters' => $this->counters,
            'delete' => $this->deleteSubmission === null ? null : [
                'label' => (string) $this->deleteLabel,
                'confirm' => (string) $this->deleteConfirm,
                ...$this->deleteSubmission->toArray(),
            ],
        ];
    }
}
