# `2026-11-25` — Un fuseau horaire par pays ; « aujourd'hui » chez l'offre pour réserver

- **Type** : décision
- **Question posée** : « enregistrer le fuseau horaire par pays et le faire dans le seeder », après le constat qu'entre minuit et 1h, heure de Douala, le calendrier de réservation acceptait encore la veille (le « aujourd'hui » du serveur était celui d'UTC).
- **Ce qui est retenu** :
  - **La colonne** : `countries.timezone` (`Africa/Douala`), nullable. La migration la remplit pour les pays existants.
  - **Le seeder des pays** la pose si elle est vide, sans écraser un choix fait au back-office.
    - La valeur vient de la base de fuseaux de PHP (`Timezones::forCountry`) : CM `Africa/Douala`, CH `Europe/Zurich`, CI `Africa/Abidjan`.
    - Un pays à plusieurs fuseaux prend celui de sa capitale (US `America/New_York`, RU `Europe/Moscow`…).
    - Trois territoires n'ont pas de fuseau dans cette base (AC, TA, XK) : ils prennent celui de l'application.
  - **Le back-office** : un champ « Fuseau horaire » sur la fiche d'un pays.
  - **Ce que ça change** : le formulaire de réservation calcule « aujourd'hui » dans le fuseau du pays de l'offre (`Country::today()`), pour les bornes du calendrier, les jours indisponibles et la vérification des dates. À 23:30 UTC, il est déjà le lendemain à Douala : la veille est refusée.
  - **Ce qui ne change pas encore** : les listes de demandes (« à venir », « passées ») et l'expiration des demandes gardent le « aujourd'hui » de l'application. Elles mêlent des offres de plusieurs pays.
- **Tranché par** : humain ; session (le fuseau de la capitale, l'usage limité au formulaire de réservation, le nom `localTimezone()`)
