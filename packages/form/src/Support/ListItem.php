<?php

namespace Bza\Form\Support;

use Illuminate\Support\Str;

/**
 * An item of a list (`Listing`): title, subtitle, photo, rating, highlighted
 * sentence and quoted message, and what the app can
 * do with it: open it in a form bound to the record (`edit`), remove it
 * after confirmation (`delete`, submission described like a form's).
 */
final class ListItem
{
    /** @var array{form: string, record: string}|null */
    private ?array $edit = null;

    /** Photo of the item (absolute URL), displayed above the title; none by default. */
    private ?string $image = null;

    /** Average rating and number of ratings; none by default. */
    private ?float $ratingAverage = null;

    private int $ratingCount = 0;

    /** Highlighted sentence, on its own line and in bold; none by default. */
    private ?string $highlight = null;

    /** Message quoted on the card (a member's); none by default. */
    private ?string $quote = null;

    /** A separate line below the subtitle (« Séjour · du 2 au 6 déc. »); `null`: none. */
    private ?string $detail = null;

    /** @var array{count: int, label: string}|null */
    private ?array $views = null;

    private ?string $viewLink = null;

    /** @var array{label: string, panel: array{name: string, record: string}}|null */
    private ?array $ratingPanel = null;

    /** A date (ISO 8601) below the message, on the right (« il y a 2 h »); `null`: none. */
    private ?string $date = null;

    /** The number of unread notifications, as a badge to the right of the message; 0: none. */
    private int $unreadCount = 0;

    /** Favorite heart: state and API endpoint (PUT adds, DELETE removes); none by default. */
    private ?bool $favoriteActive = null;

    private ?string $favoriteEndpoint = null;

    /** App screen opened by tapping the item (« /offers/12 »); none by default. */
    private ?string $link = null;

    /** The screen opened by tapping the title alone (« /members/3 »); `null`: the title opens the card. */
    private ?string $titleLink = null;

    private ?string $subtitleLink = null;

    /** The number of lines of the subtitle; `null`: in full. */
    private ?int $subtitleLines = null;

    /** The image shown in full (`contain`) rather than filling (`cover`, the default). */
    private ?string $imageFit = null;

    /** The image small, to the left of the text (`side`), rather than above it (the default). */
    private ?string $imageLayout = null;

    private ?string $layout = null;

    /** Removal: label, confirmation question and submission (all null without removal). */
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
     * Photo of the item, above the title and subtitle (decision
     * 2026-11-18-liste-photo-barre-connecte).
     */
    public function image(?string $url): self
    {
        $this->image = filled($url) ? $url : null;

        return $this;
    }

    /**
     * Average rating (1 to 5) and number of ratings, displayed below the subtitle
     * (decision 2026-11-19-contrat-avis-offres). Without a rating (`$count` at 0), nothing.
     */
    public function rating(float|string|null $average, int $count): self
    {
        $this->ratingCount = $average === null ? 0 : max(0, $count);
        $this->ratingAverage = $this->ratingCount === 0 ? null : round((float) $average, 1);

        return $this;
    }

    /**
     * A highlighted sentence (for an offer: details and price, decision
     * 2026-11-19-item-titre-saisi-details), on its own line and in bold; truncated to
     * `$limit` characters.
     */
    public function highlight(?string $text, int $limit = 140): self
    {
        $text = $text === null ? '' : trim((string) preg_replace('/\s+/u', ' ', $text));
        $this->highlight = $text === '' ? null : Str::limit($text, $limit, '…');

        return $this;
    }

    /**
     * A message entered by a user, quoted on the card below the subtitle (a
     * member's message with their request); truncated to `$limit` characters.
     */
    public function quote(?string $text, int $limit = 300): self
    {
        $text = $text === null ? '' : trim((string) preg_replace('/\s+/u', ' ', $text));
        $this->quote = $text === '' ? null : Str::limit($text, $limit, '…');

        return $this;
    }

    /**
     * A heart on the photo (decision 2026-11-19-contrat-vedette-favoris): filled if
     * `$active`; `$endpoint` (relative to the API, `/v1/…`) receives PUT to add,
     * DELETE to remove. Without a call, no heart (guest, their own offer).
     */
    public function favorite(bool $active, string $endpoint): self
    {
        $this->favoriteActive = $active;
        $this->favoriteEndpoint = $endpoint;

        return $this;
    }

    /**
     * Tapping the item opens this app screen (« /offers/12 »), when it does not open a form.
     * With a form (`edit()`) as well, the app offers « Voir » and « Éditer »
     * (decision 2026-11-19-nuit-voir-editer-retirer).
     */
    public function link(?string $path): self
    {
        $this->link = $path;

        return $this;
    }

    /**
     * A separate line, below the subtitle, with its own style (the request type and the dates of a
     * booking, decision 2026-11-22-reservations-ligne-dates); the subtitle stays the one from the home screen.
     */
    public function detail(?string $detail): self
    {
        $this->detail = $detail === '' ? null : $detail;

        return $this;
    }

