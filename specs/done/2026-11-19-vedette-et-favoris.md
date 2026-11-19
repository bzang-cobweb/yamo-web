# Offres en vedette et favoris

Demande du 2026-11-19 :

> 2. prévoir une mise en avant d'une offre et qui sera aussi payante
> 3. ajout de la liste des favoris à travers une image d'un coeur qui s'affiche en haut à droite sur l'image ListItem et sur le détail

Contexte (décision `2026-11-19-modele-proprietaire-payant`) : à terme, seul le propriétaire paie ; au lancement, tout est gratuit.

Retours de l'humain sur la première version : « pas de badge en vedette », « pas de favoris sur les photos si pas connecté ».

## Ce que j'ai compris

### 1. Offres en vedette

Une offre peut être **mise en vedette jusqu'à une date**. Le paiement viendra plus tard : au lancement, c'est un **administrateur** qui la met en vedette, depuis le back-office. Le modèle est déjà prêt pour qu'un paiement la mette en vedette ensuite, sans rien changer à l'affichage.

- **Données** : une colonne `featured_until` (date et heure, ou vide) sur l'offre. Une offre est en vedette tant que cette date n'est pas passée.
- **Back-office** :
  - dans la fiche d'une offre, un champ « En vedette jusqu'au » ;
  - dans la liste des offres, une colonne et un filtre « En vedette ».
  - Permission : celle de modifier les offres (`offers.update`).
- **Vitrine de l'accueil** (`offer.browse`) :
  - les offres en vedette (publiées, du pays courant) viennent **en premier**, puis les autres, les plus récentes d'abord ;
  - le défilement infini et la recherche gardent cet ordre.
- **Aucun badge** : une offre en vedette se reconnaît seulement à sa place en tête de la vitrine, ni dans la liste ni sur la fiche.

### 2. Favoris

Un membre connecté garde des offres en **favoris** avec un cœur.

- **Le cœur** :
  - en haut à **droite** de la photo, dans chaque item de la vitrine et sur la fiche ;
  - vide quand l'offre n'est pas en favori, plein et rouge quand elle l'est ;
  - le toucher l'ajoute ou la retire aussitôt, et l'accessibilité lit « Ajouter aux favoris » ou « Retirer des favoris » ;
  - pas de cœur sur ses propres offres, ni dans « Mes logements ».
- **Visiteur non connecté** : aucun cœur, ni dans la vitrine ni sur la fiche.
- **Liste « Mes favoris »** :
  - un lien du menu du profil ouvre une liste générique (`offer.favorites`) des offres en favori, les plus récemment ajoutées d'abord ;
  - seules les offres publiées du pays courant s'y affichent ; une offre archivée ou retirée en disparaît, sans que le favori soit perdu ;
  - toucher une offre ouvre sa fiche ; le cœur plein permet de la retirer ;
  - liste vide : « Aucun favori pour l'instant. Touchez le cœur d'une offre pour la retrouver ici. »
- **Serveur** :
  - une table `offer_favorites` (`user_id`, `offer_id`, date), un favori par membre et par offre ;
  - `PUT /api/v1/offers/{offer}/favorite` l'ajoute, `DELETE` le retire. Une offre non visible répond 404, la sienne 403 ;
  - `ListItem` reçoit un champ générique `favorite` : `{ active, endpoint }`, ou rien quand le cœur n'a pas lieu d'être (visiteur non connecté, sa propre offre) ;
  - la fiche sert le même bloc `favorite`.
- **App** : le cœur change aussitôt (mise à jour optimiste). En cas d'échec, il revient à son état et un message s'affiche.

### Hors périmètre

- Le **paiement** de la mise en vedette, et la visibilité des offres selon le paiement du propriétaire.
- Une **limite** au nombre d'offres en vedette, une rotation entre elles, ou une page « Vedettes ».
- Une **notification** quand une offre en favori change (prix, disponibilité).
- Des favoris **sans compte**, gardés sur le téléphone.

## Découpage

1. **Serveur** :
   - migration (`featured_until`, `offer_favorites`) ;
   - `ListItem::favorite()` ;
   - tri de la vitrine ;
   - routes et policy des favoris ;
   - liste `offer.favorites` et lien du menu ;
   - bloc `favorite` sur la fiche ;
   - back-office (champ, colonne, filtre) ;
   - démo (2 offres en vedette, quelques favoris) ;
   - textes fr/en, OpenAPI et tests.
2. **App** :
   - composant cœur sur la photo de l'item et de la fiche ;
   - mise à jour optimiste ;
   - « Mes favoris » ;
   - tests.
3. **Vérification** au simulateur, puis QA indépendante.

## Critères de succès

