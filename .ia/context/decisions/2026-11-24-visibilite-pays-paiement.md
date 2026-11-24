# `2026-11-24` — Le pays et la période payée, deux traits réutilisables

- **Type** : décision de conception
- **Questions posées**, dans l'ordre :
  1. « scopeVisible : il faut faire un trait avec un scope visibleCountry($country = null), et si country est null on prend le country courant ; un scope payment, car ils vont être réutilisés » ;
  2. « faire 2 traits séparés » ;
  3. « faire un trait avec un scope visibleRange() qui reprend starts_at et expires_at » ;
  4. « supprimer HasPayment » ;
  5. « move country() and the $country, $country_id declarations to HasCountry ; do the same with HasRange » ;
  6. « rename HasRange to HasPaymentRange and introduce payment required setting » ;
  7. « add Payment in all function name in HasPaymentRange ».
- **Ce qui est retenu** :
  - `App\Models\Concerns\HasCountry` :
    - la relation `country()` et les déclarations de `$country_id` et `$country` ;
    - `scopeVisibleCountry(?Country|int $country = null)` : le pays donné, sinon le pays courant ;
    - `isInCountry()` : la même règle pour un modèle chargé.
  - `App\Models\Concerns\HasPaymentRange` :
    - les déclarations de `$starts_at` et `$expires_at`, lus en dates (`initializeHasPaymentRange()`, `mergeCasts`) ;
    - `paymentRequired()`, abstrait : chaque modèle déclare le réglage de la page Application qui applique son paiement ;
    - `scopeVisiblePaymentRange()` : le paiement appliqué, seulement la période payée, commencée et pas terminée ; sans date de fin, jamais. Non appliqué, tous ;
    - `scopePaymentStarted()` : la période commencée, que le paiement soit appliqué ou non ;
    - `isInPaymentRange(?bool $required = null)` et `isPaymentStarted()` : les mêmes règles pour un modèle chargé. `$required` évite de relire le réglage à chaque ligne d'une liste.
  - `Offer` (`paymentRequired()` = `Setting::paymentRequired()`) : `scopeVisibleToOthers()` = publié + `visibleCountry()` + `visiblePaymentRange()`.
  - `Partner` (`paymentRequired()` = `Setting::partnerPaymentRequired()`) : `scopeVisible()` = publié + `visibleCountry()` + `paymentStarted()` (sa campagne) + `visiblePaymentRange()`. `scopeAdministeredBy()` passe aussi par `visibleCountry()`.
  - Les colonnes restent dans le `#[Fillable]` de chaque modèle : la liste des attributs remplissables y est déclarée par attribut.
- **Tranché par** : humain (deux traits, leurs scopes, ce qu'ils déclarent, le réglage de paiement dans le trait) ; session (`paymentStarted()`, méthodes pour un modèle chargé, `Fillable` gardé dans le modèle)
