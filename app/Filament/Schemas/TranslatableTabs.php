<?php

namespace App\Filament\Schemas;

use Bza\Form\Support\Locales;
use Closure;
use Filament\Forms\Components\Field;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * Un champ (ou un groupe de champs) traduisible du back-office : un onglet par
 * langue de l'application (« Français », « English »), à l'endroit du champ.
 * Remplace le sélecteur de langue de page (décision
 * 2026-11-19-traduction-par-champ-filament).
 *
 * Chaque champ est nommé `<attribut>.<langue>` (`name.fr`) : l'état du formulaire
 * porte toutes les traductions (`['name' => ['fr' => …, 'en' => …]]`), que
 * spatie/laravel-translatable lit et écrit d'un bloc.
 *
 * ```php
 * TranslatableTabs::make(fn (string $locale): array => [
 *     TextInput::make("name.{$locale}")->label(__('…'))->required(),
 * ])
 * ```
 */
final class TranslatableTabs
{
    /**
     * @param  Closure(string $locale): array<Component|Field>  $fields  champs d'une langue
     */
    public static function make(Closure $fields, string $key = 'translations'): Tabs
    {
        return Tabs::make($key)
            ->key($key)
            ->contained(true)
            ->tabs(array_map(
                fn (string $locale): Tab => Tab::make(Locales::label($locale))
                    ->key("{$key}-{$locale}")
                    ->schema($fields($locale)),
                Locales::values(),
            ));
    }
}
