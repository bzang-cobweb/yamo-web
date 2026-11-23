<?php

// Le module Partner : les partenaires de l'accueil (décision 2026-11-23-module-partenaires).

return [
    'singular' => 'Partenaire',
    'plural' => 'Partenaires',
    'sections' => [
        'identity' => 'Partenaire',
        'content' => 'Ce que montre l\'app',
        'campaign' => 'Campagne',
        'contact' => 'Contact',
        'stats' => 'Statistiques',
    ],
    // Décision 2026-11-24-partenaires-statuts.
    'statuses' => [
        'pending' => 'En validation',
        'published' => 'Publié',
        'rejected' => 'Refusé',
        'archived' => 'Archivé',
    ],
    'fields' => [
        'slogan' => 'Slogan',
        'status' => 'Statut',
        'user' => 'Compte',
        'is_admin' => 'Administrateur',
        'make_admin' => 'Faire administrateur',
        'remove_admin' => "Retirer l'administration",
        'proposed' => "Proposé depuis l'app",
        'name' => 'Nom',
        'country' => 'Pays',
        'logo' => 'Logo',
        'description' => 'Description',
        'url' => 'Lien',
        'cta_label' => 'Libellé du bouton',
        'published' => 'Publié',
        'starts_at' => 'Début',
        'expires_at' => 'Expire le',
        'contact_email' => 'E-mail de contact',
        'contact_phone' => 'Téléphone de contact',
        'views_count' => 'Vues',
        'clicks_count' => 'Clics',
    ],
    // « Mes partenaires » dans l'app (décision 2026-11-24-partenaires-par-les-membres).
    'member' => [
        // Les étapes du formulaire de l'app (décision 2026-11-24-etape-presentation).
        'steps' => [
            'presentation' => 'Présentation',
        ],
        'group' => 'Partenaire',
        'count' => '{0} Aucun partenaire|{1} :count partenaire|[2,*] :count partenaires',
        'mine' => 'Mes partenaires',
        'add' => 'Ajouter un partenaire',
        'edit' => 'Modifier le partenaire',
        'submit' => 'Envoyer pour validation',
        'save' => 'Enregistrer et renvoyer',
        'submitted' => "Partenaire envoyé : l'équipe le valide avant de le publier.",
        'saved' => "Partenaire enregistré : l'équipe le valide de nouveau avant de le publier.",
        'review_notice' => "L'équipe valide chaque partenaire, et chaque modification, avant de l'afficher dans l'app.",
        'cta_help' => 'Le texte du bouton qui mène à votre lien. Vide : « Visiter le site ».',
        'empty' => "Aucun partenaire pour l'instant.",
        'remove' => 'Retirer',
        'remove_confirm' => 'Retirer ce partenaire ?',
        'removed' => 'Partenaire retiré.',
        'status' => [
            'shown' => 'Affiché',
            'hidden' => 'Pas affiché',
        ],
    ],
    // Les comptes qui administrent un partenaire, au back-office.
    'admins' => 'Administrateurs',
    // Un changement de statut, aux comptes du partenaire (décision 2026-11-24-partenaires-notifications).
    'notify' => [
        'greeting' => 'Bonjour :name,',
        'salutation' => "L'équipe :app",
        'title' => [
            'pending' => 'Partenaire en validation',
            'published' => 'Partenaire publié',
            'rejected' => 'Partenaire refusé',
            'archived' => 'Partenaire archivé',
        ],
        'body' => [
            'pending' => "Votre partenaire « :name » est en validation par l'équipe.",
            'published' => 'Votre partenaire « :name » est publié.',
            'rejected' => "Votre partenaire « :name » a été refusé par l'équipe.",
            'archived' => 'Votre partenaire « :name » est archivé.',
        ],
    ],
    // La langue de saisie à la modification (décision 2026-11-24-mes-partenaires-cartes).
    'content_locale' => [
        'step' => 'Langue',
        'label' => 'Langue de saisie',
        'help' => 'Dans quelle langue modifiez-vous ce partenaire ? Une langue à traduire est pré-remplie avec un texte existant.',
        'translated' => 'traduite',
        'to_translate' => 'à traduire',
    ],
];
