<?php

namespace Modules\Form\Support;

/**
 * Un item d'une liste (`Listing`) : titre, sous-titre, et ce que l'app peut en
 * faire : l'ouvrir dans un formulaire lié à l'enregistrement (`edit`), le retirer
 * après confirmation (`delete`, envoi décrit comme celui d'un formulaire).
 */
final class ListItem
{
    /** @var array{form: string, record: string}|null */
    private ?array $edit = null;

    /** @var array{label: string, confirm: string, submission: Submission}|null */
    private ?array $delete = null;

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
        $this->delete = ['label' => $label, 'confirm' => $confirm, 'submission' => $submission];

        return $this;
    }

    /**
     * @return array{id: string, title: string, subtitle: string|null, edit: array{form: string, record: string}|null, delete: array{label: string, confirm: string, method: string, endpoint: string, destructive: bool, success: array{action: string, message: string}}|null}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'edit' => $this->edit,
            'delete' => $this->delete === null ? null : [
                'label' => $this->delete['label'],
                'confirm' => $this->delete['confirm'],
                ...$this->delete['submission']->toArray(),
            ],
        ];
    }
}
