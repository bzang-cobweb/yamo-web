Remplacée en partie par : 2026-11-19-premiere-section-ouverte.md (seule la première section est ouverte à l'arrivée)

Remplacée en partie par : 2026-11-19-contrat-paiement-visibilite-avis.md

Remplace en partie : 2026-11-19-fiche-localisation.md (le pays s'ajoute à l'adresse) et 2026-11-19-nuit-tous-les-avis.md (bouton dès deux avis).

# `2026-11-19` — Fiche d'une offre : pays dans l'adresse, séparateur, « Voir tous les avis », sections repliables

- **Type** : décision
- **Question posée** : demande directe de l'humain : « 1. toujours ajouter le pays dans l'adresse 2. ajouter le séparateur à la fin du bloc localisation (location en anglais) 3. ajouter un bouton voir tous les avis si plus d'un avis 4. mettre les blocs localisation, caractéristiques et avis dans un accordéon ».
- **Réponse** : la demande elle-même ; la forme est choisie par la session.
- **Ce qui est retenu** :
  - l'adresse servie se termine toujours par le pays, dans la langue de la requête (« Rue de Kotto, 00237 Douala, Cameroun ») ;
  - le bloc « Localisation » (« Location » en anglais) finit par un trait de séparation, comme les lignes des caractéristiques ;
  - dès deux avis, un bouton « Voir tous les avis » (« See all reviews ») ouvre la liste complète ; avec un seul avis, pas de bouton. Le texte ne porte plus le nombre ;
  - Localisation, Caractéristiques et Avis sont des sections repliables (composant générique `Accordion` de l'app) : le titre et une flèche ; toucher le titre replie ou déplie la section.
- **Choix de la session** :
  - les trois sections sont **ouvertes** à l'affichage de la fiche ;
  - chacune s'ouvre et se ferme seule (ouvrir l'une ne ferme pas les autres) ;
  - le séparateur ne vaut que pour la localisation : la dernière ligne des caractéristiques et le dernier avis en ont déjà un.
- **Motif** : demande de l'humain.
- **Portée** : `OfferDetailResource` et `OfferController` (web), textes `offer.allReviews` ; `Accordion`, `OfferDetailScreen`, `OfferReviews` (mobile).
- **Corollaires** : le pays vient de `Address::subtitle()`, déjà utilisé par le carnet d'adresses.
- **Tranché par** : humain (forme par la session)
