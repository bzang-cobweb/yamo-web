Remplace en partie : 2026-11-23-listeners-vers-services.md (noms des services).

# `2026-11-23` — Les services se nomment `xxxService`

- **Type** : décision
- **Question posée** : « j'ai fait des changements sur les skills dans le projet home/private/ai » (bza-laravel 0.7.0 : une classe de service et ses variables se nomment `xxxService`).
- **Réponse** : appliquer la règle au code écrit le 2026-11-23.
- **Ce qui est retenu** :
  - `BookingNotifier` devient `BookingNotifierService`, `BookingThreadDetails` devient `BookingThreadDetailsService` ;
  - leurs propriétés et paramètres : `$bookingNotifierService`, `$bookingThreadDetailsService` ;
  - `OfferController::publish()` et `unpublish()` : `$offerService`.
- **Motif** : la règle de `bza-laravel:requete-service`.
- **Portée** : `Modules/Offer` (listeners, services, contrôleur, test des listeners).
- **Corollaires** : le code plus ancien qui nomme encore un service `$service` n'a pas été repris.
- **Tranché par** : humain (règle du marketplace)
