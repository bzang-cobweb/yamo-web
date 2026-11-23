<?php

namespace Modules\Form\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Une image envoyée mesure au moins `$pixels` sur son plus grand côté (décision 2026-11-24-logo-taille-minimale) :
 * plus petite, elle serait agrandie, donc floue, là où elle s'affiche en grand. Un logo en bandeau (1600 × 400) ou en
 * hauteur (640 × 960) est accepté dès que son grand côté suffit.
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
        // Pas une image lisible : la règle du type de fichier s'en charge.
        if ($size === false) {
            return;
        }

        if (max((int) $size[0], (int) $size[1]) < $this->pixels) {
            $fail('form::validation.image_min_side')->translate(['pixels' => (string) $this->pixels]);
        }
    }
}
