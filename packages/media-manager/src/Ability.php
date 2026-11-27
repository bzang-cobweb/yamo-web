<?php

namespace Bza\MediaManager;

/**
 * What a right allows on a folder. Each one is given or refused on its own,
 * and is inherited by the subfolders unless they give or refuse it too.
 */
enum Ability: string
{
    /** Open the folder: its subfolders, its files and their preview. */
    case View = 'view';

    /** Download the files and copy their address. */
    case Download = 'download';

    /** Upload files and create subfolders. */
    case Create = 'create';

    /** Rename and move the folder and its files. */
    case Update = 'update';

    /** Delete the folder (when empty) and its files. */
    case Delete = 'delete';

    /** Set the rights of the folder. */
    case Manage = 'manage';

    public function label(): string
    {
        return __('media-manager::media-manager.abilities.'.$this->value);
    }

    /**
     * The abilities that mean something on the folders of the media attached
     * to other models: their files are read-only, "update" edits their metadata.
     *
     * @return list<self>
     */
    public static function forLinkedMedia(): array
    {
        return [self::View, self::Download, self::Update, self::Manage];
    }
}
