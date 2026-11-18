# RETEX — Espace pays

## Ce qui s'est produit

- **La règle d'affichage de l'écran d'entrée a changé quatre fois en quelques minutes** (sauter si un seul pays, montrer la langue, sauter si une seule langue, toujours montrer, puis montrer tant que rien n'est choisi). Chaque consigne a été consignée par une décision qui remplace la précédente. C'est la capture d'écran du simulateur qui a fixé la dernière version.
- **La QA a trouvé que changer de langue perdait la sélection du pays.** Une requête dépendant de la langue, sans `keepPreviousData`, démontait l'écran. Le même piège existait déjà pour les listes (`useListDefinition`).
- **Jest ne rendait plus la main** avec un `QueryClient` réel. Le minuteur de nettoyage de react-query restait ouvert ; `gcTime: Infinity` dans les tests règle le problème.
- **18 tests sont tombés** quand le pays est devenu obligatoire. Les factories créaient un nouveau pays actif à chaque adresse : un seul pays par test règle le problème.

## Règles candidates

- **Projet (app)** : toute requête dont la clé contient la langue utilise `placeholderData: keepPreviousData`, sinon un changement de langue démonte l'écran.
- **Projet (app)** : un `QueryClient` de test a `gcTime: Infinity`, sinon jest peut rester ouvert.
- **Projet (web)** : une donnée « contextuelle » (pays courant) a une valeur par défaut déterministe dans les factories, celle déjà présente, pour que les tests restent dans un seul espace.

## Règle ré-implémentée, règle domiciliée

- **Domiciliée** : le pays courant (`CurrentCountry`) côté serveur, et `CountryProvider` côté app.
- **Domiciliée** : la devise d'un pays (`Currencies::forCountry`, puis `countries.currency`).
- **Ré-implémentée** : `CountryFlag` et le sélecteur de langue de l'accueil (`LocaleSwitcher`) ont chacun leur propre déclencheur. `EntrySelect` pourrait remplacer `LocaleSwitcher` à terme.
