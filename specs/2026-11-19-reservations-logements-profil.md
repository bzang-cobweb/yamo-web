# Contrat — Profil : « Mes réservations » et « Mes logements », demandes par logement

Validation : validé le 2026-11-19 (« Approved »)

Demande : « ok avec le profil groupé. On n'affiche plus le menu du bas dans les pages des liens du profil. Mes réservations : ajouter lien filtres (sur les 3 statuts, la ville, …). Ajouter bouton rechercher un logement dans zone bottom fixe qui va sur la home. Mes logements : ajouter lien filtres (comme sur la home). Ajouter bouton ajouter un logement dans zone bottom fixe qui ouvre le formulaire d'ajout, ajouter un compteur pour les demandes sur chaque card. Mais comment traiter séparément chaque demande ? »

## Ce que j'ai compris

### Le profil, en groupes
- Le menu du profil est rangé en groupes titrés :
  - **Compte** : informations personnelles, e-mail, téléphone, mot de passe, préférences, adresses ;
  - **Voyageur** : Mes réservations, Mes favoris ;
  - **Hôte** : Mes logements.
- « **Réservations reçues** » quitte le menu : les demandes se traitent depuis « Mes logements » (ci-dessous).
- Groupes et ordre servis par Laravel ; un groupe vide n'est pas affiché.

### Plus de menu du bas dans les pages du profil
- Une liste ouverte depuis le profil (Mes adresses, Mes logements, Mes réservations, Mes favoris, les demandes d'un logement) s'ouvre **par-dessus les onglets**, comme les formulaires : pas de menu du bas, la flèche (et la croix) ramènent au profil.

### Mes réservations (voyageur)
- Un lien « **Filtres** » (comme sur l'accueil), avec :
  - **Statut** : « À venir » (acceptées, pas commencées), « En attente », « Passées » (terminées, refusées, annulées) ; « Tous » par défaut ;
  - **Ville** du logement ;
  - **Type** : séjour ou visite.
- Une **barre fixe en bas** : « **Rechercher un logement** », qui ramène à l'accueil.
- Chaque carte : la pastille du statut en couleur (en attente orange, acceptée verte, refusée / annulée grise), les dates, le total ; « Annuler » tant que c'est possible ; toucher ouvre la fiche du logement.

### Mes logements (hôte)
- Un lien « **Filtres** », les mêmes que l'accueil (ville, prix, type, meublé, chambres, équipements, règles…) plus le **statut** de l'offre (publiée, brouillon, archivée).
- Une **barre fixe en bas** : « **Ajouter un logement** », qui ouvre le formulaire d'ajout (le lien d'ajout sous la recherche disparaît).
- Sur chaque carte, un **compteur des demandes en attente** (« 2 demandes en attente ») ; rien sans demande. Le toucher ouvre les demandes de ce logement.

### Traiter chaque demande (réponse à la question)
- L'écran « **Demandes · <logement> »** : les demandes de ce seul logement, la plus récente en tête, avec les filtres de statut (« En attente » par défaut).
- Chaque carte : le membre, les dates, les voyageurs, le total, son message, la pastille du statut, et **deux boutons sur la carte** :
  - « **Accepter** » : une confirmation (« Accepter la demande de Marie Curie du 3 au 6 mars ? »), puis c'est fait ;
  - « **Refuser** » : ouvre un petit écran pour un message facultatif, puis refuse.
- Une demande répondue garde sa carte, avec son nouveau statut et sans boutons ; le compteur du logement baisse ; le membre est prévenu (notification, e-mail, SMS) comme aujourd'hui.

### Générique (pour rester décrit par Laravel)
- Une liste peut servir une **barre fixe en bas** (un bouton qui ouvre un formulaire ou un écran de l'app).
- Une carte peut servir des **boutons d'action** (libellé, style, confirmation facultative, envoi ou formulaire).
- Une carte peut servir un **compteur** qui ouvre une autre liste filtrée.
- Les filtres des listes existent déjà (accueil) : réutilisés.

## Découpage

- Laravel :
  - menu du profil : groupes (`group` sur chaque lien, titres fr, en, de) ; « Réservations reçues » retirée du menu ;
  - listes : barre du bas, boutons d'action de carte, compteur de carte (module Form) ;
  - « Mes réservations » : filtres (statut groupé, ville, type), barre « Rechercher un logement » ;
  - « Mes logements » : filtres de l'accueil + statut, barre « Ajouter un logement », compteur des demandes en attente ;
  - « Demandes d'un logement » : la liste des demandes reçues filtrée par logement, boutons Accepter / Refuser (route d'acceptation directe ; refus par le formulaire existant, prérempli) ;
  - tests ; OpenAPI.
- App :
  - groupes du menu ; listes du profil hors des onglets (comme les formulaires) ;
  - liste générique : barre fixe du bas, boutons d'action avec confirmation, compteur ;
  - tests ; vérification au simulateur.
- Aucune dépendance ajoutée.

## Critères de succès (état observable)

- Le profil montre trois groupes titrés ; « Réservations reçues » n'y est plus.
- Ouvrir une liste du profil n'affiche pas le menu du bas.
- « Mes réservations » : filtres (statut, ville, type) qui restreignent la liste ; « Rechercher un logement » en bas ramène à l'accueil.
- « Mes logements » : filtres comme l'accueil plus le statut ; « Ajouter un logement » en bas ouvre le formulaire d'ajout ; chaque carte montre le nombre de demandes en attente.
- Toucher le compteur ouvre les demandes de ce logement ; « Accepter » (après confirmation) et « Refuser » (avec message facultatif) changent le statut, font baisser le compteur et préviennent le membre.
- Suites web et app vertes, tsc propre, OpenAPI à jour, simulateur vu, QA indépendante.
