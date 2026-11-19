<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * L'allemand (décision 2026-11-19-contrat-langue-saisie-allemand) : chaque texte du
 * projet en français a son équivalent allemand, et l'app reçoit ses textes en allemand.
 */
class GermanTranslationsTest extends TestCase
{
    /**
     * @param  array<int|string, mixed>  $texts
     * @return list<string>
     */
    private function keys(array $texts, string $prefix = ''): array
    {
        $keys = [];
        foreach ($texts as $key => $value) {
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            $keys = [...$keys, ...(is_array($value) && ! array_is_list($value) ? $this->keys($value, $path) : [$path])];
        }

        return $keys;
    }

    public function test_every_french_text_has_a_german_one(): void
    {
        $files = [...glob(base_path('lang/fr/*.php')), ...glob(base_path('Modules/*/lang/fr/*.php'))];
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $german = str_replace('/fr/', '/de/', $file);
            $this->assertFileExists($german);
            $this->assertSame([], array_values(array_diff($this->keys(require $file), $this->keys(require $german))), "Textes allemands manquants dans {$german}");
        }
    }

    public function test_the_app_receives_its_texts_in_german(): void
    {
        config(['app.supported_locales' => ['fr', 'en', 'de']]);

        $messages = $this->withHeader('Accept-Language', 'de')->getJson('/api/v1/messages')->assertOk()
            ->assertJsonPath('data.locale', 'de')
            ->json('data.messages');
        $this->assertSame('Regeln', $messages['offer.rules']);
        $this->assertSame('Weiter', $messages['form.next']);
    }
}
