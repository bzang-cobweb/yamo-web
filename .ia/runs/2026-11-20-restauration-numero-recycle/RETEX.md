# RETEX — 2026-11-20-restauration-numero-recycle

## Ce qui s'est produit deux fois

- Une course entre la vérification d'unicité et l'écriture, trouvée par qa-check. Au chantier « suppression de compte », c'était `ensureFree` qui ignorait les comptes supprimés ; ici, deux demandes simultanées. Les deux viennent d'une vérification faite à part de l'écriture.
  - **Domicile trouvé** : `Challenges::persist`, une transaction plus la conversion de la violation d'unicité en refus. Tout enregistrement d'un identifiant passe par là.
  - **Règle candidate** (marketplace, `bza-laravel:requete-service`) : toute écriture protégée par un index unique est faite en transaction, et capture `UniqueConstraintViolationException`. La vérification préalable n'est qu'un confort pour le message.
- Les réponses arrivent hors des questions posées. Cette fois, la réponse était ambiguë (« reset si… »). Une question de précision fermée l'a tranchée en un tour.
  - **Règle tenue** : quand une réponse libre peut se lire de deux façons sur un point de sécurité, reposer une seule question fermée plutôt que deviner.

## Règles ré-implémentées / domiciliées

- Domiciliée : la propriété d'un numéro (`PhoneOwnership`). Les règles d'unicité, la libération et l'inactivité vivent au même endroit.
- Réutilisé : le motif « étape suivante après le code » (`next: password`) sert maintenant aussi à la récupération (`next: restore`).
