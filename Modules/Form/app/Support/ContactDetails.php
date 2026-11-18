<?php

namespace Modules\Form\Support;

use App\Support\Locales;
use Illuminate\Support\Str;

/**
 * Repère une coordonnée ou un indice de contact dans un texte (décision
 * 2026-11-19-contrat-annonces-sans-coordonnees) : téléphone, chiffres écrits en
 * lettres, e-mail (même masqué), lien ou site, réseau social, formule d'appel,
 * pseudonyme « @… ». Les listes de mots viennent de `form::contact`, toutes
 * langues réunies : un texte peut mélanger les langues.
 */
final class ContactDetails
{
    /** Chiffres à partir desquels une suite est un téléphone. */
    public const PHONE_DIGITS = 8;

    /** Chiffres écrits en lettres à la suite à partir desquels c'est un numéro. */
    public const SPELLED_DIGITS = 7;

    /** Extensions de domaine reconnues dans un site ou une adresse masquée. */
    private const TLDS = 'com|net|org|info|biz|fr|cm|ch|be|ca|io|me|africa|online|site|shop|app|co\.uk';

    /**
     * Le genre de coordonnée trouvée (`phone`, `spelled_phone`, `email`, `link`,
     * `network`, `phrase`, `handle`), ou `null`.
     */
    public static function find(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        $text = self::normalize($text);

        return match (true) {
            self::hasEmail($text) => 'email',
            self::hasLink($text) => 'link',
            self::hasHandle($text) => 'handle',
            self::hasPhone($text) => 'phone',
            self::hasSpelledPhone($text) => 'spelled_phone',
            self::hasWord($text, self::words('networks')) => 'network',
            self::hasWord($text, self::words('phrases')) => 'phrase',
            default => null,
        };
    }

    /**
     * Minuscules, sans accents, tirets et apostrophes changés en espaces.
     */
    private static function normalize(string $text): string
    {
        $text = mb_strtolower(Str::ascii($text));

        return (string) preg_replace(['/[\x{2010}-\x{2015}\'’]/u', '/\s+/'], [' ', ' '], $text);
    }

    private static function hasEmail(string $text): bool
    {
        if (preg_match('/[a-z0-9._%+\-]+@[a-z0-9\-]+(\.[a-z0-9\-]+)*\.[a-z]{2,}/', $text) === 1) {
            return true;
        }

        // Masquée : « awa arobase gmail point com », « awa (at) gmail (dot) com ».
        $at = implode('|', [...array_map(fn (string $word): string => '\b'.preg_quote($word, '/').'\b', self::words('at_words')), '\(at\)', '\[at\]', '\(a\)']);
        $dot = implode('|', [...array_map(fn (string $word): string => '\b'.preg_quote($word, '/').'\b', self::words('dot_words')), '\.', '\(dot\)', '\[dot\]']);

        return preg_match('/[a-z0-9._%+\-]+\s*(?:'.$at.')\s*[a-z0-9\-]+\s*(?:'.$dot.')\s*(?:'.self::TLDS.')\b/', $text) === 1;
    }

    private static function hasLink(string $text): bool
    {
        return preg_match('/https?:\/\/|\bwww\s*\.|\b[a-z0-9\-]+\.(?:'.self::TLDS.')\b/', $text) === 1;
    }

    /**
     * « @awa_ngono », sans partie locale devant (ce serait un e-mail).
     */
    private static function hasHandle(string $text): bool
    {
        return preg_match('/(?:^|[^a-z0-9._%+\-])@[a-z0-9_.]{2,}/', $text) === 1;
    }

    /**
     * Au moins 8 chiffres à la suite, séparés ou non par des espaces, points,
     * tirets, parenthèses, barres obliques ou soulignés (un « O » contre un chiffre
     * compte pour un zéro) ; sauf un montant suivi d'une devise et une date.
     */
    private static function hasPhone(string $text): bool
    {
        // La lettre O tapée pour un zéro, contre un chiffre : « 6O 9O 12 34 56 ».
        $text = (string) preg_replace('/(?<=\d)o|o(?=\d)/', '0', $text);
        preg_match_all('/\+?\d[\d\s.\-()\/_]*\d/', $text, $runs, PREG_OFFSET_CAPTURE);
        $currencies = implode('|', array_map(fn (string $word): string => preg_quote($word, '/'), self::words('currencies')));

        foreach ($runs[0] as [$run, $offset]) {
            if (strlen((string) preg_replace('/\D/', '', $run)) < self::PHONE_DIGITS) {
                continue;
            }

            // Une date (« 01.12.2026 », « 2026-2027 ») n'est pas un téléphone.
            if (preg_match('/^\s*(\d{1,2}\s*[.\-\s\/]\s*\d{1,2}\s*[.\-\s\/]\s*\d{2,4}|(19|20)\d\d\s*[\-\/]\s*(19|20)\d\d)\s*$/', $run) === 1) {
                continue;
            }

            // Un montant : une devise juste après (« 250 000 000 FCFA »).
            $after = substr($text, $offset + strlen($run), 12);
            if ($currencies !== '' && preg_match('/^\s*(?:'.$currencies.')\b/', $after) === 1) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * « six neuf neuf un deux trois quatre » : 7 chiffres ou plus écrits en lettres
     * (ou mêlés de chiffres) à la suite.
     */
    private static function hasSpelledPhone(string $text): bool
    {
        $words = array_flip(self::words('digit_words'));
        $count = 0;
        $spelled = false;

        foreach (preg_split('/[^a-z0-9]+/', $text) ?: [] as $token) {
            if ($token === '') {
                continue;
            }

            if (isset($words[$token])) {
                $count++;
                $spelled = true;
            } elseif (ctype_digit($token)) {
                $count += strlen($token);
            } else {
                $count = 0;
                $spelled = false;
            }

            if ($spelled && $count >= self::SPELLED_DIGITS) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $words
     */
    private static function hasWord(string $text, array $words): bool
    {
        foreach ($words as $word) {
            if (preg_match('/(?<![a-z0-9])'.preg_quote($word, '/').'(?![a-z0-9])/', $text) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Une liste de `form::contact`, toutes langues réunies, normalisée.
     *
     * @return list<string>
     */
    private static function words(string $key): array
    {
        $words = [];

        foreach (Locales::values() as $locale) {
            $list = trans('form::contact.'.$key, [], $locale);
            foreach (is_array($list) ? $list : [] as $word) {
                $words[] = self::normalize((string) $word);
            }
        }

        return array_values(array_unique(array_filter($words, fn (string $word): bool => trim($word) !== '')));
    }
}
