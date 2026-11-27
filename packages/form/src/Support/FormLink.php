<?php

namespace Bza\Form\Support;

/**
 * A link shown under a form, opening another form (decision 2026-11-27-formulaire-liens): "Verify my address" under
 * "Change my email". The app opens the target form the way it opens the current one.
 */
final readonly class FormLink
{
    public function __construct(
        /** Text of the link, translated. */
        public string $label,
        /** Name of the form it opens (`auth.profile.email.verify`). */
        public string $form,
    ) {}

    public static function make(string $label, string $form): self
    {
        return new self($label, $form);
    }

    /**
     * @return array{label: string, form: string}
     */
    public function toArray(): array
    {
        return ['label' => $this->label, 'form' => $this->form];
    }
}
