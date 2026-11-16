# `2026-11-16` — Contrat du chantier genre, date de naissance, nom complet et initiales, validé

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-16-genre-date-naissance.md`, quatre
  arbitrages et une correction.
- **Réponse** :
  - **Genre** : `GenderEnum` (`male`, `female`), **obligatoire** dans le formulaire
    Informations personnelles, en boutons radio. La colonne accepte `null` (comptes
    existants, commande `auth:create-user`).
  - **Date de naissance** : facultative, nouveau type de champ `date` (`AAAA-MM-JJ`),
    saisie avec le sélecteur natif. Dépendance mobile autorisée :
    **`@react-native-community/datetimepicker`**.
  - **Filament** : genre et date dans la fiche utilisateur seulement, pas dans la
    liste.
  - **Propriété `required` des champs** : déjà en place (booléen de `Field`, envoyé à
    l'app). Rien à ajouter.
  - **Nom complet et initiales** : calculés par le modèle `User` (`full_name`,
    `initials`), servis par l'API et utilisés tels quels par l'app. Les initiales
    sont **sans accent** (correction de l'humain).
- **Motif** :
  - des données de profil plus complètes ;
  - une seule source pour le nom complet et les initiales.
- **Portée** : module Auth, module Form (type `date`), app mobile.
- **Corollaires** :
  - l'app ne recalcule plus ni nom complet ni initiales ;
  - tout nouveau type de champ du module Form demande son composant mobile.
- **Tranché par** : humain
