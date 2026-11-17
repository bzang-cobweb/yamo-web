# RETEX — 2026-11-20-canaux-email-telephone

## Ce qui s'est produit deux fois

- Un réglage transverse a laissé un endroit oublié, trouvé par qa-check : ici la recherche globale du back-office, au chantier précédent `ensureFree`. Dans les deux cas, la carte des usages (sous-agent Explore) ne cherchait pas les « attributs indirects » : recherche globale, unicité par le modèle.
  - **Règle candidate** (marketplace, `bza:lead`) : avant un changement transverse, la cartographie liste aussi les usages indirects d'une colonne (recherche, tri, filtres, unicité, exports), pas seulement les formulaires et les écrans.
- L'utilisateur répond hors des questions posées et laisse des questions sans réponse : c'est la troisième fois. Les défauts du contrat ont tenu chaque fois.
  - **Règle candidate** (projet) : chaque question posée porte un défaut écrit dans le contrat, et le registre dit quand ce défaut a été appliqué.

## Règles ré-implémentées / domiciliées

- Domiciliée : les canaux actifs et le libellé de l'identifiant (`AuthChannels`). Formulaires, requêtes, back-office, commande et API en dépendent.
- Ré-implémentée : la règle « au moins un des deux canaux » existe sous trois formes, à cause de trois mécanismes de validation : `required_without` (formulaires de l'API), Filament (`requiredWithout`) et la commande (`Validator`). Toutes passent désormais par `AuthChannels`.
