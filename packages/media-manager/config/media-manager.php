<?php

use Bza\MediaManager\Support\FolderPathGenerator;
use Bza\MediaManager\Support\UserAndRolesGranteeResolver;

return [

    /*
    | The filesystem disk of the files uploaded in the manager. Null means
    | the media library's default disk (media-library.disk_name).
    */
    'disk' => env('MEDIA_MANAGER_DISK'),

    /*
    | The media collection that holds a folder's files.
    */
    'collection' => 'files',

    /*
    | Where the uploaded files are stored on the disk. The generator is
    | registered for the folders only (media-library.custom_path_generators):
    | the files of the other models keep the application's generator. Null
    | keeps the application's generator for the folders too.
    */
    'path_generator' => FolderPathGenerator::class,

    /*
    | The directory, at the root of the disk, of the uploaded files.
    */
    'directory' => 'media-manager',

    /*
    | The largest upload, in bytes. Null means media-library.max_file_size.
    */
    'max_file_size' => null,

    /*
    | The accepted MIME types ("image/*", "application/pdf"). Empty: all.
    */
    'accepted_file_types' => [],

    /*
    | Lists the grantees of a user: the user and the roles it belongs to.
    | A class implementing Bza\MediaManager\Contracts\GranteeResolver.
    */
    'grantee_resolver' => UserAndRolesGranteeResolver::class,

    /*
    | Who rights can be given to: model class => attribute shown in the lists.
    | Null means the roles of spatie/laravel-permission (when installed) and
    | the users of the default authentication guard.
    */
    'grantees' => null,

    /*
    | Show the media attached to the other models (photos, avatars…) as
    | read-only folders, one per model type, then one per collection.
    */
    'linked_media' => true,

];
