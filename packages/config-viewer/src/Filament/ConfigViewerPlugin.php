<?php

namespace Bza\ConfigViewer\Filament;

use BackedEnum;
use Bza\ConfigViewer\Filament\Pages\ConfigViewerPage;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use UnitEnum;

/**
 * Filament adapter: adds the configuration page to a panel.
 *
 *     $panel->plugin(ConfigViewerPlugin::make()
 *         ->navigationGroup('Settings')
 *         ->authorize(fn (): bool => auth()->user()->isSuperAdmin()));
 *
 * Every option accepts a closure, evaluated when the panel renders, so labels
 * can be translated in the current locale.
 */
class ConfigViewerPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool|Closure $authorized = true;

    protected string|UnitEnum|Closure|null $navigationGroup = null;

    protected string|BackedEnum|Closure|null $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected int|Closure|null $navigationSort = null;

    protected string|Closure|null $navigationLabel = null;

    protected string $slug = 'configuration';

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static */
        return filament(app(static::class)->getId());
    }

    public function getId(): string
    {
        return 'config-viewer';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([ConfigViewerPage::class]);
    }

    public function boot(Panel $panel): void {}

    /**
     * Who may open the page. Defaults to every user of the panel.
     */
    public function authorize(bool|Closure $condition): static
    {
        $this->authorized = $condition;

        return $this;
    }

    public function isAuthorized(): bool
    {
        return (bool) $this->evaluate($this->authorized);
    }

    public function navigationGroup(string|UnitEnum|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): string|UnitEnum|null
    {
        return $this->evaluate($this->navigationGroup);
    }

    public function navigationIcon(string|BackedEnum|Closure|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): string|BackedEnum|null
    {
        return $this->evaluate($this->navigationIcon);
    }

    public function navigationSort(int|Closure|null $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        return $this->evaluate($this->navigationSort);
    }

    public function navigationLabel(string|Closure|null $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): string
    {
        return $this->evaluate($this->navigationLabel) ?? __('config-viewer::config-viewer.title');
    }

    public function slug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
}