    /**
     * The number of views, below the subtitle: the app shows an eye followed by the number, `$label` (« 12 vues ») is
     * read by screen readers (decision 2026-11-25-vues-oeil).
     */
    public function views(int $count, string $label): self
    {
        $this->views = ['count' => $count, 'label' => $label];

        return $this;
    }

    /**
     * An eye to the right of the stars, which opens `$link` (decision 2026-11-27-reservations-oeil-voir): the app reads it
     * as « Voir ».
     */
    public function viewLink(string $link): self
    {
        $this->viewLink = $link;

        return $this;
    }

    /**
     * The stars open a panel of the app (decision 2026-11-27-action-panneau), titled `$label`: its name and the record it
     * shows (the reviews of an offer, decision 2026-11-27-reservations-oeil-voir).
     */
    public function ratingPanel(string $label, string $panel, string|int $record): self
    {
        $this->ratingPanel = ['label' => $label, 'panel' => ['name' => $panel, 'record' => (string) $record]];

        return $this;
    }

    /**
     * A date, below the quoted message, right-aligned, which the app shows as relative time (the latest notification
     * of a request, decision 2026-11-22-demandes-date-derniere-notification).
     */
    public function date(?string $iso): self
    {
        $this->date = $iso === '' ? null : $iso;

        return $this;
    }

    /**
     * The number of unread notifications: the app shows it as a badge to the right of the quoted message (decision
     * 2026-11-22-demandes-pastille-nombre-message).
     */
    public function unreadCount(int $count): self
    {
        $this->unreadCount = max(0, $count);

        return $this;
    }

    /**
     * Tapping the title alone opens this app screen (« /members/3 »), the rest of the card keeping its link;
     * the title is then displayed as a link (decision 2026-11-21-demandes-lien-membre).
     */
    public function titleLink(?string $path): self
    {
        $this->titleLink = $path;

        return $this;
    }

    /**
     * Tapping the subtitle alone opens this app screen (the entered title of an offer, « /offers/12 »), the rest of the
     * card keeping its link; the subtitle is then displayed as a link (decision 2026-11-22-reservations-lien-offre).
     */
    public function subtitleLink(?string $path): self
    {
        $this->subtitleLink = $path;

        return $this;
    }

    /**
     * The image shown in full, centered in its slot, rather than filling it and cropped: for a logo (decision
     * 2026-11-24-mes-partenaires-logo-entier); without a call, it fills like a photo.
     */
    public function imageContain(): self
    {
        $this->imageFit = 'contain';

        return $this;
    }

    /**
     * The layout of a review, as on an offer's page (decision 2026-11-25-liste-avis-generique): the title
     * (the first name), then the rating stars and the separate line (the date, formatted by the server) separated by a
     * dot, then the subtitle (the comment).
     */
    public function reviewLayout(): self
    {
        $this->layout = 'review';

        return $this;
    }

    /**
     * The image small, in full, to the left of the text, like the « Partenaires » page; the status above the title
     * (« Mes partenaires », decision 2026-11-24-mes-partenaires-comme-partenaires); without a call, above the text.
     */
    public function imageSide(): self
    {
        $this->imageFit = 'contain';
        $this->imageLayout = 'side';

        return $this;
    }

    /**
     * The subtitle cut to `$lines` lines, the rest as « … » (a partner's description, decision
     * 2026-11-24-mes-partenaires-cartes); without a call, in full.
     */
    public function subtitleLines(int $lines): self
    {
        $this->subtitleLines = $lines;

        return $this;
    }

    /**
     * Tapping the item opens the form `$form` bound to this record.
     */
    public function edit(string $form, string|int|null $record = null): self
    {
        $this->edit = ['form' => $form, 'record' => (string) ($record ?? $this->id)];

        return $this;
    }

    /**
     * A button on the card (decision 2026-11-19-contrat-reservations-logements-profil):
     * it sends `$submission` (after `$confirm`, if any), opens the
     * form `$form` bound to `$record`, or opens the app screen `$link`
     * (« /offers/12/calendar »). Style: `primary`, `destructive` or `default`. With
     * `$filter`, the screen `$link` opens with these filters already chosen, stored under the name
     * `$list` (« notifications »), like a counter (decision 2026-11-21-lien-notifications).
     *
     * @param  array<string, string>|null  $filter
     */
    public function action(string $label, string $style = 'default', ?string $confirm = null, ?Submission $submission = null, ?string $form = null, string|int|null $record = null, ?string $link = null, ?array $filter = null, ?string $list = null, ?string $panel = null): self
    {
        $this->actions[] = [
            'label' => $label,
            'style' => $style,
            'confirm' => $confirm,
            'submission' => $submission,
            'form' => $form === null ? null : ['form' => $form, 'record' => (string) ($record ?? $this->id)],
            'link' => $link,
            ...($filter === null ? [] : ['list' => $list, 'filter' => $filter]),
            // A panel the app opens over the list, titled by the label (decision 2026-11-27-action-panneau): its name,
            // known to the app (`partner.statistics`), and the record it shows.
            ...($panel === null ? [] : ['panel' => ['name' => $panel, 'record' => (string) ($record ?? $this->id)]]),
        ];

        return $this;
    }

