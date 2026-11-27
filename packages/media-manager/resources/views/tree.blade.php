{{--
    The folder tree, beside the listing. Styled inline: the panel's compiled
    Tailwind does not scan the package's views.
--}}
<nav class="mm-tree" aria-label="{{ __('media-manager::media-manager.tree') }}">
    <style>
        .mm-tree { font-size: .875rem; line-height: 1.25rem; }
        .mm-tree ul { list-style: none; margin: 0; padding: 0; }
        .mm-tree ul ul { padding-inline-start: .875rem; }
        .mm-tree .mm-row { display: flex; align-items: center; gap: .125rem; }
        .mm-tree .mm-toggle { display: inline-flex; width: 1.25rem; height: 1.25rem; flex: none; align-items: center; justify-content: center; border-radius: .25rem; color: var(--gray-400); }
        .mm-tree .mm-toggle:hover { background: var(--gray-100); }
        .mm-tree .mm-toggle svg { width: .875rem; height: .875rem; transition: transform .15s; }
        .mm-tree .mm-toggle[aria-expanded="true"] svg { transform: rotate(90deg); }
        .mm-tree .mm-node { display: flex; flex: 1; min-width: 0; align-items: center; gap: .5rem; padding: .3rem .5rem; border-radius: .5rem; text-align: start; color: var(--gray-700); }
        .mm-tree .mm-node:hover { background: var(--gray-100); }
        .mm-tree .mm-node[aria-current="page"] { background: var(--primary-50); color: var(--primary-700); font-weight: 600; }
        .mm-tree .mm-node svg { width: 1.25rem; height: 1.25rem; flex: none; color: var(--primary-500); }
        .mm-tree .mm-node.mm-locked svg { color: var(--gray-400); }
        .mm-tree .mm-label { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .mm-tree .mm-spacer { width: 1.25rem; flex: none; }
        .dark .mm-tree .mm-node { color: var(--gray-200); }
        .dark .mm-tree .mm-node:hover, .dark .mm-tree .mm-toggle:hover { background: rgb(255 255 255 / .05); }
        .dark .mm-tree .mm-node[aria-current="page"] { background: rgb(255 255 255 / .08); color: var(--primary-400); }
    </style>

    <ul>
        @include('media-manager::partials.tree-node', ['node' => $root])
    </ul>
</nav>
