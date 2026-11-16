# RETEX — chantier 2026-11-16-textes-servis-par-api

## Ce qui s'est produit deux fois → règles candidates

1. **L'essai réel a encore trouvé ce que les tests ne voyaient pas**, comme au chantier
   photo de profil. Cette fois :
   - un « serveur arrêté » répondait 404 par le routeur DDEV au lieu de couper la
     connexion ;
   - l'ordre des middlewares rendait le 401 dans la mauvaise langue.

   La règle candidate du chantier précédent est confirmée : **un essai réel est
   obligatoire pour tout ce qui touche le réseau ou le démarrage**. Ajout : simuler
   l'indisponibilité en arrêtant le serveur, et pas seulement en coupant le réseau.
2. **Un faux de test qui diffère du vrai** :
   - le `FormData` et la langue par défaut de jest ;
   - le mode debug qui enrichit les réponses JSON (`exception`, `file`) ;
   - une règle d'accès sans paramètre jamais évaluée pour un invité.

   Règle candidate : dans un test d'API, comparer le champ utile (`assertJsonPath`)
   plutôt que la réponse exacte, sauf si la forme exacte est le sujet du test.
3. **Une liste qui doit rester alignée entre deux dépôts** : les clés `ApiMessageKey`
   côté mobile et les fichiers `mobile.php` côté web, comme `openapi.json`. Aucun test
   ne croise les deux dépôts : seul qa-check l'a vérifié, avec `curl`. C'est candidat à
   un script de contrôle lancé par le mobile contre l'API locale.

## Autres leçons

- **Laravel trie les middlewares par priorité** : un middleware ajouté au groupe `api`
  passe après `Authenticate`. `prependToPriorityList` règle l'ordre.
- **Les corrections de l'humain avant validation** (aide et erreur distinctes,
  rangement des médias) ont été intégrées au contrat **avant** le code. Aucune reprise.

## Règles : ré-implémentée ou domiciliée ?

- **Domiciliées** :
  - les textes de l'app dans `MobileMessages` (web) et `translate` (mobile), avec une
    seule exception locale, `OFFLINE_MESSAGES` ;
  - les refus d'API traduits dans `TranslatedApiErrors` ;
  - le rangement des médias dans `ModuleFieldPathGenerator` ;
  - l'aide et l'erreur d'un champ dans `FieldShell` seul : la duplication dans la case
    à cocher et l'interrupteur a été retirée.
- **À surveiller** :
  - la gestion des erreurs d'API reste dupliquée entre `DynamicForm`, `FormLoader` et
    `AvatarPicker` (déjà relevé au chantier précédent : 3 copies maintenant) ;
  - candidat urgent à une fonction partagée `messageOf(error, t)`.
