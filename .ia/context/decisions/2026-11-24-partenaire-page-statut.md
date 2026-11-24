# `2026-11-24` — La pastille du statut sur la page d'un partenaire, en haut à droite

- **Type** : décision
- **Question posée** : « mettre la pastille du statut en haut à droite et flottant sur la zone de contenu de la page de détail d'un partenaire ».
- **Ce qui est retenu** :
  - **API** (`GET /partners/{id}`) : `status` ({label, tone}) est servi aux comptes du partenaire seulement. Un visiteur ne voit qu'un partenaire affiché et n'en a pas besoin.
  - **Une seule règle** : la pastille est celle de « Mes partenaires » (en validation, refusé, archivé ; publié : « Affiché » ou « Pas affiché »). Elle vit maintenant dans `Partner::statusTag()`, que la liste et la page partagent.
  - **App** (`PartnerDetailScreen`) : la pastille flotte en haut à droite de la zone de contenu (position absolue). Le contenu défile dessous, elle reste en place.
- **Tranché par** : humain (la place) ; session (pour ses comptes seulement, la règle de « Mes partenaires »)
