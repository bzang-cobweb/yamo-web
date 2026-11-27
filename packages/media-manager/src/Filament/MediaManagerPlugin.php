<?php

namespace Bza\MediaManager\Filament;

use BackedEnum;
use Bza\MediaManager\Filament\Pages\MediaManagerPage;
use Bza\MediaManager\LinkedMedia;
use Bza\MediaManager\MediaAccess;
use Closure;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Concerns\EvaluatesClosures;
use UnitEnum;

/**
 * Filament adapter: adds the media manager page to a panel.
 *
 *     $panel->plugin(MediaManagerPlugin::make()
 *         ->navigationGroup('Content')
 *         ->superUser(fn (User $user): bool => $user->isSuperAdmin()));
 *
 * The page shows to the users the per-folder rights let in. Every option
 * accepts a closure, evaluated when the panel renders, so labels can be
 * translated in the current locale.
 */
class MediaManagerPlugin implements Plugin
{
    use EvaluatesClosures;

    protected bool|Closure $authorized = true;

    protected string|UnitEnum|Closure|null $navigationGroup = null;

    protected string|BackedEnum|Closure|null $navigationIcon = 'heroicon-o-folder';

    protected int|Closure|null $navigationSort = null;

    protected string|Closure|null $navigationLabel = null;

    protected string $slug = 'media';

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
        return 'media-manager';
    }

    public function register(Panel $panel): void
    {
        $panel->pages([MediaManagerPage::class]);
    }

    public function boot(Panel $panel): void {}

    /**
     * Who may do anything in the manager, whatever the rights.
     *
     * @param  Closure(mixed): bool|null  $callback
     */
    public function superUser(?Closure $callback): static
    {
        MediaAccess::bypassUsing($callback);

        return $this;
    }

    /**
     * An extra condition to open the page, on top of the per-folder rights.
     */
    public function authorize(bool|Closure $condition): static
    {
        $this->authorized = $condition;

        return $this;
    }

    public function isAuthorized(): bool
    {
        if (! $this->evaluate($this->authorized)) {
            return false;
        }

        return app(MediaAccess::class)->hasAnyAccess(auth()->user(), array_keys(app(LinkedMedia::class)->types()));
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
        return $this->evaluate($this->navigationLabel) ?? __('media-manager::media-manager.title');
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
