Remplacée en partie par : 2026-11-19-acces-avis-backoffice.md (ligne de note de l'item : étoiles seules en bas à droite)
# `2026-11-19` — Item d'offre : titre saisi en sous-titre, détails et prix en gras

- **Remplace** : `2026-11-19-contrat-avis-offres.md`, sur un seul point — le contenu de `highlight` pour les offres.
- **Type** : décision
- **Question posée** : demande directe (« mettre le titre dans le sous titre de ListItem », « mettre le sous titre actuel dans highlight »), puis : où va le dernier commentaire ?
- **Réponse** : la demande, puis « Drop it from the list ».
- **Ce qui est retenu** :
  - un item d'offre se lit dans cet ordre :
    1. le titre construit par le domaine (« Appartement - meublé - Yaoundé ») ;
    2. en sous-titre, le titre saisi (« Appartement meublé haut standing »), omis s'il est identique au titre ;
    3. la note ;
    4. en gras (`highlight`), les détails et le prix (« 3 chambres · 60 000 FCFA / jour ») ; dans « Mes logements », le statut vient en tête (« Publiée · … »).
  - le dernier commentaire ne figure plus dans les listes ; les avis se lisent sur la fiche seulement.
- **Motif** : la demande de l'humain.
- **Portée** : `OfferBrowseListing`, `OfferListing` ; la relation `latestReview` est retirée.
- **Corollaires** : le reste du contrat des avis est inchangé (moyenne et nombre dans les listes, section de la fiche, modération).
- **Tranché par** : humain
