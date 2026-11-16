RAPPORT QA — chantier 2026-11-17-pays-telephone-codes, partie A (tâche 04)
Go : oui
Base du verdict : rejoué moi-même.
- `php artisan test` : 142 passés (541 assertions). `pint --test` : passed.
- `scramble:export` frais comparé à openapi.json : identique. Migrations 2026_11_17_100000/100100/110000 : Ran.
- Mobile : `npm test` 14 suites / 55 tests verts ; `typecheck` et `lint` sans erreur.
- Base : 245 pays, 26 régions, 26 cantons pour CH, aucun dial_code ni native_name vide. CH Schweiz +41 {fr Suisse, en Switzerland}, JP 日本 +81, DE Deutschland +49, ES España +34, US +1.
- Essai réel API (compte jetable) : GET forms/auth.profile.personal : gender de type select (2 options), country_id 245 options « Suisse (Schweiz) », region_id depends_on=country_id, 26 options toutes parent=CH, readonly=false. PATCH personal-info : région sans pays refusée, région d'un autre pays refusée (message fr traduit), CH+canton accepté (country/region renvoyés). JP désactivé : 244 options, JP absent ; réactivé ensuite (0 pays inactif).
- Présence : last_seen_at écrit par un appel de /me ; updated_at inchangé ; is_online vrai après l'appel, faux avec last_seen_at à -6 min.
- auth:create-user : e-mail seul OK ; téléphone seul (--phone "079 123 45 67" --phone-country=CH) enregistré +41791234567 ; ni e-mail ni téléphone refusé ; numéro invalide (12345, CH) refusé avec message fr traduit.
- Revue de code : readonly (Field::rules renvoie [] ; submittedValues côté mobile), OptionBelongsToParent, règle phone + <nom>_country, digits:6, UserForm Filament (e-mail requiredWithout phone, téléphone E.164), IconColumn is_online, policies/permissions Location, middleware TrackLastSeen (toBase()->update), mobile (PhoneField, CodeField, OptionsModal avec champ de recherche, DynamicForm vide les enfants et affiche l'erreur <nom>_country sous le téléphone, User.email nullable, profil), textes form.search fr/en côté API.
- Nettoyage : comptes jetables supprimés ; il ne reste que diag-pays@example.test (non touché). Aucun fichier du dépôt modifié par moi.
Ne couvre pas : partie B (codes, inscription, connexion deux étapes, changements) ; rendu visuel Filament et simulateur mobile (non ouverts) ; saisie d'un téléphone via le formulaire de l'app (aucun formulaire phone n'est encore servi, seulement la règle et les tests) ; connexion back-office Filament par e-mail (non rejouée à la main, tests verts).

Bloquants : 0

Majeurs : 0

Mineurs : 2
- La pastille « en ligne » de la liste Filament (IconColumn is_online) n'a pas de test dédié (grep dans UserResourceTest : aucune occurrence) ; vérifiée seulement par lecture du code.
- Les tests Filament de saisie du téléphone (E.164, pays) dans UserResource ne sont pas identifiables (seule la règle required_without est testée, ligne ~138) ; comportement CLI vérifié à la main, pas celui du formulaire Filament.
