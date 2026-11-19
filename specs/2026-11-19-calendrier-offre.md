# Contrat — Calendrier d'un logement pour traiter les réservations

Validation : validé le 2026-11-19 (« Approved »)

Demande : « proposer une alternative avec entrée sur un calendrier de l'offre pour le traitement des réservations », puis « Yes, write it » sur la proposition.

## Ce que j'ai compris

La liste « Demandes · <logement> » reste : elle sert à traiter les demandes une à une. Le calendrier s'ajoute, logement par logement. Il montre l'occupation dans le temps, et les demandes qui se chevauchent.

### Où on l'ouvre
- Sur chaque carte de « **Mes logements** », un bouton « **Calendrier** », à côté du compteur des demandes en attente.
- Le compteur ouvre toujours la liste filtrée, comme aujourd'hui.
- Le titre de l'écran est « Calendrier · <logement> ».

### L'écran calendrier
- **Vue par mois**, en grille de sept colonnes, du lundi au dimanche. On change de mois par les flèches ou en glissant. Le mois courant s'affiche par défaut.
- **Couleur de chaque jour** (les mêmes tons que les pastilles de statut) :
  - **vert** : occupé par un séjour accepté ;
  - **orange** : au moins une demande en attente ;
  - **contour rouge** : une demande en attente chevauche un séjour accepté ;
  - **gris** : jour passé, ou jour bloqué par le propriétaire (bloqué : gris hachuré ou barré, distinct du passé) ;
  - **sans couleur** : libre.
- Une **visite** (logement non meublé) est un jour sans heure : un point sur ce jour, orange en attente, vert acceptée.
- Une **légende** sous la grille.
- **Toucher un jour** ouvre un panneau en bas avec ce qui touche ce jour :
  - chaque demande : le membre, les dates, les voyageurs, le total, son message cité, sa pastille de statut, et les boutons **Accepter / Refuser** déjà existants ;
  - un jour libre et à venir : « **Bloquer ce jour** » ;
  - un jour bloqué : « **Débloquer** ».
- Après une réponse, le calendrier et le compteur de « Mes logements » se mettent à jour.

### Chevauchements (règle côté serveur)
- **Accepter** une demande qui chevauche d'autres demandes en attente sur ce logement :
  - une confirmation nomme celles-ci (« 2 demandes en attente chevauchent ces dates et seront refusées ») ;
  - accepter refuse automatiquement les demandes en attente qui chevauchent, et chaque membre concerné est prévenu (notification, e-mail, SMS) avec un motif standard (« Ces dates ne sont plus disponibles ») ;
  - ces demandes sont refusées, pas annulées.
- **Accepter** une demande qui chevauche un séjour **déjà accepté**, ou un jour bloqué : refusé, avec un message clair. Deux séjours acceptés ne peuvent jamais se chevaucher sur un même logement.
- **Demander** (côté membre) des dates qui chevauchent un séjour accepté ou un jour bloqué : refusé à l'envoi, avec un message clair.
- Les visites ne bloquent pas les séjours, et inversement : un logement non meublé n'a que des visites.

### Jours bloqués
- Le propriétaire bloque ou débloque un jour à venir depuis le panneau du jour.
- Un jour bloqué ne peut plus être demandé ni accepté.
- Bloquer un jour qui porte un séjour accepté est impossible : il faut d'abord l'annuler. Ce cas est montré, mais aucune action d'annulation par le propriétaire n'est ajoutée ici.

### Ce que voit le membre en choisissant ses dates
- Dans le formulaire de réservation, les jours occupés (séjours acceptés) et bloqués sont **grisés et non choisissables**.
- Le sélecteur natif ne sait pas griser des jours précis. Le formulaire de réservation utilise donc la **même grille de mois** que le calendrier, en sélecteur :
  - un séjour se choisit par arrivée puis départ ;
  - une visite se choisit en un seul jour.
