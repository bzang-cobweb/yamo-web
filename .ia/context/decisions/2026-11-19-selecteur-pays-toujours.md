Remplace en partie : 2026-11-18-accueil-offres-du-pays.md et 2026-11-18-ecran-entree-selecteurs.md (corollaire : le drapeau ne rouvrait la liste qu'avec plusieurs pays).

# `2026-11-19` — Barre du haut : le sélecteur de pays reste, même avec un seul pays ouvert

- **Type** : décision
- **Question posée** : demande directe de l'humain : « laisser uniquement le pays cameroun activé ; même si on a un seul pays activé laisser le sélecteur de pays dans la barre fixe du haut ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - le drapeau de la barre du haut garde sa flèche et ouvre toujours la liste des pays (avec recherche), même s'il n'y en a qu'un, coché ;
  - l'écran d'entrée ne change pas : un pays seul y est montré choisi ;
  - dans la base locale, seul le Cameroun est ouvert (les 244 autres pays fermés le 2026-11-19).
- **Motif** : demande de l'humain.
- **Portée** : `CountrySwitcher`, `CountryContext` (l'indicateur `canChange` est retiré), `CountryProvider` (mobile) ; base locale.
- **Corollaires** : choisir le pays déjà courant ne recharge rien. Le réglage des pays ouverts d'une autre installation (production) n'est pas touché : il se fait dans le back-office.
- **Tranché par** : humain
