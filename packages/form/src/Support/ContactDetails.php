<?php

namespace Bza\Form\Support;

use Bza\Form\Support\Locales;
use Illuminate\Support\Str;

/**
 * Detects contact details or contact hints in a text (decision
 * 2026-11-19-contrat-annonces-sans-coordonnees): phone number, digits spelled out
 * in words, e-mail (even obfuscated), link or website, social network, call-to-action
 * phrase, « @… » handle. The word lists come from `form::contact`, all languages
 * combined: a text may mix languages.
 */
final class ContactDetails
{
    /** Number of digits from which a sequence counts as a phone number. */
    public const PHONE_DIGITS = 8;

    /** Number of consecutive spelled-out digits from which it counts as a number. */
    public const SPELLED_DIGITS = 7;

    /** Domain extensions recognized in a website or an obfuscated address. */
    private const TLDS = 'com|net|org|info|biz|fr|cm|ch|be|ca|io|me|africa|online|site|shop|app|co\.uk';

    /**
     * The kind of contact detail found (`phone`, `spelled_phone`, `email`, `link`,
     * `network`, `phrase`, `handle`), or `null`.
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
     * Lowercase, without accents, dashes and apostrophes turned into spaces.
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

        // Obfuscated: « awa arobase gmail point com », « awa (at) gmail (dot) com ».
        $at = implode('|', [...array_map(fn (string $word): string => '\b'.preg_quote($word, '/').'\b', self::words('at_words')), '\(at\)', '\[at\]', '\(a\)']);
        $dot = implode('|', [...array_map(fn (string $word): string => '\b'.preg_quote($word, '/').'\b', self::words('dot_words')), '\.', '\(dot\)', '\[dot\]']);

        return preg_match('/[a-z0-9._%+\-]+\s*(?:'.$at.')\s*[a-z0-9\-]+\s*(?:'.$dot.')\s*(?:'.self::TLDS.')\b/', $text) === 1;
    }

    private static function hasLink(string $text): bool
    {
        return preg_match('/https?:\/\/|\bwww\s*\.|\b[a-z0-9\-]+\.(?:'.self::TLDS.')\b/', $text) === 1;
    }

    /**
     * « @awa_ngono », with no local part before it (that would be an e-mail).
     */
    private static function hasHandle(string $text): bool
    {
        return preg_match('/(?:^|[^a-z0-9._%+\-])@[a-z0-9_.]{2,}/', $text) === 1;
    }

    /**
     * At least 8 consecutive digits, optionally separated by spaces, dots, dashes,
     * parentheses, slashes or underscores (an « O » next to a digit counts as a
     * zero); except an amount followed by a currency, and a date.
     */
    private static function hasPhone(string $text): bool
    {
        // The letter O typed for a zero, next to a digit: « 6O 9O 12 34 56 ».
        $text = (string) preg_replace('/(?<=\d)o|o(?=\d)/', '0', $text);
        preg_match_all('/\+?\d[\d\s.\-()\/_]*\d/', $text, $runs, PREG_OFFSET_CAPTURE);
        $currencies = implode('|', array_map(fn (string $word): string => preg_quote($word, '/'), self::words('currencies')));

        foreach ($runs[0] as [$run, $offset]) {
            if (strlen((string) preg_replace('/\D/', '', $run)) < self::PHONE_DIGITS) {
                continue;
            }

            // A date (« 01.12.2026 », « 2026-2027 ») is not a phone number.
            if (preg_match('/^\s*(\d{1,2}\s*[.\-\s\/]\s*\d{1,2}\s*[.\-\s\/]\s*\d{2,4}|(19|20)\d\d\s*[\-\/]\s*(19|20)\d\d)\s*$/', $run) === 1) {
                continue;
            }

            // An amount: a currency right after (« 250 000 000 FCFA »).
            $after = substr($text, $offset + strlen($run), 12);
            if ($currencies !== '' && preg_match('/^\s*(?:'.$currencies.')\b/', $after) === 1) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * « six neuf neuf un deux trois quatre »: 7 or more consecutive digits spelled
     * out in words (or mixed with digits).
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
     * A list from `form::contact`, all languages combined, normalized.
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