- Le serveur décrit ce sélecteur comme un champ de formulaire (bornes et jours indisponibles). Les autres champs date de l'app gardent le sélecteur natif.

### Générique (pour rester décrit par Laravel)
- Le serveur décrit chaque mois du calendrier : pour chaque jour, son état, ses demandes et les actions possibles. L'app ne connaît ni les réservations ni les logements : elle affiche une grille et un panneau.
- Le champ de formulaire « dates sur une grille » (une période, ou un seul jour, et des jours indisponibles) est générique. Il sert à la réservation, et peut servir ailleurs.

## Découpage

- **Laravel :**
  - table `offer_blocked_dates` (offre, jour), migration et modèle ;
  - règle de chevauchement :
    - dans `BookingService` : demande refusée sur un jour occupé ou bloqué ; accepter refuse les demandes en attente qui chevauchent, avec notification ; accepter est refusé sur un séjour accepté ou un jour bloqué ;
    - les demandes concurrentes sont verrouillées dans une transaction ;
  - point d'API du calendrier : `GET /v1/offers/{offer}/calendar?month=AAAA-MM`, réservé au propriétaire, avec les jours, leurs états, leurs demandes (comme les cartes) et leurs actions ;
  - bloquer et débloquer : `PUT` et `DELETE /v1/offers/{offer}/blocked-dates/{date}` ;
  - bouton « Calendrier » sur la carte de « Mes logements » ;
  - champ de formulaire « dates sur une grille » ; le formulaire de réservation l'utilise, avec les jours indisponibles ;
  - textes fr, en, de ; tests ; OpenAPI.
- **App :**
  - écran calendrier : grille de mois en vues simples, navigation, légende, panneau du jour avec les cartes existantes (pastille, citation, boutons) ;
  - champ « dates sur une grille », avec la même grille ;
  - route de l'écran calendrier hors des onglets, comme les listes du profil ;
  - tests ; vérification au simulateur.
- **Aucune dépendance ajoutée** : la grille est faite à la main, sans bibliothèque de calendrier.

## Critères de succès (état observable)

- Sur « Mes logements », chaque carte propose « Calendrier », qui ouvre le mois courant de ce logement.
- Les jours s'affichent avec les bons états : accepté, en attente, chevauchement, bloqué, passé, libre, et visites en point. On passe d'un mois à l'autre.
- Toucher un jour montre ses demandes avec Accepter / Refuser. Répondre met à jour le calendrier et le compteur.
- Accepter une demande refuse les demandes en attente qui la chevauchent, après une confirmation qui les nomme, et leurs membres sont prévenus.
- Il est impossible d'avoir deux séjours acceptés qui se chevauchent sur un logement, ni un séjour accepté sur un jour bloqué : ni par l'app, ni par l'API.
- Le propriétaire bloque et débloque un jour à venir. Un jour bloqué n'est plus demandable.
- Dans le formulaire de réservation, les jours occupés et bloqués sont grisés et non choisissables. Une demande envoyée quand même sur ces jours est refusée.
- Seul le propriétaire voit le calendrier de son logement (404 pour les autres).
- Suites web et app vertes, tsc propre, OpenAPI à jour, simulateur vu (iOS), QA indépendante.

## Points tranchés par défaut (à confirmer en validant)

1. Accepter une demande qui en chevauche d'autres en attente : **elles sont refusées automatiquement**, après une confirmation qui les nomme.
2. Le propriétaire **peut bloquer des jours**.
3. Le membre **voit les jours indisponibles grisés** en choisissant ses dates.
4. Visites : **un point sur le jour**, sans créneau horaire. Des créneaux seraient un chantier à part.

## Hors périmètre

- Créneaux horaires pour les visites.
- Annulation d'un séjour accepté par le propriétaire.
- Prix variables selon les jours, synchronisation avec des calendriers externes (iCal, Airbnb).
- Bloquer une période en un geste (seulement jour par jour ici).
