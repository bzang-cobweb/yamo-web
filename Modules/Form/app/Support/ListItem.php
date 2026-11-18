<?php

namespace Modules\Form\Support;

/**
 * Un item d'une liste (`Listing`) : titre, sous-titre, photo, et ce que l'app peut en
 * faire : l'ouvrir dans un formulaire lié à l'enregistrement (`edit`), le retirer
 * après confirmation (`delete`, envoi décrit comme celui d'un formulaire).
 */
final class ListItem
{
    /** @var array{form: string, record: string}|null */
    private ?array $edit = null;

    /** Photo de l'item (URL absolue), affichée au-dessus du titre ; aucune par défaut. */
    private ?string $image = null;

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
     * @return array{id: string, title: string, subtitle: string|null, image: string|null, edit: array{form: string, record: string}|null, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: string, message: string}}|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'image' => $this->image,
            'edit' => $this->edit,
            'delete' => $this->deleteSubmission === null ? null : [
                'label' => (string) $this->deleteLabel,
                'confirm' => (string) $this->deleteConfirm,
                ...$this->deleteSubmission->toArray(),
            ],
        ];
    }
}
