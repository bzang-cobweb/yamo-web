# Nuit du 2026-11-28 — Yamo
Branche : nuit/2026-11-28 (web depuis main@2e8728c6, mobile depuis main@c817da5)
État : terminée avec réserves

## Ce qui attend votre attention
- **Le mot de passe revient** : le parcours est maintenant identifiant → code → mot de passe → (inscription), dans un
  panneau. Cela remplace la connexion sans mot de passe d'hier.
- **Rien n'a été vu dans le simulateur** : le parcours est prouvé par les tests et par un passage de bout en bout
  contre le serveur local, pas à l'écran.
- **Sécurité, corrigé** : un numéro saisi sans code (autre contact de l'inscription) permettait de se connecter par SMS
  au compte d'un autre ; seul un numéro prouvé par code identifie ou retient un compte maintenant. Conséquence : un
  compte qui ne vérifie pas son numéro peut le perdre au profit de qui le prouve.
- **Âge minimum** : s'il est réglé (c'est le cas en local), l'inscription demande aussi la date de naissance.
- **Compte en attente de suppression** : il se récupère avec le seul code, sans mot de passe (comme avant).
- Dossier non suivi `database/migrations/migration-viewer/` (autre session) laissé en place, ni touché ni commité.

## Fait
- Connexion dans un panneau (contrat `specs/2026-11-28-connexion-panneau.md`) : onglets d'un visiteur (titre,
  sous-titre, message, bouton), panneau à hauteur maximale, « Bon retour » avec la mémoire de l'appareil, code (envoi
  à 6 chiffres, nouveau code, « Essayer autrement »), mot de passe et « Mot de passe oublié ? », inscription, tous les
  pays aux champs téléphone. Web `33c98bdd`, `425f3db2`, `f0afa07f`, `e2675614`, `2ff586f7`, `db24565d` ; mobile
  `6a1fbed`, `9eba6bf`, `f7fbcfd`, `99e48a4`, `0b19e9e`, `6901ad3`.
- Preuves : web 1028 tests verts, Pint propre ; mobile tsc propre, 76 suites / 491 tests verts ; bout en bout contre le
  serveur local (inscription 201, connexion, mot de passe faux 422, défi réutilisé 422) ; qa-check **GO**
  (`qa-check.md`), son écart majeur (indicatif mémorisé à l'inscription) et un mineur (cinquième essai) corrigés.

## Reste à faire
- Voir le parcours dans l'app (simulateur, appareil réel).
- Les suspens du contrat (« Ce qui reste en suspens »).
- Rien n'est poussé ni mergé : les deux branches `nuit/2026-11-28` attendent votre relecture.

## Décidé seul
- Voir la section « Ce que j'ai décidé seul » du contrat, et les décisions `2026-11-28-*` du registre.

## Défauts trouvés
- `ddev composer openapi` échoue sur 10 erreurs antérieures (Notification, ProfileMenuService) : `openapi.json` n'est
  plus régénérable en l'état ; noté, pas corrigé.
