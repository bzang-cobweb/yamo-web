# `2026-11-18` — Module Offer et profil de domaine ; premier domaine `rental`

- **Type** : décision
- **Question posée** :
  - quelle structure permet d'initialiser le projet selon un domaine (rencontre, location, boutique…) ;
  - valider le contrat `specs/2026-11-18-offres-et-domaines.md`.
- **Réponse** : « utilise donc le nom Offer partout et non Product », puis « Approved ».
- **Ce qui est retenu** :
  - **un seul socle de code**. Le domaine est un profil déclaré en code (interface `Domain`, `DomainRegistry`) et choisi par `config('offer.domain')` ;
  - **l'offre** a des champs communs et une colonne JSON `attributes`. Le schéma de cette colonne, écrit avec les `Field` du module Form, sert au formulaire de l'app, à la validation et au back-office ;
  - **les domaines sont des modules optionnels.** Une commande `project:init` et un skill viendront dans un chantier suivant ;
  - **premier domaine** : `rental`, la location de logements ;
  - le mot « Product » n'est employé nulle part.
- **Motif** : un projet opérationnel avec quelques réglages. Un correctif profite à tous les domaines, et l'app, décrite par le serveur, n'a pas à changer.
- **Portée** : nouveau module Offer, rôles et permissions, menu du profil.
- **Corollaires** :
  - sans domaine choisi, le module n'expose rien ; un domaine inconnu est une erreur ;
  - les photos ne s'ajoutent que depuis le back-office tant qu'il n'existe pas de champ « fichier » générique.
- **Tranché par** : humain
