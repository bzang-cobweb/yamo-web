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
        'metadata' => 'Informationen',
        'save' => 'Speichern',
        'close' => 'Schließen',
        'grid' => 'Raster',
        'list' => 'Liste',
        'open' => 'Öffnen',
        'new_folder' => 'Neuer Ordner',
        'upload' => 'Hochladen',
        'rename' => 'Umbenennen',
        'duplicate' => 'Duplizieren',
        'move' => 'Verschieben',
        'delete' => 'Löschen',
        'download' => 'Herunterladen',
        'copy_url' => 'Adresse kopieren',
        'copied' => 'Adresse kopiert',
        'preview' => 'Vorschau',
        'permissions' => 'Rechte',
        'root_permissions' => 'Rechte der Wurzel',
    ],

    'metadata' => [
        'file' => 'Datei',
        'type' => 'Typ',
        'size' => 'Größe',
        'dimensions' => 'Abmessungen',
        'created_at' => 'Hinzugefügt am',
        'updated_at' => 'Geändert am',
        'owner' => 'Gehört zu',
        'alt' => 'Alternativtext',
        'alt_help' => 'Beschreibt das Bild für alle, die es nicht sehen (Screenreader, Suchmaschinen).',
        'title' => 'Titel',
        'caption' => 'Bildunterschrift',
        'credit' => 'Bildnachweis',
        'texts_tab' => 'Texte',
        'credit_tab' => 'Nachweis und Weiteres',
        'credit_placeholder' => '© Urheber, Quelle, Lizenz',
        'extra' => 'Weitere Informationen',
        'key' => 'Schlüssel',
        'value' => 'Wert',
        'add_pair' => 'Information hinzufügen',
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
        'duplicate_name' => 'Der Name der Kopie; ein bereits vergebener Name erhält eine Nummer.',
        'permissions' => 'Jedes Recht gilt für diesen Ordner und seine Unterordner. „Geerbt“ übernimmt den übergeordneten Ordner; ein Unterordner mit „Ja“ oder „Nein“ hat für dieses Recht Vorrang vor seinen übergeordneten Ordnern. Es genügt, dass eine der Rollen des Benutzers das Recht hat.',
        'linked_permissions' => 'Die Medien der Inhalte erben die Rechte der Wurzel nicht: Nur die hier vergebenen Rechte zählen. „Bearbeiten“ erlaubt, ihre Informationen (Alternativtext, Bildnachweis…) zu ändern, nie die Dateien.',
    ],

    'confirm' => [
        'delete_folder' => 'Den Ordner „:name“ löschen? Nur ein leerer Ordner kann gelöscht werden.',
        'delete_file' => 'Die Datei „:name“ löschen? Dies kann nicht rückgängig gemacht werden.',
    ],

    'notifications' => [
        'folder_created' => 'Ordner erstellt',
        'renamed' => 'Umbenannt',
        'duplicated' => 'Dupliziert',
        'moved' => 'Verschoben',
        'deleted' => 'Gelöscht',
        'uploaded' => '{1} 1 Datei hochgeladen|[2,*] :count Dateien hochgeladen',
        'metadata_saved' => 'Informationen gespeichert',
        'permissions_saved' => 'Rechte gespeichert',
    ],

    'errors' => [
        'forbidden' => 'Sie dürfen das hier nicht tun.',
        'name_required' => 'Der Name ist erforderlich.',
        'name_invalid' => 'Dieser Name ist ungültig: kein „/“ oder „\\“, höchstens 200 Zeichen.',
        'name_taken' => '„:name“ existiert bereits in diesem Ordner.',
        'duplicate_into_itself' => 'Ein Ordner kann nicht in sich selbst oder in einen seiner Unterordner dupliziert werden.',
        'file_missing' => 'Die Datei ist auf dem Datenträger nicht auffindbar.',
        'move_into_itself' => 'Ein Ordner kann nicht in sich selbst oder in einen seiner Unterordner verschoben werden.',
        'folder_not_empty' => 'Dieser Ordner ist nicht leer: Verschieben oder löschen Sie zuerst seinen Inhalt.',
        'too_large' => 'Die Datei überschreitet die maximale Größe (:size).',
        'metadata_key_invalid' => 'Ein Schlüssel hat höchstens 100 Zeichen.',
        'read_only' => 'Die Medien der Inhalte können nur gelesen werden.',
    ],
];
