<?php

return [
    'title' => 'Médias',
    'root' => 'Médias',
    'read_only' => 'Lecture seule',
    'files_count' => '{0} Vide|{1} 1 fichier|[2,*] :count fichiers',
    'items_count' => '{0} Vide|{1} 1 élément|[2,*] :count éléments',
    'attached_to' => 'Rattaché à : :owner',
    'empty' => 'Ce dossier est vide.',

    'kinds' => [
        'folder' => 'Dossier',
        'linked' => 'Médias des contenus',
        'collection' => 'Collection',
    ],

    'columns' => [
        'name' => 'Nom',
        'preview' => 'Aperçu',
        'type' => 'Type',
        'size' => 'Taille',
        'updated_at' => 'Modifié le',
    ],

    'abilities' => [
        'view' => 'Voir',
        'download' => 'Télécharger',
        'create' => 'Ajouter',
        'update' => 'Modifier',
        'delete' => 'Supprimer',
        'manage' => 'Gérer les droits',
    ],

    'states' => [
        'inherit' => 'Hérité',
        'allow' => 'Oui',
        'deny' => 'Non',
    ],

    'tree' => 'Dossiers',

    'actions' => [
        'open' => 'Ouvrir',
        'new_folder' => 'Nouveau dossier',
        'upload' => 'Téléverser',
        'rename' => 'Renommer',
        'move' => 'Déplacer',
        'delete' => 'Supprimer',
        'download' => 'Télécharger',
        'copy_url' => 'Copier l\'adresse',
        'copied' => 'Adresse copiée',
        'preview' => 'Aperçu',
        'permissions' => 'Droits',
        'root_permissions' => 'Droits de la racine',
    ],

    'fields' => [
        'name' => 'Nom',
        'files' => 'Fichiers',
        'destination' => 'Destination',
        'grants' => 'Droits',
        'grantee' => 'Rôle ou utilisateur',
        'add_grant' => 'Ajouter un rôle ou un utilisateur',
    ],

    'help' => [
        'permissions' => 'Chaque droit vaut pour ce dossier et ses sous-dossiers. « Hérité » reprend le dossier parent ; un sous-dossier qui dit « Oui » ou « Non » l\'emporte sur ses parents pour ce droit. Il suffit qu\'un des rôles de l\'utilisateur ait le droit.',
        'linked_permissions' => 'Les médias des contenus n\'héritent pas des droits de la racine : seuls les droits donnés ici comptent.',
    ],

    'confirm' => [
        'delete_folder' => 'Supprimer le dossier « :name » ? Seul un dossier vide peut être supprimé.',
        'delete_file' => 'Supprimer le fichier « :name » ? Cette action est définitive.',
    ],

    'notifications' => [
        'folder_created' => 'Dossier créé',
        'renamed' => 'Renommé',
        'moved' => 'Déplacé',
        'deleted' => 'Supprimé',
        'uploaded' => '{1} 1 fichier téléversé|[2,*] :count fichiers téléversés',
        'permissions_saved' => 'Droits enregistrés',
    ],

    'errors' => [
        'forbidden' => 'Vous n\'avez pas le droit de faire cela ici.',
        'name_required' => 'Le nom est obligatoire.',
        'name_invalid' => 'Ce nom n\'est pas valable : pas de « / » ni de « \\ », 200 caractères au plus.',
        'name_taken' => '« :name » existe déjà dans ce dossier.',
        'move_into_itself' => 'Un dossier ne peut pas être déplacé dans lui-même ni dans un de ses sous-dossiers.',
        'folder_not_empty' => 'Ce dossier n\'est pas vide : déplacez ou supprimez d\'abord son contenu.',
        'too_large' => 'Le fichier dépasse la taille maximale (:size).',
        'read_only' => 'Les médias des contenus sont en lecture seule.',
    ],
];
