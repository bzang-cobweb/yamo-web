# `2026-11-27` — Le module Form devient le paquet local `bza/laravel-form`

Complète : 2026-11-27-page-configuration-paquet.md (mêmes conventions de paquet)

- **Type** : décision
- **Question posée** : « transformer le module Form en package » ; choix humains : espace de noms `Bza\Form`, les classes d'appui de l'application déplacées dans le paquet, un cœur sans Filament et un adaptateur Filament, commentaires traduits en anglais.
- **Ce qui est retenu** :
  - `packages/form` (`bza/laravel-form`, dépôt composer `path` déjà ouvert sur `packages/*`), espace de noms `Bza\Form\` ; le module `Modules/Form` disparaît (et sa ligne de `modules_statuses.json`) ;
  - un fournisseur de services Laravel ordinaire (`Bza\Form\FormServiceProvider`, découvert par `extra.laravel`) : registres, liaisons `{form}` et `{list}`, portes `viewForm` et `viewList`, routes de l'API (`api.` + préfixe `api`, noms inchangés), traductions sous l'espace `form::` (clés inchangées), configuration `form`, migrations ;
  - `Locales`, `DisplayTime` et `ContainsSearch` quittent `App\Support` pour `Bza\Form\Support` ; l'application et les modules les utilisent depuis le paquet ;
  - le cœur ne dépend pas de Filament ; l'adaptateur (`FieldComponent`) vit sous `Bza\Form\Filament`, Filament en « suggest » ;
  - les commentaires du paquet sont en anglais (comme `config-viewer`) ; les références aux décisions restent ;
  - l'API servie à l'app ne change pas (chemins, noms de routes, JSON) : l'app mobile n'est pas touchée.
- **Corollaires** : un module qui déclare un formulaire ou une liste importe `Bza\Form\…` ; les tests qui ont besoin de l'application (comptes, modules) restent dans l'application, ceux du cœur seul vivent dans `packages/form/tests`.
- **Tranché par** : humain
