<?php

namespace Modules\Form\Support;

/**
 * Une étape d'un formulaire : un titre facultatif et les champs qu'elle affiche.
 * L'app valide chaque étape par l'API avant de passer à la suivante.
 */
final class Step
{
    /**
     * @param  list<string>  $fields  noms des champs, dans l'ordre d'affichage
     */
    private function __construct(
        public readonly ?string $title,
        public readonly array $fields,
    ) {}

    /**
     * @param  list<string>  $fields
     */
    public static function make(?string $title, array $fields): self
    {
        return new self($title, $fields);
    }

    /**
     * @return array{title: string|null, fields: list<string>}
     */
    public function toArray(): array
    {
        return ['title' => $this->title, 'fields' => $this->fields];
    }
}
