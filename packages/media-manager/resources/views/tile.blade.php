{{--
    One item of the grid: a large icon (a folder, a file type) or the image
    itself, then the name. Styled inline: the panel's compiled Tailwind does
    not scan the package's views.
--}}
@php
    $record = $getRecord();
    $isFolder = $record['kind'] !== 'file';
@endphp

<div class="mm-tile">
    <style>
        .mm-tile { display: flex; width: 100%; flex-direction: column; align-items: center; gap: .5rem; padding: .5rem .25rem; text-align: center; }
        .mm-tile .mm-visual { position: relative; display: flex; width: 100%; height: 6.5rem; align-items: center; justify-content: center; }
        /* A square thumbnail, the size of a folder icon, not the whole tile's width. */
        .mm-tile .mm-visual img { width: 6.5rem; max-width: 100%; height: 6.5rem; object-fit: cover; border-radius: .5rem; background: var(--gray-100); }
        .mm-tile .mm-visual > svg { width: 5rem; height: 5rem; color: var(--gray-400); }
        .mm-tile .mm-visual.mm-folder > svg { color: var(--primary-400); }
        .mm-tile .mm-visual.mm-folder.mm-locked > svg { color: var(--gray-300); }
        .mm-tile .mm-badge { position: absolute; inset-block-end: .6rem; inset-inline-start: 50%; translate: -50% 0; display: flex; width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; border-radius: 9999px; background: var(--gray-500); color: white; }
        .mm-tile .mm-badge svg { width: .9rem; height: .9rem; }
        .mm-tile .mm-name { display: -webkit-box; max-width: 100%; overflow: hidden; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow-wrap: anywhere; font-size: .875rem; font-weight: 500; line-height: 1.25rem; color: var(--gray-950); }
        .mm-tile .mm-meta { font-size: .75rem; line-height: 1rem; color: var(--gray-500); }
        .dark .mm-tile .mm-name { color: white; }
        .dark .mm-tile .mm-visual img { background: rgb(255 255 255 / .05); }
        .dark .mm-tile .mm-visual.mm-folder.mm-locked > svg { color: var(--gray-500); }
    </style>

    <div @class(['mm-visual', 'mm-folder' => $isFolder, 'mm-locked' => $record['read_only']])>
        @if ($record['thumbnail'] !== null)
            <img src="{{ $record['thumbnail'] }}" alt="" loading="lazy">
        @else
            {{ \Filament\Support\generate_icon_html($isFolder ? 'heroicon-s-folder' : $record['icon']) }}

            @if ($isFolder && $record['read_only'])
                <span class="mm-badge">{{ \Filament\Support\generate_icon_html('heroicon-s-lock-closed') }}</span>
            @endif
        @endif
    </div>

    <span class="mm-name" title="{{ $record['name'] }}">{{ $record['name'] }}</span>

    @if (filled($meta = $record['description'] ?? ($record['size'] !== null ? \Illuminate\Support\Number::fileSize($record['size']) : null)))
        <span class="mm-meta">{{ $meta }}</span>
    @endif
</div>
