@php($hasChildren = $node['children'] !== [])
<li x-data="{ expanded: @js($node['expanded']) }" wire:key="mm-tree-{{ $node['key'] }}">
    <div class="mm-row">
        @if ($hasChildren)
            <button type="button" class="mm-toggle" x-on:click="expanded = ! expanded" x-bind:aria-expanded="expanded" aria-label="{{ $node['label'] }}">
                {{ \Filament\Support\generate_icon_html('heroicon-m-chevron-right') }}
            </button>
        @else
            <span class="mm-spacer"></span>
        @endif

        <button
            type="button"
            @class(['mm-node', 'mm-locked' => $node['read_only']])
            wire:click="navigate(@js($node['folder']), @js($node['linked']), @js($node['collection']))"
            @if ($node['active']) aria-current="page" @endif
        >
            {{ \Filament\Support\generate_icon_html($node['active'] && ! $node['read_only'] ? 'heroicon-s-folder-open' : $node['icon']) }}
            <span class="mm-label">{{ $node['label'] }}</span>
        </button>
    </div>

    @if ($hasChildren)
        <ul x-show="expanded" x-cloak>
            @foreach ($node['children'] as $child)
                @include('media-manager::partials.tree-node', ['node' => $child])
            @endforeach
        </ul>
    @endif
</li>
