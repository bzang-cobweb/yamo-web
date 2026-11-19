# `2026-11-19` — Contrat « Profil : réservations, logements, demandes par logement », validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-19-reservations-logements-profil.md` ; avant cela : comment afficher réservations et logements, où (onglet ou profil), comment traiter chaque demande.
- **Réponse** : « ok avec le profil groupé », les ajouts demandés (plus de menu du bas dans les pages du profil, filtres, boutons fixes en bas, compteur des demandes), puis « Approved ».
- **Ce qui est retenu** :
  - profil en groupes Compte / Voyageur / Hôte ; « Réservations reçues » retirée du menu ;
  - listes du profil ouvertes par-dessus les onglets (sans menu du bas) ;
  - « Mes réservations » : filtres (statut À venir / En attente / Passées, ville, type), barre « Rechercher un logement » ;
  - « Mes logements » : filtres de l'accueil + statut, barre « Ajouter un logement », compteur des demandes en attente par carte ;
  - « Demandes · <logement> » : cartes avec « Accepter » (confirmation) et « Refuser » (message facultatif) ;
  - listes génériques : barre du bas, boutons de carte, compteur de carte, décrits par Laravel.
- **Motif** : la demande de l'humain.
- **Portée** : modules Form (listes), Auth (menu), Offer (listes, filtres, réponse) ; app (profil, listes, routes).
- **Corollaires** : choix de l'agent validés avec le contrat : trois statuts groupés, acceptation sur confirmation, refus par le formulaire existant, groupe « Compte ».
- **Tranché par** : humain
