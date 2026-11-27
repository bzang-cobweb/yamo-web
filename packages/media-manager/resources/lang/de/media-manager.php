<?php

return [
    'title' => 'Medien',
    'root' => 'Medien',
    'read_only' => 'Nur Lesen',
    'files_count' => '{0} Leer|{1} 1 Datei|[2,*] :count Dateien',
    'items_count' => '{0} Leer|{1} 1 Element|[2,*] :count Elemente',
    'attached_to' => 'Gehört zu: :owner',
    'empty' => 'Dieser Ordner ist leer.',

    'kinds' => [
        'folder' => 'Ordner',
        'linked' => 'Medien der Inhalte',
        'collection' => 'Sammlung',
    ],

    'columns' => [
        'name' => 'Name',
        'preview' => 'Vorschau',
        'type' => 'Typ',
        'size' => 'Größe',
        'updated_at' => 'Geändert am',
    ],

    'abilities' => [
        'view' => 'Ansehen',
        'download' => 'Herunterladen',
        'create' => 'Hinzufügen',
        'update' => 'Bearbeiten',
        'delete' => 'Löschen',
        'manage' => 'Rechte verwalten',
    ],

    'states' => [
        'inherit' => 'Geerbt',
        'allow' => 'Ja',
        'deny' => 'Nein',
    ],

    'tree' => 'Ordner',

    'actions' => [
        'grid' => 'Raster',
        'list' => 'Liste',
        'open' => 'Öffnen',
        'new_folder' => 'Neuer Ordner',
        'upload' => 'Hochladen',
        'rename' => 'Umbenennen',
        'move' => 'Verschieben',
        'delete' => 'Löschen',
        'download' => 'Herunterladen',
        'copy_url' => 'Adresse kopieren',
        'copied' => 'Adresse kopiert',
        'preview' => 'Vorschau',
        'permissions' => 'Rechte',
        'root_permissions' => 'Rechte der Wurzel',
    ],

    'fields' => [
        'name' => 'Name',
        'files' => 'Dateien',
        'destination' => 'Ziel',
        'grants' => 'Rechte',
        'grantee' => 'Rolle oder Benutzer',
        'add_grant' => 'Rolle oder Benutzer hinzufügen',
    ],

    'help' => [
        'permissions' => 'Jedes Recht gilt für diesen Ordner und seine Unterordner. „Geerbt“ übernimmt den übergeordneten Ordner; ein Unterordner mit „Ja“ oder „Nein“ hat für dieses Recht Vorrang vor seinen übergeordneten Ordnern. Es genügt, dass eine der Rollen des Benutzers das Recht hat.',
        'linked_permissions' => 'Die Medien der Inhalte erben die Rechte der Wurzel nicht: Nur die hier vergebenen Rechte zählen.',
    ],

    'confirm' => [
        'delete_folder' => 'Den Ordner „:name“ löschen? Nur ein leerer Ordner kann gelöscht werden.',
        'delete_file' => 'Die Datei „:name“ löschen? Dies kann nicht rückgängig gemacht werden.',
    ],

    'notifications' => [
        'folder_created' => 'Ordner erstellt',
        'renamed' => 'Umbenannt',
        'moved' => 'Verschoben',
        'deleted' => 'Gelöscht',
        'uploaded' => '{1} 1 Datei hochgeladen|[2,*] :count Dateien hochgeladen',
        'permissions_saved' => 'Rechte gespeichert',
    ],

    'errors' => [
        'forbidden' => 'Sie dürfen das hier nicht tun.',
        'name_required' => 'Der Name ist erforderlich.',
        'name_invalid' => 'Dieser Name ist ungültig: kein „/“ oder „\\“, höchstens 200 Zeichen.',
        'name_taken' => '„:name“ existiert bereits in diesem Ordner.',
        'move_into_itself' => 'Ein Ordner kann nicht in sich selbst oder in einen seiner Unterordner verschoben werden.',
        'folder_not_empty' => 'Dieser Ordner ist nicht leer: Verschieben oder löschen Sie zuerst seinen Inhalt.',
        'too_large' => 'Die Datei überschreitet die maximale Größe (:size).',
        'read_only' => 'Die Medien der Inhalte können nur gelesen werden.',
    ],
];
