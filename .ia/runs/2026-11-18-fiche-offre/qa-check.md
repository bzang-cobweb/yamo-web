RAPPORT QA — 2026-11-18-fiche-offre
Go : oui (avec 1 écart majeur à traiter)
Base du verdict : rejoué moi-même.
- web : `ddev exec php artisan test` = 391 passés (1941 assertions) ; Pint --test = PASS (406 fichiers) ; `scramble:export --fail-on-unknown` puis diff avec openapi.json = identique.
- mobile : jest 35 suites / 192 tests passés ; `tsc --noEmit` sans sortie d'erreur ; `npm run lint` sans erreur.
- Test jetable (supprimé, hors arbre suivi, git status propre) : visiteur et compte non propriétaire -> 404 sur brouillon et archivée ; jeton invalide + offre publiée -> 200 ; propriétaire -> 200 sur brouillon/archivée avec statut ; supprimée -> 404 même pour le propriétaire ; sans domaine -> 404 ; non-propriétaire connecté : status = null ; aucune occurrence de la rue, user_id, latitude, longitude, e-mail du propriétaire ; clés servies : id,title,price,location,photos,description,facts,published_at,status ; requêtes SQL constantes (8) pour 0 ou 6 photos (pas de N+1) ; textes de la fiche présents dans /api/v1/messages ; valeurs fr (Oui, 85 m², 3,5, 1 décembre 2026) et en (Yes, 85 m², 3.5, December 1, 2026) correctes.
- Code relu : route sans `can:` justifiée (commentaire + entrée de RouteGuardTest), autorisation effective dans le contrôleur (Gate::forUser(user('sanctum'))->authorize('show')), RequireOfferDomain posé ; app : `offers/[id]` hors des `Stack.Protected` dans _layout.tsx, retry désactivé sur 404, message hors ligne, carrousel borné (clamp de l'index), ouverture depuis BrowseScreen.
Ne couvre pas : exécution sur appareil/simulateur (défilement réel du carrousel, rendu 16:9, retour vers l'accueil), critère 1 sur les données de démonstration de la base locale (non rejoué, aucun seed autorisé), cas hors ligne réel, accessibilité, performance en charge.

Bloquants : 0

Majeurs : 1
- Le formulaire de l'app ignore la nouvelle clé `suffix` : `grep suffix` dans mobile/src ne trouve rien. Le libellé « Surface (m²) » est devenu « Surface » côté serveur, donc le champ du formulaire mobile n'affiche plus son unité (régression visible dans « Mes logements »). Corriger : afficher `suffix` dans le champ nombre de l'app, ou remettre l'unité tant que l'app ne la rend pas. Aucun test mobile ne couvre ce point.

Mineurs : 3
- Le propriétaire voit son offre même dans un autre pays que le pays courant (200 sur un brouillon d'un autre pays), alors que les autres actions du propriétaire (`owns`) exigent le pays courant. Le contrat ne tranche pas ; à confirmer. Les caractéristiques utilisent alors le domaine actif.
- Avec un jeton Sanctum, la langue est celle du compte et non celle de l'en-tête Accept-Language (constaté : en-tête `en`, compte `fr`, réponse en français). C'est le comportement existant du socle, mais « la langue de la requête » du contrat s'entend donc « de la requête ou du compte ».
- Tests web manquants : aucun test de non-propriétaire connecté (brouillon -> 404), d'archivée pour le propriétaire, ni de fuite de l'e-mail/coordonnées ; le comportement est correct (vérifié ci-dessus) mais non verrouillé.
