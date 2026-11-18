# RETEX — project:init et skill projet-domaine

## Ce qui s'est produit

- **La QA a trouvé une fuite possible de secret par interpolation Dotenv.** Un nom `${APP_KEY}` écrit entre guillemets doubles est relu comme la valeur de `APP_KEY`. Mes tests vérifiaient l'écriture ; ils ne relisaient pas avec Dotenv. Le test relit maintenant avec `Dotenv::createArrayBacked`.
- **Pas de lancement réel sur l'environnement local** : la commande aurait changé le `.env` et la base de l'humain. Seuls les tests, avec un `.env` temporaire (`useEnvironmentPath`), l'ont lancée.

## Règles candidates

- **Marketplace (bza-laravel)** : tout code qui écrit un `.env` est testé en **relisant** le fichier avec Dotenv, y compris avec des valeurs contenant `$`, `${…}`, des guillemets, un `#` ou des espaces.
- **Projet** : une commande qui modifie `.env` ou la base se teste avec `useEnvironmentPath` vers un répertoire temporaire, jamais sur le vrai fichier.

## Règle ré-implémentée, règle domiciliée

- **Domiciliée** : l'écriture des réglages non secrets du `.env` (`EnvFile`, liste blanche).
- **Domiciliée** : toute la logique d'initialisation est dans la commande. Le skill ne fait que guider et vérifier.
