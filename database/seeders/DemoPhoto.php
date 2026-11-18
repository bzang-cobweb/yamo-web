<?php

namespace Database\Seeders;

use GdImage;

/**
 * Photo de démonstration dessinée (GD, sans police ni téléchargement) : un ciel,
 * le sol et la silhouette du logement selon son type (immeuble, maison, studio,
 * chambre), en couleurs variées. Pour `DemoSeeder` seulement.
 */
final class DemoPhoto
{
    public const WIDTH = 1200;

    public const HEIGHT = 675;

    /** Couleurs de façade (RVB), tirées selon `$variant`. */
    private const WALLS = [[240, 214, 170], [214, 120, 90], [120, 170, 200], [190, 210, 150], [235, 235, 225], [200, 160, 210]];

    /** Couleurs de ciel (haut, bas), tirées selon `$variant`. */
    private const SKIES = [[[110, 170, 230], [200, 230, 250]], [[250, 170, 120], [255, 225, 190]], [[90, 120, 200], [180, 200, 240]]];

    public static function jpeg(string $type, int $variant): string
    {
        $image = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        [$top, $bottom] = self::SKIES[$variant % count(self::SKIES)];
        self::gradient($image, $top, $bottom, 0, (int) (self::HEIGHT * 0.72));
        // Sol.
        imagefilledrectangle($image, 0, (int) (self::HEIGHT * 0.72), self::WIDTH, self::HEIGHT, self::color($image, [110, 160, 90]));
        // Soleil.
        imagefilledellipse($image, 1020 - 40 * ($variant % 4), 130, 120, 120, self::color($image, [255, 220, 110]));

        $wall = self::WALLS[$variant % count(self::WALLS)];

        match ($type) {
            'apartment' => self::building($image, $wall, $variant),
            'house' => self::house($image, $wall, 760),
            'room' => self::room($image, $wall),
            default => self::house($image, $wall, 480),
        };

        ob_start();
        imagejpeg($image, null, 82);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    /**
     * @param  array{int, int, int}  $from
     * @param  array{int, int, int}  $to
     */
    private static function gradient(GdImage $image, array $from, array $to, int $y0, int $y1): void
    {
        for ($y = $y0; $y <= $y1; $y++) {
            $t = ($y - $y0) / max(1, $y1 - $y0);
            $rgb = array_map(fn (int $a, int $b): int => (int) round($a + ($b - $a) * $t), $from, $to);
            imageline($image, 0, $y, self::WIDTH, $y, self::color($image, $rgb));
        }
    }

    /**
     * Un immeuble de quelques étages, fenêtres en grille.
     *
     * @param  array{int, int, int}  $wall
     */
    private static function building(GdImage $image, array $wall, int $variant): void
    {
        $floors = 4 + $variant % 3;
        $x0 = 380;
        $x1 = 820;
        $base = (int) (self::HEIGHT * 0.72);
        // Étages de 60 px : six étages tiennent sous le ciel.
        $top = $base - $floors * 60 - 20;
        imagefilledrectangle($image, $x0, $top, $x1, $base, self::color($image, $wall));
        imagefilledrectangle($image, $x0 - 10, $top - 18, $x1 + 10, $top, self::color($image, [90, 90, 100]));

        for ($floor = 0; $floor < $floors; $floor++) {
            for ($column = 0; $column < 5; $column++) {
                $x = $x0 + 35 + $column * 82;
                $y = $top + 15 + $floor * 60;
                imagefilledrectangle($image, $x, $y, $x + 40, $y + 38, self::color($image, [170, 210, 235]));
                imagerectangle($image, $x, $y, $x + 40, $y + 38, self::color($image, [70, 70, 80]));
            }
        }

        imagefilledrectangle($image, 575, $base - 55, 625, $base, self::color($image, [110, 70, 50]));
    }

    /**
     * Une maison : murs, toit, porte et fenêtres ; `$width` la fait villa ou studio.
     *
     * @param  array{int, int, int}  $wall
     */
    private static function house(GdImage $image, array $wall, int $width): void
    {
        $base = (int) (self::HEIGHT * 0.72);
        $x0 = (int) ((self::WIDTH - $width) / 2);
        $x1 = $x0 + $width;
        $top = $base - 230;
        imagefilledrectangle($image, $x0, $top, $x1, $base, self::color($image, $wall));
        imagefilledpolygon($image, [$x0 - 30, $top, $x1 + 30, $top, (int) (($x0 + $x1) / 2), $top - 150], self::color($image, [170, 60, 50]));
        imagefilledrectangle($image, (int) (($x0 + $x1) / 2) - 35, $base - 120, (int) (($x0 + $x1) / 2) + 35, $base, self::color($image, [110, 70, 50]));

        foreach ([$x0 + 50, $x1 - 140] as $x) {
            imagefilledrectangle($image, $x, $top + 50, $x + 90, $top + 130, self::color($image, [170, 210, 235]));
            imagerectangle($image, $x, $top + 50, $x + 90, $top + 130, self::color($image, [70, 70, 80]));
        }
    }

    /**
     * Une chambre vue de l'intérieur : mur, sol, lit et fenêtre.
     *
     * @param  array{int, int, int}  $wall
     */
    private static function room(GdImage $image, array $wall): void
    {
        imagefilledrectangle($image, 0, 0, self::WIDTH, (int) (self::HEIGHT * 0.7), self::color($image, $wall));
        imagefilledrectangle($image, 0, (int) (self::HEIGHT * 0.7), self::WIDTH, self::HEIGHT, self::color($image, [170, 120, 80]));
        imagefilledrectangle($image, 760, 100, 1040, 320, self::color($image, [170, 210, 235]));
        imagerectangle($image, 760, 100, 1040, 320, self::color($image, [255, 255, 255]));
        // Lit.
        imagefilledrectangle($image, 180, 360, 640, 560, self::color($image, [240, 240, 245]));
        imagefilledrectangle($image, 180, 300, 260, 560, self::color($image, [120, 80, 60]));
        imagefilledrectangle($image, 280, 330, 420, 380, self::color($image, [255, 255, 255]));
        imagefilledrectangle($image, 300, 420, 640, 560, self::color($image, [90, 130, 190]));
    }

    /**
     * @param  array{int, int, int}  $rgb
     */
    private static function color(GdImage $image, array $rgb): int
    {
        return (int) imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
    }
}
