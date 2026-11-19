# RETEX — calendrier d'un logement

## Ce qui s'est produit deux fois (règles candidates)

- **Un test de forme exacte casse quand on ajoute un champ.** `ListItem` (`quote`) puis `Field` (`calendar`) l'ont fait, dans `ListingApiTest`, `UserAddressApiTest`, `OfferBrowseApiTest` et `FieldTest`.
  - Règle candidate (projet) : en ajoutant une clé à `ListItem::toArray()` ou à `Field::toArray()`, chercher `'highlight' => null` et `'date_range' => null` dans les tests avant de lancer la suite.
- **Une fenêtre native (`Modal`) reste au-dessus après une navigation.**
  - Règle candidate (projet, app) : tout `Modal` d'un écran se ferme au départ de l'écran (`useFocusEffect`, avec une fonction de nettoyage).
- **Une commande `timeout` est absente sur macOS** : une exécution est partie sans rien lancer, et j'ai cru que jest était bloqué.
  - Règle candidate (marketplace) : ne pas utiliser `timeout` sur macOS ; lancer les commandes longues en arrière-plan.

## Règle ré-implémentée, règle domiciliée

- **Domiciliée** : la disponibilité d'une offre (`OfferAvailability`). La demande, l'acceptation, le calendrier et le formulaire de réservation la lisent tous au même endroit.
- **Domiciliée** : le geste d'un bouton de carte (`useItemActions`) et son rendu (`ItemActionButtons`), autrefois dans `ProfileListScreen` seul.
- **Domicilié** : les couleurs des statuts (`TONE_COLORS`, dans `StatusTag`), partagées par la pastille, la grille et la légende.
- **Ré-implémenté puis corrigé** : la vérification « pas de séjour accepté ce jour » existait à la fois dans le contrôleur de blocage et dans le service. Après la QA, elle ne vit plus que dans `BookingService::block`, sous le verrou.

## Ce qui a coûté

- **Scramble** a refusé des types qu'il ne savait pas inférer (`format()`, `->day`, la carte d'un item) : il a fallu des casts explicites et une méthode typée.
