# Contrat — Réservations : demande du membre, réponse du propriétaire

Validation : validé le 2026-11-19 (« Approved »)

Demande : « détail offre : dans le footer fixe afficher le prix et le bouton de réservation » ; puis « Build booking now », « Only in the bar » ; meublé réservé par dates, non meublé : demande de visite ; demande que le propriétaire accepte ou refuse, sans paiement ; pas de calendrier bloquant ; prévenir dans l'app, par e-mail et par SMS.

## Ce que j'ai compris

### Sur la fiche d'une offre
- Une **barre fixe en bas** : le prix à gauche (« 45 000 FCFA / jour »), le bouton à droite. Le prix n'est plus sous le titre.
- Le bouton dépend de l'offre (décidé par le domaine, dans Laravel) :
  - logement **meublé** : « **Réserver** » ;
  - logement **non meublé** : « **Demander une visite** ».
- Visiteur non connecté : le bouton mène à la connexion, puis revient sur la fiche.
- Sa propre offre : la barre montre le prix, sans bouton.
- Offre sans prix : la barre montre seulement le bouton.

### La demande (formulaire décrit par Laravel)
- **Réserver** (meublé) : date d'arrivée, date de départ, nombre de voyageurs (1 à 20), message au propriétaire (facultatif). Sous les dates : le prix estimé (« 3 jours × 45 000 FCFA = 135 000 FCFA »), recalculé par le serveur à l'envoi et gardé avec la demande.
- **Demander une visite** (non meublé) : date souhaitée, message (facultatif).
- Règles : membre connecté ; pas sa propre offre ; offre visible par les autres ; dates à partir d'aujourd'hui ; départ après l'arrivée ; 365 jours au plus ; **une seule demande en attente** par membre et par offre.
- Les dates ne sont **pas bloquées** : plusieurs membres peuvent demander les mêmes dates ; le propriétaire refuse ce qui ne convient pas.

### Statuts et actions
- **En attente** → le propriétaire **accepte** ou **refuse** (message de refus facultatif).
- Le membre peut **annuler** sa demande tant qu'elle est en attente, ou acceptée et pas encore commencée.
- Statuts : en attente, acceptée, refusée, annulée. Rien n'est supprimé.

