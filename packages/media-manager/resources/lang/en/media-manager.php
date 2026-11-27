<?php

return [
    'title' => 'Media',
    'root' => 'Media',
    'read_only' => 'Read-only',
    'files_count' => '{0} Empty|{1} 1 file|[2,*] :count files',
    'items_count' => '{0} Empty|{1} 1 item|[2,*] :count items',
    'attached_to' => 'Attached to: :owner',
    'empty' => 'This folder is empty.',

    'kinds' => [
        'folder' => 'Folder',
        'linked' => 'Content media',
        'collection' => 'Collection',
    ],

    'columns' => [
        'name' => 'Name',
        'preview' => 'Preview',
        'type' => 'Type',
        'size' => 'Size',
        'updated_at' => 'Modified',
    ],

    'abilities' => [
        'view' => 'View',
        'download' => 'Download',
        'create' => 'Add',
        'update' => 'Edit',
        'delete' => 'Delete',
        'manage' => 'Manage rights',
    ],

    'states' => [
        'inherit' => 'Inherited',
        'allow' => 'Yes',
        'deny' => 'No',
    ],

    'actions' => [
        'new_folder' => 'New folder',
        'upload' => 'Upload',
        'rename' => 'Rename',
        'move' => 'Move',
        'delete' => 'Delete',
        'download' => 'Download',
        'copy_url' => 'Copy address',
        'copied' => 'Address copied',
        'preview' => 'Preview',
        'permissions' => 'Rights',
        'root_permissions' => 'Root rights',
    ],

    'fields' => [
        'name' => 'Name',
        'files' => 'Files',
        'destination' => 'Destination',
        'grants' => 'Rights',
        'grantee' => 'Role or user',
        'add_grant' => 'Add a role or a user',
    ],

    'help' => [
        'permissions' => 'Each right applies to this folder and its subfolders. "Inherited" follows the parent folder; a subfolder saying "Yes" or "No" overrides its parents for that right. One of the user\'s roles having the right is enough.',
        'linked_permissions' => 'Content media do not inherit the root\'s rights: only the rights given here count.',
    ],

    'confirm' => [
        'delete_folder' => 'Delete the folder ":name"? Only an empty folder can be deleted.',
        'delete_file' => 'Delete the file ":name"? This cannot be undone.',
    ],

    'notifications' => [
        'folder_created' => 'Folder created',
        'renamed' => 'Renamed',
        'moved' => 'Moved',
        'deleted' => 'Deleted',
        'uploaded' => '{1} 1 file uploaded|[2,*] :count files uploaded',
        'permissions_saved' => 'Rights saved',
    ],

    'errors' => [
        'forbidden' => 'You are not allowed to do this here.',
        'name_required' => 'The name is required.',
        'name_invalid' => 'This name is not valid: no "/" or "\\", 200 characters at most.',
        'name_taken' => '":name" already exists in this folder.',
        'move_into_itself' => 'A folder cannot be moved into itself or one of its subfolders.',
        'folder_not_empty' => 'This folder is not empty: move or delete its content first.',
        'too_large' => 'The file exceeds the maximum size (:size).',
        'read_only' => 'Content media are read-only.',
    ],
];
