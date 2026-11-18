<?php

namespace Modules\Form\Support;

use Illuminate\Support\Str;

/**
 * Un item d'une liste (`Listing`) : titre, sous-titre, photo, note et phrase mise
 * en avant, et ce que l'app peut en
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

    /** Retrait : libellé, question de confirmation et envoi (tous nuls sans retrait). */
    private ?string $deleteLabel = null;

    private ?string $deleteConfirm = null;

    private ?Submission $deleteSubmission = null;

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
     * Une phrase mise en avant (le dernier avis d'une offre), sur sa ligne et en
     * gras ; tronquée à `$limit` caractères.
     */
    public function highlight(?string $text, int $limit = 140): self
    {
        $text = $text === null ? '' : trim((string) preg_replace('/\s+/u', ' ', $text));
        $this->highlight = $text === '' ? null : Str::limit($text, $limit, '…');

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
     * @return array{id: string, title: string, subtitle: string|null, image: string|null, rating: array{average: float, count: int}|null, highlight: string|null, edit: array{form: string, record: string}|null, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: string, message: string}}|null}
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
            'edit' => $this->edit,
            'delete' => $this->deleteSubmission === null ? null : [
                'label' => (string) $this->deleteLabel,
                'confirm' => (string) $this->deleteConfirm,
                ...$this->deleteSubmission->toArray(),
            ],
        ];
    }
}
