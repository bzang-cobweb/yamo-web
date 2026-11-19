# RETEX — 2026-11-19-reservations-logements-profil

- **La liste générique a grandi par besoins concrets** (barre du bas, boutons de carte, compteur, pastille, titre selon filtres) : chaque capacité est servie par Laravel et reste optionnelle ; l'app n'a pas d'écran spécifique aux réservations. Domicile : `Modules/Form/app/Support/ListItem.php` et `Listing.php`.
- **Filtres de l'accueil réutilisés** : factorisés dans un trait plutôt que copiés.
- **`git add` qui échoue sur un chemin déplacé** laisse un commit partiel quand on enchaîne sans vérifier : vu une fois, rattrapé par `--amend` avant tout push. Règle candidate (marketplace) : après un déplacement de fichier, ajouter par les nouveaux chemins seulement et relire `git show --stat` avant de passer à la suite.
- **Données de test dans la base locale** (demande temporaire, jobs de file) : créées pour voir l'écran, effacées ensuite ; à dire à l'humain à chaque fois.
