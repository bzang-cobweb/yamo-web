<?php

return [
    'name' => 'Partner',

    // Le logo d'un partenaire, envoyé depuis l'app ou le back-office (décision 2026-11-24-partenaire-logo-config).
    'logo' => [
        // Les types de fichier acceptés, séparés par des virgules.
        'mime_types' => array_values(array_filter(array_map('trim', explode(',', (string) env('PARTNER_LOGO_MIME_TYPES', 'image/jpeg,image/png,image/webp'))))),
        // La taille maximale d'un envoi, en kilo-octets.
        'max_upload_kb' => (int) env('PARTNER_LOGO_MAX_UPLOAD_KB', 5120),
        // Le plus grand côté du logo enregistré, en pixels.
        'stored_size' => (int) env('PARTNER_LOGO_STORED_SIZE', 512),
        // La qualité du WebP enregistré, de 1 à 100.
        'quality' => (int) env('PARTNER_LOGO_QUALITY', 85),
        // Une conversion par endroit où le logo s'affiche : le plus grand côté, en pixels (trois fois l'affichage,
        // pour les écrans haute densité) ; proportions gardées, en WebP (décision 2026-11-24-partenaire-logo-conversions).
        'conversions' => [
            // Le carrousel de l'accueil (96 points).
            'home' => (int) env('PARTNER_LOGO_HOME_SIZE', 288),
            // La page « Partenaires » (56 points).
            'list' => (int) env('PARTNER_LOGO_LIST_SIZE', 168),
            // Les cartes de « Mes partenaires » (le bandeau d'une carte).
            'mine' => (int) env('PARTNER_LOGO_MINE_SIZE', 480),
            // La page d'un partenaire (120 points).
            'detail' => (int) env('PARTNER_LOGO_DETAIL_SIZE', 360),
        ],
    ],
];