    /**
     * A colored badge on the card (a status: « En attente », « Acceptée »),
     * decision 2026-11-19-contrat-reservations-logements-profil. Tone: `warning`
     * (orange), `success` (green), `danger` (red) or `neutral` (grey).
     */
    public function tag(string $label, string $tone = 'neutral', bool $onImage = false): self
    {
        // `$onImage`: on the photo, top right, in place of the favorite heart (decision
        // 2026-11-21-statut-a-la-place-du-coeur).
        $this->tag = ['label' => $label, 'tone' => $tone, ...($onImage ? ['on_image' => true] : [])];

        return $this;
    }

    /**
     * A counter on the card (« 2 demandes en attente ») that opens the list `$list`
     * with these filters already chosen; nothing without a counter. With `$link`, it opens this
     * app screen (« /notifications ») and `$list` names its filters. With `$tone`
     * (`warning`, `success`, `danger`, `neutral`), a badge of that color, below the
     * title (decision 2026-11-21-pastille-demandes-en-attente).
     *
     * @param  array<string, string>  $filter
     */
    public function counter(string $label, string $list, array $filter = [], ?string $link = null, ?string $tone = null): self
    {
        // Several counters possible, in call order (`counters`); `counter` stays the first one,
        // for an app that only knows one (decision 2026-11-21-compteur-non-lues).
        $this->counters[] = ['label' => $label, 'list' => $list, 'filter' => $filter, 'link' => $link, ...($tone === null ? [] : ['tone' => $tone])];
        $this->counter ??= $this->counters[0];

        return $this;
    }

    /**
     * Removal button: label, confirmation question, and submission.
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
            /** Separate line below the subtitle; absent: none. */
            ...($this->detail === null ? [] : ['detail' => $this->detail]),
            /** The views: an eye followed by the number; `label` for screen readers; absent: nothing. */
            ...($this->views === null ? [] : ['views' => $this->views]),
            /** An eye to the right of the stars, which opens this app screen; absent: none. */
            ...($this->viewLink === null ? [] : ['view_link' => $this->viewLink]),
            /** The stars open this panel, titled `label`; absent: the stars open nothing. */
            ...($this->ratingPanel === null ? [] : ['rating_panel' => $this->ratingPanel]),
            /** ISO 8601 date below the message, on the right; absent: none. */
            ...($this->date === null ? [] : ['date' => $this->date, 'date_text' => DisplayTime::relative($this->date)]),
            /** Unread notifications, as a badge to the right of the message; absent: none. */
            ...($this->unreadCount > 0 ? ['unread_count' => $this->unreadCount] : []),
            'image' => $this->image,
            /** Average rating (1 to 5, to one decimal) and number of ratings; `null` without a rating. */
            'rating' => $this->ratingAverage === null ? null : ['average' => $this->ratingAverage, 'count' => $this->ratingCount],
            /** Highlighted sentence, displayed in bold on its own line; `null` without. */
            'highlight' => $this->highlight,
            /** Message quoted on the card, below the subtitle; `null` without. */
            'quote' => $this->quote,
            /** Favorite heart: filled if `active`; PUT on `endpoint` adds, DELETE removes; `null`: no heart. */
            'favorite' => $this->favoriteEndpoint === null ? null : ['active' => (bool) $this->favoriteActive, 'endpoint' => $this->favoriteEndpoint],
            /** App screen opened by tapping the item; `null`: none. */
            'link' => $this->link,
            /** Screen opened by tapping the title alone; absent: the title opens the card. */
            ...($this->titleLink === null ? [] : ['title_link' => $this->titleLink]),
            ...($this->subtitleLink === null ? [] : ['subtitle_link' => $this->subtitleLink]),
            ...($this->subtitleLines === null ? [] : ['subtitle_lines' => $this->subtitleLines]),
            ...($this->imageFit === null ? [] : ['image_fit' => $this->imageFit]),
            ...($this->imageLayout === null ? [] : ['image_layout' => $this->imageLayout]),
            ...($this->layout === null ? [] : ['layout' => $this->layout]),
            'edit' => $this->edit,
            /** Card buttons: label, style, confirmation, and a submission, a form to open (bound to `record`) or an app screen (`link`). */
            'actions' => array_map(fn (array $action): array => [
                'label' => $action['label'],
                'style' => $action['style'],
                'confirm' => $action['confirm'],
                'submission' => $action['submission']?->toArray(),
                'form' => $action['form'],
                'link' => $action['link'],
                // The already chosen filters of the opened screen, if it has any (decision 2026-11-21-lien-notifications).
                ...(isset($action['filter']) ? ['list' => $action['list'], 'filter' => $action['filter']] : []),
                ...(isset($action['panel']) ? ['panel' => $action['panel']] : []),
            ], $this->actions),
            /** Colored badge (a status): label and tone (`warning`, `success`, `danger`, `neutral`); `null`: none. */
            'tag' => $this->tag,
            /** Card counter: its label, the list it opens (or the `link` screen) and the already chosen filters; `null`: none. */
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
