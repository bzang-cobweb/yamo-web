<?php

// Textes de l'interface de l'app mobile, servis par GET /api/v1/messages
// (App\Support\MobileMessages). Variables au format :name.

return [
    'form' => [
        'loading' => 'Formular wird geladen…',
        'submitting' => 'Wird gesendet…',
        'selectPlaceholder' => 'Auswählen…',
        'showPassword' => 'Passwort anzeigen',
        'hidePassword' => 'Passwort verbergen',
        'clearDate' => 'Löschen',
        // Grille de mois d'un champ date (décision 2026-11-19-contrat-calendrier-offre).
        'previousMonth' => 'Vorheriger Monat',
        'nextMonth' => 'Nächster Monat',
        'closeCalendar' => 'Schließen',
        'chooseStartFirst' => 'Wählen Sie zuerst die Anreise.',
        'search' => 'Suchen…',
        'retry' => 'Erneut versuchen',
        'next' => 'Weiter',
        'previous' => 'Zurück',
        // Champ note en étoiles, lu par l'accessibilité.
        'ratingValue' => ':value von 5',
        // Unité d'un champ, dans sa ligne d'aide (« En m² »).
        'unit' => 'In :unit',
        // Liste à choix multiples et intervalle (décision 2026-11-19-contrat-multiselect-intervalle-equipements).
        'confirm' => 'Bestätigen',
        'rangeMin' => 'Min.',
        'rangeMax' => 'Max.',
        'rangeOrder' => 'Das Maximum muss mindestens so groß wie das Minimum sein.',
        'stepOf' => 'Schritt :current von :total',
        // Champ de fichiers (décision 2026-11-19-contrat-champ-upload-photos).
        'uploadAdd' => 'Fotos hinzufügen',
        'uploadMain' => 'Hauptfoto',
        'uploadMoveLeft' => 'Eine Stelle nach links',
        'uploadMoveRight' => 'Eine Stelle nach rechts',
        'uploadRemove' => 'Entfernen',
        // Liste de groupes (décision 2026-11-19-contrat-offer-informations).
        'repeaterRemove' => 'Diesen Block entfernen',
        'repeaterAdd' => 'Hinzufügen',
        'repeaterUntitled' => 'Ohne Titel',
        // Champ qui recharge le formulaire (décision 2026-11-19-contrat-langue-saisie-allemand).
        'reloadConfirm' => 'Wechseln? Nicht gespeicherte Änderungen gehen verloren.',
        'reloadContinue' => 'Fortfahren',
        'uploadSending' => 'Wird gesendet…',
        'uploadWrongType' => 'Format nicht erlaubt.',
        'uploadTooLarge' => 'Datei zu groß: höchstens :max.',
        'uploadTooMany' => 'Höchstens :max Dateien.',
        'uploadFailed' => 'Senden fehlgeschlagen. Erneut versuchen.',
    ],
    'list' => [
        // Filtres d'une liste (décision 2026-11-19-contrat-filtres-vitrine).
        'filters' => 'Filter',
        // Première option de chaque liste déroulante d'un filtre : aucun filtre.
        'all' => 'Alle',
        'applyFilters' => 'Filter anwenden',
        'clearFilters' => 'Filter zurücksetzen',
        // Actions d'un item avec un écran et un formulaire (décision 2026-11-19-nuit-voir-editer-retirer).
        'view' => 'Ansehen',
        'actions' => 'Aktionen',
        'edit' => 'Bearbeiten',
        'noFilterResults' => 'Keine Ergebnisse für diese Filter.',
        // Cœur de favori sur une photo (décision 2026-11-19-contrat-vedette-favoris).
        'addFavorite' => 'Zu den Favoriten hinzufügen',
        'removeFavorite' => 'Aus den Favoriten entfernen',
        // Note moyenne d'un item, lue par l'accessibilité (décision 2026-11-19-contrat-avis-offres).
        'ratingSummary' => 'Durchschnittliche Bewertung :average von 5, :count Bewertungen',
        'loading' => 'Liste wird geladen…',
        'cancel' => 'Abbrechen',
        'noResults' => 'Keine Ergebnisse.',
    ],
];
