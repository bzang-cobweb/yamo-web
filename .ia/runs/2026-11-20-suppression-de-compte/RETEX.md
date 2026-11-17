# RETEX — 2026-11-20-suppression-de-compte

## Ce qui s'est produit deux fois

- Ajouter la suppression douce a laissé des recherches d'unicité passer par `User::query()`, qui ignore les comptes supprimés, alors que l'index unique les compte. C'est arrivé deux fois : `UniquePhone`, vu par moi, puis `Challenges::ensureFree`, vu par qa-check.
  - **Règle candidate** (marketplace, `bza-laravel:create-model`) : ajouter `SoftDeletes` à un modèle impose de passer en revue chaque recherche d'unicité. Les règles `Rule::unique` interrogent la table directement et comptent déjà ces lignes ; les recherches par le modèle demandent `withTrashed()`.
- Un test qui vérifie un texte servi à un utilisateur connecté a échoué au hasard : la langue du compte vient de la factory, au hasard. C'est la deuxième fois après le chantier « accueil, nom, langue », qui fixait l'en-tête de la requête.
  - **Règle candidate** (projet) : un test qui attend un texte dans une langue fixe la langue de la requête et celle du compte.

## Règles ré-implémentées / domiciliées

- Domiciliée : la couleur des actions irréversibles réutilise `ERROR_COLOR` des formulaires, sans nouvelle constante.
- Ré-implémentée : la vérification « valeur encore libre » existe à deux endroits, `UniquePhone` à la saisie et `ensureFree` à la validation d'un code. Les deux comptent désormais les comptes supprimés. Les réunir reste à étudier.
- Tenue : qa-check, cette fois avec la consigne de ne toucher aucun fichier suivi, n'a rien remis à zéro.
