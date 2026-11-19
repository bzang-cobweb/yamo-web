Remplacée en partie par : 2026-11-19-fiche-localisation.md (la rue est servie ; bloc « Localisation »)

# `2026-11-19` — Fiche d'une offre : prix sans libellé, ordre des blocs et des caractéristiques

- **Type** : décision
- **Question posée** : demande directe de l'humain.
- **Réponse** : « offre détail : 1) enlever loyer sur le prix, caractéristiques : type de logement, meublé, ville, catégorie, ..., 2) description après le prix, 3) adresse après la description ».
- **Ce qui est retenu** :
  - le prix s'affiche seul, sans son libellé (« Loyer ») ;
  - ordre de la fiche : photos, titre, prix, description, adresse, caractéristiques, puis le reste (avis, date) ;
  - l'adresse est le bloc des indications sur l'adresse (`address_description`), sous un titre « Adresse » ; sans indications, le bloc n'apparaît pas. La rue n'est toujours jamais servie ;
  - les caractéristiques commencent par le type de logement, meublé, la ville, la catégorie, puis les autres attributs dans l'ordre du domaine. La ville quitte la ligne sous le prix ;
  - l'ordre de tête est déclaré par le domaine (`Domain::leadingFacts()`), le serveur sert les caractéristiques dans cet ordre.
- **Motif** : la demande de l'humain.
- **Portée** : `OfferDetailResource`, `Domain`, `RentalDomain` ; `OfferDetailScreen` (mobile).
- **Corollaires** : lecture de « adresse » par l'orchestrateur : indications seulement, la ville étant passée dans les caractéristiques. Le libellé du prix reste servi par l'API (`price.label`), l'app ne l'affiche plus.
- **Tranché par** : humain (lecture de « adresse » par l'orchestrateur)
