<?php

namespace Bza\MediaManager;

use RuntimeException;

/**
 * An operation the manager refuses: the message is translated and meant for
 * the person who asked for it.
 */
class MediaManagerException extends RuntimeException
{
    /**
     * @param  array<string, string>  $replace
     */
    public static function because(string $reason, array $replace = []): self
    {
        return new self(__('media-manager::media-manager.errors.'.$reason, $replace));
    }
}
