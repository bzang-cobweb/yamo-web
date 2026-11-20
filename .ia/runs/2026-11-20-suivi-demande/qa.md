RAPPORT QA — 2026-11-20-suivi-demande
Go : oui
Base du verdict : rejoué moi-même.
- web : `ddev exec -s web php artisan test --compact` = 585 passed (3203 assertions). BookingThreadTest = 5 passed (38 assertions).
- mobile : `npx jest src/features/notification` = 3 suites, 13 tests passed. `npx jest` complet = 54 suites, 289 tests passed. `npx tsc --noEmit` sans erreur.
- Code lu (3e25c44, e445aa0) et mis en face de chaque critère :
  - Une carte par demande : NotificationThreads::latestOnly ne garde que la notification la plus récente du fil (created_at, id). Test : reçue puis annulée = 1 carte chez le propriétaire.
  - Deux côtés et ordre : steps() prend tout le fil `booking:<id>` trié created_at puis id (plus ancien vers plus récent). Test : mêmes 3 étapes, acteurs et dates croissantes lues par le membre et par le propriétaire. Mobile : NotificationHistory affiche les étapes dans l'ordre reçu, sans re-tri.
  - Motif de refus : test « Déjà réservé » présent dans history[1].message.
  - Remontée en tête : tri created_at desc sur la carte la plus récente. Test : l'annulation remonte la carte.
  - Non lues par demande : unreadCount = count(distinct coalesce(thread, id)) ; read() marque tout le fil lu ; la carte est « lue » si le fil entier l'est. Test : 2 notifications non lues d'une demande + 1 autre = 2, puis 1 après ouverture.
  - Notification sans fil : thread null, carte propre. Test : couverte.
  - Étranger : show() est borné aux notifications de l'utilisateur, donc 404. Test : couvert.
  - Compteur « x demandes en attente » : OfferListing ouvre `filter[type=booking, status=pending, offer=<id>]`. Le filtre de statut est le statut réel de la demande (inchangé). Il s'applique après latestOnly, sur la carte du fil. Une demande en attente n'a qu'une notification, donc une carte, donc nombre de cartes = compteur. Vérifié par lecture du code et par NotificationFiltersTest (dans les 585). Aucun test ne croise explicitement compteur et fil.
  - OpenAPI : openapi.json modifié dans le commit (history présent). Je n'ai pas rejoué `ddev composer openapi` (interdit).
  - Migrations : colonne `thread` indexée, et rattrapage des notifications existantes (booking_id vers booking:<id>). Lues, non rejouées (interdit de migrer).
  - Dépôts propres : git status vide dans web et mobile avant écriture de ce rapport.
Ne couvre pas : exécution sur appareil ou simulateur ; MariaDB réelle (les tests tournent en SQLite) ; rejeu de la migration de rattrapage sur des données existantes ; régénération d'OpenAPI ; performance de la requête NOT EXISTS (auto-jointure) sur un gros volume ; aucune notification chez le propriétaire pour sa propre réponse (hors périmètre, non vérifié).

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Aucun test ne vérifie directement le critère « compteur Mes logements cohérent avec la liste filtrée » en présence du fil (liste filtrée status=pending sur les cartes). Le raisonnement tient, mais il n'est pas couvert par un test de non-régression.
- La migration de rattrapage ne reprend que les `booking_id` entiers (`is_int`). Une donnée ancienne stockée en chaîne resterait sans fil, donc avec sa carte séparée.
- steps() lit tout `thread` sans borne de destinataire. C'est voulu (suivi des deux côtés) et la clé est propre à une demande, mais l'accès reste protégé uniquement par la route show().
