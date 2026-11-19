# RETEX — 2026-11-19-centre-notifications

- **Demande reformulée en cours de route** (pastilles sur les listes → module et onglet) : le travail non commité a été défait proprement (migration annulée puis supprimée, fichiers restaurés) avant de reprendre. Règle candidate : commiter par petits pas ce qui est acquis, pour qu'un changement de cap ne défasse que l'incertain.
- **Les textes de l'app sont chargés au démarrage** : une clé nouvelle s'affiche brute dans une app déjà ouverte. Attendu, mais à dire à l'humain à chaque ajout de texte.
- **`Intl.RelativeTimeFormat` absent du moteur de l'app** : vu seulement au simulateur. Règle candidate (projet) : ne pas compter sur les API `Intl` avancées sans repli ; les textes servis par Laravel sont le repli naturel.
- **Le garde-fou refuse `git add -A`**, même pour enregistrer des suppressions déjà indexées : `git rm` puis `git commit` sans chemins suffit.
- **OpenAPI généré sur une base locale dont un réglage change le schéma** (âge minimum) : vider le réglage le temps de la génération, puis le remettre. S'est produit trois fois : règle candidate (projet) — générer l'OpenAPI sur une base de test ou rendre les règles indépendantes des réglages en documentation.
