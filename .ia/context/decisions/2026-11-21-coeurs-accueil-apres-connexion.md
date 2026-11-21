# Accueil : les cœurs du membre connecté, même si l'accueil était ouvert avant la connexion

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-19-contrat-vedette-favoris.md

## Décision
Le serveur garde sa règle : un cœur sur chaque carte de l'accueil pour un membre connecté, sauf
sur ses propres logements. Dans l'app, la clé de cache des listes (`useListDefinition`)
comprend l'identifiant du compte connecté. Une liste ouverte avant la connexion, comme
l'accueil, est donc rechargée après la connexion ou la déconnexion, et les cœurs suivent le
compte.

## Motif
Demande de l'humain du 2026-11-21 : « mettre le cœur sur toutes les cards de l'accueil qui ne
sont pas ceux de l'utilisateur connecté ». Vérifié sur la base locale : pour le compte de
démonstration, les quatre logements des autres membres ont un cœur, et les seize autres sont
les siens.