### Où les suivre (app, dans le profil)
- « **Mes réservations** » (le membre) : ses demandes, la plus récente en tête, avec l'offre, les dates, le statut ; ouvrir une demande permet de l'annuler.
- « **Réservations reçues** » (le propriétaire, s'il a des offres) : les demandes sur ses offres ; ouvrir une demande permet d'accepter ou de refuser.
- Listes et formulaires génériques, décrits par Laravel (comme « Mes adresses », « Mes logements »).

### Prévenir
- Nouvelle demande → le **propriétaire** ; réponse (acceptée, refusée) → le **membre** ; annulation → le **propriétaire**.
- Par **e-mail** et par **SMS**, chacun seulement si le compte a l'adresse ou le numéro (vérifiés), dans la langue du destinataire ; et dans l'app, par les listes ci-dessus.
- Textes en fr, en, de.

### Back-office
- Une ressource « **Réservations** » en lecture : liste filtrable (statut, offre, membre), fiche d'une demande ; pas de modification (les décisions restent au propriétaire). Visible aux rôles qui voient les offres.

## Découpage

- Laravel (module Offer) :
  - table `bookings` (offre, membre, type `stay` / `visit`, arrivée, départ, voyageurs, message, prix et devise figés, statut, message de refus, dates de réponse et d'annulation) ; modèle, policy, statut en enum ;
  - le domaine dit si une offre se réserve et comment (`RentalDomain` : meublé → séjour, non meublé → visite) ;
  - fiche : le bouton servi (`booking: {label, form}` ou `null`) ;
  - formulaires : `offer.booking` (demande) et la réponse / l'annulation ; listes « Mes réservations » et « Réservations reçues » ; entrées du menu du profil ;
  - notifications e-mail et SMS (expéditeurs déjà en place) ;
  - ressource Filament en lecture ; permissions ; OpenAPI ; tests.
- App :
  - barre fixe de la fiche (prix + bouton), prix retiré sous le titre ;
  - connexion puis retour sur la fiche ;
  - le reste passe par les écrans génériques (formulaires, listes, menu du profil) ; tests ; vérification au simulateur.
- Aucune dépendance ajoutée.

## Critères de succès (état observable)

- Fiche d'un meublé : barre fixe « prix | Réserver » ; d'un non meublé : « prix | Demander une visite » ; sa propre offre : le prix seul ; le prix n'est plus sous le titre.
- Un membre envoie une demande valide ; une demande invalide (dates passées, départ avant l'arrivée, sa propre offre, déjà une en attente) est refusée avec un message sous le champ.
- Le propriétaire la voit dans « Réservations reçues », l'accepte ou la refuse ; le membre voit le statut dans « Mes réservations » et peut annuler.
- E-mail et SMS partent aux bons destinataires, dans leur langue, seulement vers une adresse ou un numéro présent.
- Le back-office liste les réservations sans pouvoir les modifier.
- Suites web et app vertes, tsc propre, OpenAPI à jour, parcours vu au simulateur, QA indépendante.

## Ce que j'ai décidé seul

- **Pas de total estimé en direct sous les dates** : un formulaire décrit par Laravel ne calcule rien dans l'app, et le message de succès est fixe. Sous la date de départ : le prix par jour et « le total figure dans « Mes réservations » une fois la demande envoyée » ; le total est calculé et figé par le serveur à l'envoi, affiché dans les deux listes.
- **Bornes du calendrier servies par Laravel** (`Field::dateRange()`, décision 2026-11-19-bornes-des-dates) : le calendrier de l'app s'arrêtait à aujourd'hui, ce qui rendait la réservation impossible. Corrigé pour toutes les dates : naissance jusqu'à aujourd'hui (ou l'âge minimum), réservation d'aujourd'hui à un an, « Disponible dès le » et « Libre le » sans borne.
- **« Réservations reçues »** n'apparaît qu'à qui a une offre dans le pays courant : `ProfileMenuLinks` passe désormais le compte à la condition d'un lien.
- **Notifications** : e-mail seulement si l'adresse est vérifiée, SMS seulement si le numéro l'est ; envoyés après l'enregistrement.
- **Réponse du propriétaire** : un formulaire (la demande en lecture, puis Accepter / Refuser et un message de refus facultatif) ; **annulation** : le bouton de retrait de l'item, avec confirmation.
- Un envoi limité à 20 demandes par minute et par compte.

## Ce qui a été fait

- Web 1295e05 :
  - table `bookings`, modèle `Booking`, statuts et types en enums, fabrique ;
  - `Domain::bookingType()` (logement : meublé → séjour, non meublé → visite) ; règle `book` d'`OfferPolicy`, `BookingPolicy` (`cancel`, `answer`, back-office en lecture) ;
  - formulaires `offer.booking` et `offer.booking.answer`, routes `POST /v1/offers/{offer}/bookings`, `/v1/bookings/{booking}/answer|cancel`, `BookingService`, `BookingNotifier`, `BookingMail` ;
  - listes « Mes réservations » et « Réservations reçues », liens du menu ;
  - fiche : `booking` (bouton) ; ressource Filament `BookingResource` (liste, fiche) ;
  - textes fr, en, de ; tests (`BookingApiTest`, `BookingResourceTest`) ; OpenAPI.
- Web 8fef5bf : bornes des dates. Mobile 0b740cc : barre fixe de la fiche (prix + bouton, prix retiré sous le titre), route `/offers/[id]/book`, calendrier borné ; bcea8a6 : test du calendrier réparé.
- Simulateur : demande d'un séjour envoyée (3 jours × 25 000 = 75 000 FCFA), e-mail « Nouvelle demande » reçu par le propriétaire (Mailpit), demande vue dans « Mes réservations » avec « Annuler la demande ».
- Suites : serveur 542 verts, app 255 verts (47 suites), tsc propre. QA indépendante : NO-GO (suite du calendrier non exécutée), corrigé, puis GO.

## Ce que je n'ai pas vérifié

- Le côté propriétaire dans l'app (« Réservations reçues », formulaire de réponse) et l'annulation au simulateur : couverts par les tests de l'API, pas vus à l'écran (il faudrait se connecter à un autre compte, avec un code par e-mail).
- Un visiteur non connecté : le bouton mène à la connexion ; le retour sur la fiche après connexion n'est ni testé ni vu.
- L'envoi réel d'un SMS (l'expéditeur local écrit dans le journal) ; la ressource du back-office dans un vrai navigateur (tests Livewire seulement).

## Ce qui reste en suspens

- Rien de demandé. À décider plus tard si besoin : prévenir dans l'app (pastille, notifications push), bloquer les dates acceptées, rappeler une demande restée sans réponse.
