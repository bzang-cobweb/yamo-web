# RETEX — nuit du 2026-11-19

- **Session coupée à l'ouverture** : l'audit délégué (`bza-dev:auditor`) est mort avec la session sans rien écrire. Repris à la main, plus vite que relancer un sous-agent. Règle candidate : pour un audit de trois concepts sur des fichiers déjà connus, l'audit inline suffit.
- **Assertion trop lâche attrapée par le test lui-même** : `queryByText('Photos')` passait même avec le libellé affiché (texte imbriqué avec « * »). Deuxième fois qu'un test « absent » passe pour une mauvaise raison (cf. la vérification d'ordre par JSON). Règle candidate : tout test d'absence a son pendant de présence dans le même test.
- **Routes typées d'Expo** : `tsc` refuse une nouvelle route tant que Metro n'a pas régénéré les types. Lancer Metro avant `tsc` quand on ajoute une route.
- Règle domiciliée : la forme d'un avis dans `OfferReviewResource` (fiche et liste), au lieu de deux tableaux construits à la main.
