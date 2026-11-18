# RETEX — Offres multilingues et catégories en arbre

## Ce qui s'est produit

- **Dans un test, la deuxième requête d'API d'un autre compte a reçu l'utilisateur de la première.** La garde d'authentification garde son utilisateur entre les requêtes d'un même test. `app('auth')->forgetGuards()` règle le problème.
- **Une classe de test qui hérite d'`AuthFilamentTestCase` active le back-office pour tous ses tests.** Les policies prennent alors leur branche Filament, et l'API répond 403. Il faut couper cette activation pour les tests d'API.
- **La suite complète a dépassé deux minutes** dans la console. La rediriger vers un fichier évite qu'elle bascule en arrière-plan.

## Règles candidates

- **Projet** : un test qui enchaîne plusieurs comptes sur l'API oublie la garde entre deux comptes. À ranger dans un trait de test commun si le cas se répète.
- **Projet** : les tests d'API et ceux du back-office sont dans des classes séparées, ou bien l'activation du back-office est explicite dans chaque test.

## Règle ré-implémentée, règle domiciliée

- **Domiciliée** : le repli des traductions vit à un seul endroit (`AppServiceProvider`).
- **Domiciliée** : l'ordre de l'arbre et les chemins vivent dans `OfferCategory::options()`, qui sert à l'app, au formulaire et au filtre du back-office.
