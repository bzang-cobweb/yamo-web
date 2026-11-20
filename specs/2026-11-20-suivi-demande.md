# Contrat — Le suivi d'une demande dans les notifications

- **Date** : 2026-11-20
- **Demande** : « lorsqu'un utilisateur demande un logement, toutes les notifications qui vont être générées par cette demande [doivent être] regroupées. Ainsi, l'utilisateur et le propriétaire peuvent voir tout le suivi de la demande. »
- **Arbitrages** (décision 2026-11-20-suivi-demande) : une carte par demande ; le suivi montre les deux côtés ; les non lues comptent par demande.

## Ce que j'ai compris

Une demande de logement produit plusieurs notifications, réparties entre deux personnes :
- le propriétaire reçoit la demande et l'annulation ;
- le membre reçoit l'acceptation ou le refus.

Aujourd'hui, chacune est une carte à part. Après le chantier, chaque personne voit **une seule carte par demande**, avec sa dernière étape. En ouvrant la carte, elle voit **tout le suivi**, côté membre comme côté propriétaire.

## Découpage prévu

### Serveur (web)
1. **Le fil d'une notification** : une colonne `thread` (nullable, indexée) dans `notifications`.
   - Pour une demande, elle vaut `booking:<id>`. Elle est remplie à l'envoi par le canal `database`, comme `user_id`.
   - Une migration la remplit pour les notifications existantes, à partir de `data.booking_id`.
   - Une notification sans fil reste seule : elle a sa propre carte.
2. **La liste** (`GET /notifications`) : une ligne par fil, la plus récente.
   - Une nouvelle étape remonte la carte en tête.
   - Recherche et filtres s'appliquent à cette ligne. Le statut reste le statut réel de la demande.
3. **Le compteur** (`GET /notifications/unread-count`) : le nombre de fils qui ont au moins une notification non lue.
4. **La page** (`GET /notifications/{id}`) : en plus de l'actuel, un **`history`**, c'est-à-dire les étapes de la demande dans l'ordre chronologique.
   - Il va de la plus ancienne étape à la plus récente (précision de la validation).
   - Il est construit à partir des notifications des **deux** parties sur ce fil : demande envoyée, acceptée ou refusée (avec le motif), annulée.
   - Chaque étape donne : son libellé, qui agit, sa date, son message.
   - Le texte est dans la langue du lecteur.
   - Seule une partie de la demande voit ce suivi.
5. **Marquer lu** : ouvrir la carte marque lues **toutes** les notifications de ce fil pour ce lecteur.

### App (mobile)
6. **La carte** reste celle d'aujourd'hui : type et statut, texte, nom, message, date à droite. Elle porte la dernière étape.
7. **La page de la notification** affiche, sous le contenu actuel, le **suivi** : une liste d'étapes datées (libellé, qui, date, message).

## Critères de succès (état observable)

- Une demande reçue puis annulée ne fait **qu'une** carte chez le propriétaire.
- Une demande acceptée fait une carte chez le membre et une chez le propriétaire. Ouverte d'un côté ou de l'autre, chaque carte montre **demande envoyée** puis **acceptée**, avec leurs dates.
- Un refus montre son motif dans le suivi.
- Une nouvelle étape fait remonter la carte de la demande en tête de liste.
- Le badge de l'onglet compte 1 pour une demande qui a deux notifications non lues. Ouvrir la carte le ramène à 0.
- Le compteur « x demandes en attente » de Mes logements ouvre toujours la liste filtrée et cohérente.
- Une notification qui n'appartient à aucune demande garde sa propre carte.
- Un membre étranger à la demande n'obtient pas son suivi.
- Toute la suite de tests passe dans les deux dépôts ; OpenAPI est régénéré.

## Hors périmètre

- Pas de notification au propriétaire pour sa propre réponse : son action apparaît dans le suivi seulement.
- Pas de nouvelle table d'historique : le suivi est construit à partir des notifications.
