Remplacée en partie par : 2026-11-19-fiche-localisation-accordeon.md

Remplace en partie : 2026-11-18-fiche-offre.md et 2026-11-19-fiche-offre-ordre.md (la rue est désormais servie ; le bloc « Adresse » devient « Localisation »).

# `2026-11-19` — Fiche d'une offre : bloc « Localisation » avec l'adresse complète et l'indication

- **Type** : décision
- **Question posée** : demande : « rajouter un bloc avec le label localisation dans le détail de l'offre avant caractéristiques qui affiche l'adresse et sa description ». Comme la rue n'était jamais servie (décision 2026-11-18-fiche-offre), j'ai demandé quelle adresse afficher : code postal et ville (recommandé), adresse complète, ou rue réservée aux membres connectés.
- **Réponse** : « full address and indication ».
- **Ce qui est retenu** :
  - la fiche sert l'adresse complète de l'offre en une ligne (`address` : « Rue du Lac 4, 1200 Genève »), à tout visiteur, connecté ou non ;
  - dans l'app, un bloc « Localisation » avant les caractéristiques : l'adresse, puis l'indication si elle existe ; sans adresse, pas de bloc ;
  - il remplace le bloc « Adresse » (indication seule) ; la ville reste aussi dans les caractéristiques.
- **Motif** : la demande de l'humain.
- **Risque assumé** : la rue d'un logement est publique, y compris pour un visiteur non connecté. Pour revenir en arrière : ne plus servir `street` dans `OfferDetailResource`.
- **Portée** : `OfferDetailResource` ; textes de l'app (`offer.location`) ; `OfferDetailScreen` (mobile).
- **Corollaires** : les suggestions de rue (décision 2026-11-19-contrat-adresse-offre-recherche) et la fiche montrent désormais les mêmes rues.
- **Tranché par** : humain
