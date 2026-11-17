# RETEX — Adresse du compte (UserAddress) et suggestions

## Ce qui s'est produit

- **Un ajout arrivé avec la validation** (« autocomplete sur la rue ») portait une question de confidentialité : les adresses sont partagées entre comptes. Elle a été posée avant d'écrire du code, et la réponse est inscrite dans la décision.
- **`ddev exec php artisan tinker --execute '…$u…'` échoue** : DDEV repasse la commande à bash entre guillemets doubles, et `$u` est remplacé par une valeur vide. Contournement : pas de variable dans `--execute`, ou un fichier passé à `tinker`.
- **Le garde-fou a de nouveau refusé une commande** qui contenait le mot `id` (`->id`, `["id", …]`). C'est la troisième fois en deux chantiers.
- **Un script tinker a bloqué la session** (insertion puis attente) : il a fallu arrêter la tâche. Les essais sur la base locale passent mieux par des commandes courtes et vérifiables une à une.
- **Une entrée créée sur la base locale pendant mes essais** (super-admin) aurait pu être effacée par un nettoyage trop large. Mon nettoyage ne visait que les données marquées QA : c'est ce qui l'a protégée.

## Règles candidates

- **Marketplace (bza-sysadmin:ddev)** :
  - dans `ddev exec … --execute`, ne jamais utiliser de variable `$…` ; passer un fichier à `tinker` ;
  - un nettoyage de QA ne supprime que des lignes marquées par le QA (e-mail `@example.test`, valeur témoin), jamais « tout ce qui reste ».
- **Garde-fou** : le mot `id` dans le code PHP ou SQL d'une commande déclenche le refus réservé à l'outil interne `id`. Trois occurrences : à proposer au marketplace, pour que la règle ne vise que la commande `id` en tête.

## Règle ré-implémentée, règle domiciliée

- **Domiciliée** : le contrôle d'un point d'API demandé par le serveur vit dans `isApiPath` (app), partagé entre les envois et les suggestions.
- **Domiciliée** : la règle des adresses reste entièrement dans `AddressService`, suggestions comprises.
- **Ré-implémentée** : le libellé d'une suggestion est construit côté serveur (`Address::suggestionLabel()`) et relu par le back-office (`fromSuggestionLabel`). C'est une seule source, mais le libellé sert de clé. À remplacer par un identifiant si un composant d'autocomplétion le permet un jour.
