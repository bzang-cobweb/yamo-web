# RETEX — chantier 2026-11-18-etapes-mot-de-passe-oublie

## Ce qui s'est produit deux fois → règles candidates

1. **Les routes typées d'Expo** ont encore exigé de relancer Metro après l'ajout d'une
   route (`/forgot-password`). La règle candidate du chantier précédent est confirmée :
   après un nouveau fichier dans `src/app/`, relancer Expo avant `tsc`.
2. **Les captures du simulateur ont un temps de retard** (encore ici) : il faut une
   seconde capture avant de conclure qu'un appui n'a rien fait. La règle candidate est
   confirmée : capture après chaque changement de focus, et en cas de doute, une
   seconde capture.
3. **`git add` d'une liste qui contient des fichiers supprimés** échoue, parce que la
   suppression est déjà indexée par `git rm`. Règle candidate : ajouter seulement les
   chemins qui existent, puis commiter la liste complète.

## Autres leçons

- La config d'un module nwidart (`config/config.php`) est fusionnée sous la clé du
  module (`auth`) : c'est la « config auth » à laquelle on peut ajouter des clés.
- Un composant générique (`DynamicForm`) qui appelle un service fait échouer les tests
  qui ne simulent pas `@/shared/config` : toute suite qui le monte doit le simuler.

## Règles : ré-implémentée ou domiciliée ?

- **Domiciliées** :
  - reconnaissance d'un identifiant de connexion dans `Identifiers` (elle était privée
    à `LoginService`) ;
  - unicité du téléphone dans la règle `UniquePhone`, partagée par l'inscription et
    le changement de numéro ;
  - masquage d'une destination dans `VerificationCodeService::maskDestination` ;
  - en-tête de retour dans `BackHeader`.
- **Encore à deux endroits** : l'unicité est vérifiée par les règles des champs, puis
  à nouveau à la validation du code (`Challenges::ensureFree`). C'est voulu, mais la
  règle vit en double.
