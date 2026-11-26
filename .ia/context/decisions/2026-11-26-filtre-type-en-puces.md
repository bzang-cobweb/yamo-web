Remplacée par : 2026-11-26-filtre-type-select.md

# `2026-11-26` — Filtre « Type » des notifications en puces ; le type « Message »

- **Type** : décision
- **Question posée** : « remplacer le filtre type par les chips » ; « pourquoi les types partenaire et message n'apparaissent pas dans les filtres des notifications ? » → « corriger les deux ».
- **Ce qui est retenu** :
  - le filtre `type` des notifications est un champ `chips` : un ou plusieurs types ; `filter[type][]=booking&filter[type][]=offer_message` garde les notifications de chacun ; une valeur seule (`filter[type]=booking`) marche encore ; un type inconnu est ignoré ;
  - il ne propose toujours que les types présents dans les notifications du lecteur ;
  - le module Offer inscrit un second type, « Message » (`offer_message`, `OfferMessageReceived`), sans statut ;
  - « Statut » s'affiche quand chaque type choisi a des statuts (« Réservation », « Partenaire ») ; ses options sont celles de chaque type choisi (l'app lit un parent en puces : `dependentOptions`). « Logement » et « Type de location » restent pour « Réservation » seule.
- **Motif** : choisir plusieurs types d'un geste, comme les autres filtres en puces (2026-11-26-filtres-en-puces) ; un message se filtre comme le reste.
- **Portée** : la liste des notifications (serveur et app).
- **Corollaires** : un module qui inscrit un type sans statut n'a rien d'autre à faire : « Statut » ne s'affiche pas pour lui.
- **Tranché par** : humain (les puces, le type « Message ») ; session (plusieurs types à la fois, « Statut » selon les types choisis)
