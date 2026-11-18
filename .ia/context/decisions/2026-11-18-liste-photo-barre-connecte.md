# `2026-11-18` — Photo dans les items de liste ; pas de langue dans la barre une fois connecté

- **Type** : décision
- **Remplace en partie** : `2026-11-18-accueil-offres-du-pays.md`, pour la langue dans la barre d'un membre.
- **Question posée** : aucune. Consigne de l'humain :
  > 1. enlever le sélecteur de langue de la barre du haut lorsqu'on est connecté
  > 2. ajouter une photo dans ListItem
  > 3. le rendu de la liste, afficher la photo au dessus du titre et du sous titre
- **Ce qui est retenu** :
  - **Barre d'un membre** : le logo et le drapeau, sans sélecteur de langue. La langue d'un membre se règle dans les préférences du compte. Pour un visiteur, la barre ne change pas.
  - **`ListItem`** gagne une photo (`image`, URL ou `null`), servie dans la réponse des listes.
  - **Offres** : l'URL de la première photo de l'offre (collection `photos`).
  - **Rendu** :
    - dans la vitrine, la photo s'affiche au-dessus du titre et du sous-titre, au format 16:9 ; sans photo, un emplacement neutre avec une icône ;
    - dans les listes du profil, la photo s'affiche au-dessus du titre si l'item en a une, et rien sinon (les adresses n'ont pas de photo).
- **Motif** : une vitrine d'offres se parcourt par les photos.
- **Portée** : module Form (`ListItem`), module Offer (listes), app mobile (barre, listes).
- **Corollaires** :
  - la photo servie est l'originale ; des miniatures (conversions medialibrary) viendront si le poids pose problème ;
  - les offres de démonstration n'ont pas de photo.
- **Tranché par** : humain
