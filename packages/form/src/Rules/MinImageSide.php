<?php

namespace Bza\Form\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * An uploaded image measures at least `$pixels` on its longest side (decision 2026-11-24-logo-taille-minimale):
 * any smaller, it would be upscaled, hence blurry, where it is displayed large. A banner logo (1600 × 400) or a
 * portrait one (640 × 960) is accepted as soon as its longest side is enough.
 */
final class MinImageSide implements ValidationRule
{
    public function __construct(private readonly int $pixels) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $size = @getimagesize((string) $value->getRealPath());
        // Not a readable image: the file type rule handles it.
        if ($size === false) {
            return;
        }

        if (max((int) $size[0], (int) $size[1]) < $this->pixels) {
            $fail('form::validation.image_min_side')->translate(['pixels' => (string) $this->pixels]);
        }
    }
}
