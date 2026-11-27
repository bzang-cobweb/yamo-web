<?php

namespace Bza\ConfigViewer;

/**
 * Where a configuration group comes from.
 */
enum Origin: string
{
    /** A file in the application's config directory. */
    case Application = 'application';

    /** A file in a module's config directory. */
    case Module = 'module';

    /** A key with no known file: merged at runtime by a package or set in code. */
    case Package = 'package';

    public function label(): string
    {
        return __('config-viewer::config-viewer.origins.'.$this->value);
    }
}
