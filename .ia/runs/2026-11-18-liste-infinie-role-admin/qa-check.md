RAPPORT QA — 2026-11-18-liste-infinie-role-admin
Go : oui

Base du verdict (tout rejoué par moi) :
- Web : `ddev exec php artisan test` = 314 passed (1521 assertions) ; `pint --test` = PASS 328 fichiers ; `scramble:export --fail-on-unknown` vers storage/app/qa-openapi.json puis diff avec openapi.json = vide (fichier temporaire supprimé).
- Mobile : `npx jest` = 33 suites / 161 tests verts ; `tsc --noEmit` sans sortie ; `npm run lint` = 0 erreur, 1 warning (router.d.ts, fichier généré non suivi par git, hors chantier).
- Base locale (tinker lecture seule) : rôles `admin` et `super_admin` ; `admin` a addresses.view/create/update/delete.
- Rejeu contre l'application (HTTP kernel, `/api/v1/lists/location.addresses`, jeton Sanctum) avec comptes jetables qa-liste@example.test et qa-liste2@example.test, dans une transaction annulée (0 compte restant) :
  - 45 adresses : pages [20,20,5], 45 ids uniques, dans l'ordre, dernier next_cursor null (critère 2).
  - Retrait du 1er et du 20e item entre pages 1 et 2 : le reste (25) est exactement les ids suivants, sans saut ni doublon (critère 3).
  - Curseurs invalides : chaîne `abc`, base64 de `"x"`, `[1,2]`, objet aux mauvaises clés, `cursor[]=x`, `cursor=` vide, clé SQL falsifiée, id objet, id non numérique : tous 200 et première page, aucun 500 (critère 4).
  - Fuite inter-comptes : curseur `id=0` avec 5 adresses d'un autre compte : aucune adresse de l'autre compte (la requête est bornée par la relation du compte ; le curseur n'ajoute qu'une condition sur l'id).
- Lecture du code : List.tsx n'importe que react, react-native, thème (aucun import API) ; son test n'utilise que des données locales ; ProfileScreenShell garde son ScrollView par défaut (`scrolls = true`), seul ProfileListScreen passe `scrolls={false}` : pas de FlatList dans un ScrollView, pas de régression visible pour les autres écrans (leurs tests passent).
- Critère 1 : RoleSeederTest (admin a les 4 permissions ; rejeu du seeder conserve une permission ajoutée ; admin gère les adresses en back-office, compte sans rôle non) vert dans les 314 tests. Seeder en `givePermissionTo` (ajout sans retrait), vérifié à la lecture.

Ne couvre pas :
- Aucun rejeu du back-office Filament dans un navigateur (appuyé sur les tests Feature).
- Aucun rejeu sur appareil / simulateur du défilement réel (onEndReached, seuil 0.5, tirer pour recharger) : critère 5 vérifié par les tests jest (ProfileListScreen, List, useListDefinition), pas en conditions réelles.
- Pas de test de charge ni de vérification des index MariaDB.
- Non rejoué : seeder en base locale (état constaté seulement).

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Le contrat nomme les props de List `onEndReached`/`hasMore` ; le code expose `onLoadMore`/`hasMore`. Écart de nom sans effet fonctionnel, à aligner (contrat ou code).
- ProfileListScreen appelle `reload()` à chaque focus, y compris au premier montage où `useInfiniteQuery` charge déjà : risque d'une requête de première page redondante (le test « à l'affichage, la liste repart de la première page » acte le comportement ; non mesuré sur réseau réel).
- Un curseur valide mais dirigé vers l'arrière (`_pointsToNextItems:false`) sert la page précédant l'id donné (comportement natif Laravel, sans fuite ni 500) au lieu de la première page ; cohérent avec le contrat mais à savoir.
