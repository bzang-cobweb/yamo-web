# `2026-11-19` — Contrat « Réservations », validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-19-reservations.md` ; avant cela : que réserver, quel parcours, bloquer les dates, comment prévenir, le prix sur la fiche.
- **Réponse** : « Furnished: dates », « Request, owner accepts », « No, owner manages », « In the app, E-mail, SMS », « Only in the bar », puis « Approved ».
- **Ce qui est retenu** :
  - fiche : barre fixe en bas, prix + « Réserver » (meublé) ou « Demander une visite » (non meublé) ; le prix n'est plus sous le titre ;
  - une demande (dates, voyageurs, message ; ou date de visite) que le propriétaire accepte ou refuse ; le membre peut annuler ; aucun paiement ; dates non bloquées ;
  - « Mes réservations » et « Réservations reçues » dans le profil ; e-mail et SMS à chaque demande, réponse, annulation ;
  - back-office : réservations en lecture seule.
- **Motif** : la demande de l'humain.
- **Portée** : module Offer (table `bookings`, domaine, formulaires, listes, notifications, Filament), app (barre fixe de la fiche).
- **Corollaires** : choix de l'agent, validés avec le contrat : visite pour le non meublé, une seule demande en attente par membre et par offre, message de refus facultatif, annulation possible jusqu'au début du séjour, back-office en lecture.
- **Tranché par** : humain
