RAPPORT QA — 2026-11-19-multiselect-intervalle-equipements (revérification)
Go : oui
Base du verdict : rejoué moi-même après correction.
- Serveur : `ddev exec php artisan test Modules/Offer Modules/Form Modules/Auth tests/Feature` : 440 passed, 2111 assertions.
- App : jest 40 suites / 216 tests passés ; `tsc --noEmit` sans sortie ; `npm run lint` sans erreur.
- Premier passage (inchangé) : API locale sans erreur 500 sur toutes les valeurs mal formées (range à 1 ou 3 éléments, chaînes, inversé), amenities wifi -> 11, wifi+parking -> 5, fiche « Équipements : Wifi, Cuisine équipée, Lave-linge », filtres price:range / property_type:multiselect / amenities:multiselect.
- Bloquant levé : FilterScreen calcule `rangeErrors()` pour chaque champ range, passe `error` au champ, n'applique ni ne ferme si max < min ; texte `form.rangeOrder` présent en fr et en dans `Modules/Form/lang/*/mobile.php` ; le test jest (filters.test.tsx) vérifie `price-error` = « Le maximum doit être au moins égal au minimum. ».
- Majeur levé : `FieldComponent::component()` rend un range en Fieldset de deux TextInput numériques (`<nom>.0` / `<nom>.1`, unité en suffixe, règles, max ≥ min) ; OfferForm l'utilise pour les attributs non traduisibles ; `FieldComponentRangeTest` passe (2 tests).
Ne couvre pas : rendu visuel réel (simulateur, barre fixe, clavier), rendu navigateur du back-office (le Fieldset n'est vérifié que par test unitaire, pas la validation max < min en soumission Filament), régressions de formulaires hors suites automatiques, OpenAPI régénéré, en côté app non vu à l'écran, écriture via API.

Bloquants : 0
Majeurs : 0
Mineurs : 1
- Un filtre de liste invalide est ignoré sans message côté API : comportement voulu par le contrat des filtres, non bloquant. (La règle max ≥ min du back-office n'a pas de test de soumission.)
