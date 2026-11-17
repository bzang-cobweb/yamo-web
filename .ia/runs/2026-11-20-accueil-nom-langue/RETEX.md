# RETEX — 2026-11-20-accueil-nom-langue

## Ce qui s'est produit deux fois

- Les variables zsh qui contiennent une liste de chemins ne se découpent pas : `git add -- $A` a échoué, et rien n'a été commité au premier essai, dans ce chantier comme dans le précédent.
  - **Règle candidate** (pour tous les projets) : dans zsh, mettre les chemins d'un commit par chemin dans un tableau `A=(…)`, et passer `"${A[@]}"`.
- Une page publique qui suit la langue du navigateur casse les tests qui supposaient la langue par défaut : le client de test de Laravel envoie `Accept-Language: en`.
  - **Règle candidate** (projet) : un test qui attend un texte dans une langue fixe la langue de sa requête.

## Règles ré-implémentées / domiciliées

- Domiciliée : le stockage de l'appareil (`shared/storage/device-storage.ts`), qui avait jusqu'ici une seule copie, propre au jeton.
- Ré-implémentée, en connaissance de cause : le choix de la langue d'une requête existe en deux middlewares. `SetLocale` (API) lit le compte, puis `Accept-Language` ; `SetSessionLocale` (web) lit la session, puis `Accept-Language`. Leur source commune, `Locales`, est partagée.
