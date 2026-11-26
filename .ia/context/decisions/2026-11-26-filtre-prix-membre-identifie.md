# `2026-11-26` — Filtre « Prix » et filtre « Membre identifié »

- **Type** : décision
- **Question posée** : « filtre loyer renommer en prix ; ajouter filtre membre identifié (tous, oui, non) ».
- **Ce qui est retenu** :
  - le filtre `price` des listes d'offres s'appelle « Prix » (`offer::offer.filters.price`), quel que soit le domaine ; ailleurs (formulaire, fiche), le libellé du domaine (« Loyer ») reste ;
  - un filtre `identified` (« Membre identifié »), en segments Tous / Oui / Non, par défaut Tous : « Oui » garde les offres dont l'éditeur est identifié (`identified_at` rempli), « Non » les autres. Il est dans la recherche et les favoris, pas dans « Mes logements » (toutes les offres y sont du membre).
- **Tranché par** : humain ; session (pas dans « Mes logements » ; libellé du domaine gardé hors des filtres)
