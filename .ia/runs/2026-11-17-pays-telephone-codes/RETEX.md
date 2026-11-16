# RETEX — chantier 2026-11-17-pays-telephone-codes

## Ce qui s'est produit deux fois → règles candidates

1. **Scramble refuse ce qu'il ne sait pas typer**, troisième fois (`plainTextToken`,
   `birth_date`, puis le jeton rendu par un service). Règle candidate, désormais
   confirmée : une valeur calculée qui finit dans une réponse passe par une méthode au
   type de retour déclaré (`Challenges::token(): string`).
2. **Les routes typées d'Expo ne se régénèrent pas** pour une nouvelle route tant que
   Metro n'est pas relancé (`tsc` échoue sur `router.push('/sign-up')`). Règle
   candidate : après l'ajout d'un fichier dans `src/app/`, relancer Expo avant `tsc`.
3. **Dans le simulateur, une saisie juste après la fermeture d'une liste ou d'un
   clavier part dans le champ précédent.** Règle candidate : capture d'écran après
   chaque changement de focus, avant de taper.

## Autres leçons

- **zsh ne découpe pas une variable en arguments** : une liste de chemins pour
  `git add` passe par `--pathspec-from-file`.
- **`withToken()` persiste entre deux requêtes d'un même test** : `flushHeaders()` et
  `forgetGuards()` avant un appel invité.
- Le collage du simulateur abîme les caractères accentués (« Inès »). C'est l'outil,
  pas l'app.

## Règles : ré-implémentée ou domiciliée ?

- **Domiciliées** :
  - le cycle de vie d'un code dans `VerificationCodeService` (création, vérification,
    renvoi, masquage), partagé par les quatre opérations ;
  - côté mobile, le parcours « formulaire puis code » dans `ChallengeFlow`, partagé
    par la connexion, l'inscription et les deux changements ;
  - l'unicité revérifiée à la validation, dans `Challenges::ensureFree`.
- **Ré-implémentée** : l'unicité de l'e-mail et du téléphone est vérifiée à la fois
  dans les requêtes (`RegisterRequest`, `PhoneChangeRequest`, `EmailChangeRequest`) et
  à la validation du code. Les deux vérifications sont voulues, mais la règle vit à
  deux endroits.
