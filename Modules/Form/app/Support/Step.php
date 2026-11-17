<?php

namespace Modules\Form\Support;

/**
 * Une étape d'un formulaire : un titre et un sous-titre facultatifs, et les
 * champs qu'elle affiche. L'app valide chaque étape par l'API avant de passer à
 * la suivante.
 */
final class Step
{
    /** Texte sous le titre de l'étape (ex. ce qu'on attend des champs). */
    private ?string $subtitle = null;

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

    public function subtitle(?string $subtitle): self
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    /**
     * @return array{title: string|null, subtitle: string|null, fields: list<string>}
     */
    public function toArray(): array
    {
        return ['title' => $this->title, 'subtitle' => $this->subtitle, 'fields' => $this->fields];
    }
}
