<?php

namespace Bza\ConfigViewer\Contracts;

use Bza\ConfigViewer\Data\ConfigFile;

/**
 * Tells which files feed the configuration. A source only attributes files to
 * keys: values are always read from the configuration repository, so the viewer
 * shows the effective configuration (cached, merged, overridden at runtime).
 */
interface ConfigSource
{
    /**
     * @return iterable<ConfigFile>
     */
    public function files(): iterable;
}
