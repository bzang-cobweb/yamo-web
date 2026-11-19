# Contrat — Module Notification : notifications de l'app

Validation : validé le 2026-11-19 (« Approved »)

Demande : « ajoute un module notification avec une icône avec badge si > 0 à côté du drapeau sur la barre du haut et ne mets plus les badges sur les autres listes de bookings » ; puis « show badge even if 0 notifications », « notifications older than 90 days are deleted daily, in notification config file and in .env », « include phone push notifications, but not now, I will tell you when » ; place du lien : « bottom tab between home and profile ».

## Ce que j'ai compris

### Dans l'app
- Pour un membre connecté, un **onglet « Notifications »** (icône cloche) dans le **menu du bas, entre Accueil et Profil**. Un visiteur ne le voit pas.
- Une **pastille** sur l'onglet : le nombre de notifications **non lues**, **toujours affichée, « 0 » compris** ; « 99+ » au-delà.
- L'onglet ouvre l'écran « **Notifications** » : la liste, la plus récente en tête, par pages ; chacune avec son titre, une ligne de texte, sa date (« il y a 5 min », « hier ») ; les non lues mises en avant.
- Toucher une notification la **marque lue** et ouvre l'écran qu'elle désigne (pour une réservation : « Réservations reçues » ou « Mes réservations »). Un bouton « **Tout marquer comme lu** » en haut de la liste.
- La pastille se met à jour quand l'écran qui la porte revient (retour d'un écran, app rouverte).
- **Plus de pastille** sur « Mes réservations » ni « Réservations reçues » (déjà retiré, jamais livré).

### Dans Laravel
- Un **module `Notification`** : le stockage des notifications de l'app (canal `database` de Laravel, table `notifications`), l'API, et rien de propre aux réservations.
- Toute notification qui doit apparaître dans la cloche ajoute le canal `database` et décrit son contenu (`toArray()`) : un titre, un texte, et l'écran à ouvrir (une liste ou une fiche de l'app). Les textes sont figés dans la langue du destinataire au moment de l'envoi.
- Les quatre notifications de réservation (demande reçue, acceptée, refusée, annulée) y apparaissent, **toujours**, quels que soient l'e-mail et le SMS.
- API (membre connecté) : la liste par pages ; le nombre de non lues ; marquer une notification lue ; tout marquer lu. Seulement les siennes.
- Les notifications plus anciennes que la durée de conservation sont effacées chaque jour (tâche planifiée). La durée se règle dans `Modules/Notification/config/config.php` (`notification.retention_days`), lue depuis `.env` (`NOTIFICATION_RETENTION_DAYS`, 90 par défaut) ; la variable est ajoutée à `.env` et à `.env.example`.

## Place de la cloche (tranchée)

- Menu du bas, entre Accueil et Profil : visible sur l'accueil, le profil et les listes ; pastille native de l'onglet ; masqué comme les autres sur les formulaires. Les autres places (barre du haut, profil) sont écartées.

## Plus tard, sur signal de l'humain : notifications poussées du téléphone

Hors de ce chantier ; préparé pour s'y ajouter sans rien refaire :
- un canal de plus pour les mêmes notifications (comme l'e-mail et le SMS), choisi par `User::notificationChannels()` ;
- l'app enregistre le jeton de l'appareil auprès de l'API à la connexion (Expo Notifications) ; toucher une notification poussée ouvre l'écran désigné, comme dans la liste ;
- demandera une dépendance (`expo-notifications`, et côté Laravel un envoi vers le service d'Expo) : autorisation à demander alors.

## Découpage

- Laravel :
  - module `Notification` (activé dans `modules_statuses.json`), migration de la table `notifications` (commande Laravel), routes et contrôleur, ressource API, configuration de la conservation (`.env`), tâche planifiée d'effacement ;
  - module Offer : canal `database` et `toArray()` des quatre notifications de réservation ;
  - textes fr, en, de ; tests ; OpenAPI.
- App :
  - onglet « Notifications » entre Accueil et Profil, avec pastille (membre connecté) ; écran « Notifications » (liste, lu / non lu, tout marquer lu, ouverture de l'écran désigné) ; textes locaux de secours ;
  - tests ; vérification au simulateur.
- Aucune dépendance ajoutée.

## Critères de succès (état observable)

- Connecté, l'onglet « Notifications » est entre Accueil et Profil ; sa pastille montre le nombre de non lues, « 0 » compris ; visiteur : pas de cloche.
- Une demande de réservation crée une notification pour le propriétaire (en plus de l'e-mail et du SMS), qui s'affiche dans la liste et fait monter la pastille.
- Toucher une notification la marque lue (la pastille baisse) et ouvre l'écran désigné ; « Tout marquer comme lu » ramène la pastille à « 0 ».
- Une notification plus ancienne que `NOTIFICATION_RETENTION_DAYS` jours est effacée par la tâche quotidienne.
- Un membre ne voit ni ne modifie les notifications d'un autre.
- « Mes réservations » et « Réservations reçues » n'ont pas de pastille.
- Suites web et app vertes, tsc propre, OpenAPI à jour, simulateur vu, QA indépendante.