- **Mise en vedette** : un administrateur met une offre en vedette jusqu'à une date. Elle apparaît en tête de la vitrine jusqu'à cette date, puis reprend sa place. Aucun badge n'est affiché.
- **Favoris d'un membre** : il touche le cœur d'une offre, dans la vitrine ou sur la fiche. Le cœur devient plein, et l'offre apparaît dans « Mes favoris ». Le retoucher l'en retire.
- **Visiteur** : aucun cœur, dans la vitrine comme sur la fiche.
- **Refus par l'API** :
  - mettre sa propre offre en favori (403) ;
  - une offre non publiée ou d'un autre pays (404) ;
  - un visiteur non connecté (401).
- **Archivage** : une offre archivée disparaît de « Mes favoris », et réapparaît si elle est republiée.
- **Tests** : les suites serveur et app passent, ainsi que tsc et le lint.

## Ce que j'ai décidé seul

- **Vedettes en tête, sans casser le défilement infini** : la pagination par curseur ne sait pas trier sur « en vedette jusqu'à une date ».
  - Les 20 premières vedettes (la date la plus lointaine d'abord) sont **épinglées** en tête de la première page, hors pagination (`Listing::pinned()`, générique).
  - Les autres offres, y compris une vedette au-delà de la 20e, suivent dans l'ordre habituel.
  - L'expiration est calculée à chaque requête, sans tâche planifiée.
- **« Mes favoris »** est paginée par favori (le plus récent d'abord).
- **Ouvrir la fiche depuis « Mes favoris »** : `ListItem` gagne un champ générique `link` (écran de l'app, « /offers/12 »). L'app n'accepte que la forme `/<entité>/<id>`.
- **Points d'API du cœur** : l'app n'accepte que `/v1/<entité>/<id>/favorite`.
- **Super administrateur** : la règle `favorite` vaut aussi pour lui (pas de favori sur sa propre offre ni sur une offre non publiée).
- **Cœur** : sur fond sombre translucide, blanc et vide, ou rouge et plein. Dans « Mes favoris », le cœur est toujours plein.
- **Démo** : 2 logements en vedette pour 30 jours, et 3 favoris du premier visiteur.

## Ce qui a été fait

- **Serveur** :
  - colonne `featured_until`, table `offer_favorites` et modèle `OfferFavorite` ;
  - portées `featured`, `notFeatured`, `withFavoriteOf` ;
  - règle `favorite` et `PUT/DELETE /api/v1/offers/{offer}/favorite` ;
  - `ListItem::favorite()` et `link()` ;
  - épinglées dans `Listing` et `ListingResource` ;
  - vitrine avec vedettes en tête et cœur ;
  - liste `offer.favorites` et lien « Mes favoris » du menu ;
  - bloc `favorite` de la fiche ;
  - back-office : champ « En vedette jusqu'au », colonne et filtre ;
  - textes fr/en et `openapi.json` régénéré.
- **Tests serveur** :
  - `OfferFavoriteApiTest` (7 tests : ordre et expiration, pages sans doublon, plus de 20 vedettes, ajout et retrait, pas de cœur pour un visiteur ni sur sa propre offre, refus 401/403/404, archivage) ;
  - un test back-office ;
  - 6 tests mis à jour.

  La suite complète passe (475 tests au dernier passage complet, puis les modules touchés après les corrections de QA).
- **App** :
  - `FavoriteHeart`, avec mise à jour optimiste, retour en arrière et message en cas d'échec ;
  - `ItemPicture` (photo et cœur) dans la vitrine et dans les listes du profil, cœur sur la photo de la fiche ;
  - un item avec `link` ouvre la fiche ;
  - `ListService.setFavorite`.

  210 tests passent, ainsi que tsc et le lint.
- **Simulateur** :
  - visiteur : pas de cœur, vedette en tête ;
  - visiteur de démo connecté : cœurs visibles, un toucher remplit le cœur ;
  - « Mes favoris » liste les offres, la plus récente d'abord, cœurs pleins.
- **QA indépendante** : NO-GO (une vedette au-delà de la 20e disparaissait ; points d'API trop larges). Corrigé, puis GO.

## Ce que je n'ai pas vérifié

- **Simulateur** : le retrait d'un favori depuis « Mes favoris », l'ouverture de la fiche depuis cette liste, et le cœur sur la fiche.
- **Back-office** : non revu dans un navigateur, seulement par les tests Livewire.
- **Tests absents** : pas de test combinant recherche et plus de 20 vedettes, ni de mesure du nombre de requêtes.
- **Fenêtre transitoire** : une offre qui devient vedette, ou cesse de l'être, entre deux pages chargées peut être doublée ou sautée.

## Ce qui reste en suspens

- **Paiement de la mise en vedette** (et visibilité selon le paiement du propriétaire) : chantier à venir, décision `2026-11-19-modele-proprietaire-payant`.
- **Retour au bon endroit** : un visiteur doit se connecter pour voir les cœurs, sans lien direct depuis la fiche.
