# `2026-11-27` — Le paquet Form n'impose ni garde ni accès

Complète : 2026-11-27-paquet-form.md

- **Type** : décision
- **Question posée** : « form package a ceci auth:sanctum il faut accepter tous les utilisateurs », précisé : « chaque utilisateur du package doit pouvoir choisir si c'est ouvert au visiteur, quel guard pour les visiteurs, ... le package ne doit rien fixer ».
- **Ce qui est retenu** :
  - la configuration du paquet (`form`) porte `guard` (la garde qui lit le compte ; `null` : celle de Laravel par défaut), `routes` (activées, préfixe, préfixe des noms, middlewares) et `uploads` (`guests` : un visiteur peut-il envoyer un fichier ; `middleware`) ; le paquet ne nomme plus `sanctum` ;
  - un seul endroit lit le compte, `Bza\Form\Support\FormAuth` : formulaires et listes construits pour lui, propriétaire d'un fichier envoyé, vérification d'un fichier dans un formulaire ; sans visiteurs, la route d'envoi exige `auth` sur la garde choisie ;
  - un fichier de visiteur n'a pas de compte (`form_uploads.user_id` facultatif, migration additive) : détenir son identifiant aléatoire suffit, seulement si `uploads.guests` est vrai ;
  - l'application garde son comportement : `config/form.php` choisit `sanctum`, l'API sous `/api` en `api.`, l'envoi réservé aux comptes.
- **Corollaire** : la clé étrangère vers `users` reste (cascade à la suppression du compte) ; un projet sans table `users` devrait l'adapter.
- **Tranché par** : humain
