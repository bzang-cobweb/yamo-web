# RETEX — 2026-11-19-champ-upload-photos

- **Lancer le formateur du projet, pas un formateur par défaut.** `npx prettier --write` sans configuration de projet a reformaté toute la feature `form` de l'app (guillemets, largeur). Rattrapé en restaurant les fichiers et en réappliquant les seuls changements. Règle candidate (projet) : dans `mobile`, ne formater qu'avec `npm run lint` / les options du projet, jamais `prettier --write` sur un dossier.
- **Rendre un champ obligatoire casse tous les tests qui enregistrent** : 7 tests hors du module touché (Domain, Translation, CountrySpace). Comme aux chantiers précédents, la suite complète avant commit l'a montré. Une aide de test partagée (`MakesFormUploads`) a évité de dupliquer la création d'un fichier en attente.
- **Textes de l'app chargés au démarrage** : la vérification au simulateur montrait les clés tant que l'app n'était pas relancée (déjà vu, deuxième fois : règle candidate « relancer l'app après tout ajout de texte »).
- Règle ré-implémentée ? Non : l'optimisation reprend la façon de `AvatarService` (mêmes outils, même schéma) ; la validation des fichiers n'existe qu'une fois, dans `Field::fileRules()`.
- Règle domiciliée : limites d'un champ de fichiers dans `Field` (servies à l'app et validées par le serveur depuis la même déclaration).
