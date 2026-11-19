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
