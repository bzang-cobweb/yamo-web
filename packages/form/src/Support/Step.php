<?php

namespace Bza\Form\Support;

/**
 * A step of a form: an optional title and subtitle, and the
 * fields it displays. The app validates each step through the API before moving on to
 * the next one.
 */
final class Step
{
    /** Text under the step title (e.g. what is expected in the fields). */
    private ?string $subtitle = null;

    /**
     * @param  list<string>  $fields  field names, in display order
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
