# Contrat — Un avis par défaut, réglage « Appliquer le paiement », visibilité d'une offre, « Date d'expiration »

Validation : en attente

Demande : « 1. afficher un seul avis par défaut, cette valeur doit être fournie par Laravel 2. ajouter dans le formulaire de l'application un boolean qui indique si on commence à appliquer le paiement 3. pour voir une offre, la policy assure : l'auteur de l'offre voit son offre tout le temps ; autrement, les autres visiteurs la voient si elle est publiée et, si on applique le paiement, s'assurer que la date de paiement n'a pas encore expiré. Renommer date de paiement par date d'expiration ».

## Ce que j'ai compris

1. **Avis affichés sur la fiche** : le nombre des derniers avis montrés vient de Laravel
   (réglage de configuration du module Offer, `offer.reviews_shown`, valeur **1**). L'app
   affiche ce que le serveur sert. Le bouton « Voir tous les avis » apparaît quand il y a
   plus d'avis que ceux affichés — donc, avec 1, dès deux avis, comme aujourd'hui.
2. **« Appliquer le paiement »** : une case à cocher dans le formulaire « Application »
   du back-office (les réglages de l'application, `Setting`), décochée par défaut.
   Cochée, la date d'expiration des offres compte (point 3).
3. **Qui voit une offre** — une seule règle, dans le modèle (`Offer`), utilisée par la
   policy et par les listes :
   - l'auteur voit toujours la sienne (brouillon, archivée, expirée, tout pays) ;
   - tout autre visiteur, connecté ou non, la voit si elle est **publiée**, du **pays
     courant**, et — si le paiement est appliqué — si sa **date d'expiration n'est pas
     passée**. Sans date d'expiration et paiement appliqué : pas visible (jamais payée) ;
   - paiement non appliqué : la date n'a aucun effet (comme aujourd'hui) ;
   - sinon 404, comme aujourd'hui.
   La même règle vaut partout où une offre est montrée à d'autres que son auteur :
   la fiche, l'accueil (liste, villes du filtre), les favoris, la liste des avis, donner
   un avis et mettre en favori.
   **Renommage** : la colonne `paid_until` devient `expires_at`, libellée « Date
   d'expiration » (« Expiration date ») dans le back-office ; les données sont gardées.

## Découpage

- Web : config `offer.reviews_shown` ; migration `settings.payment_required` + case dans
  `SettingResource` ; migration de renommage `paid_until` → `expires_at` ; `Offer`
  (portée `visibleToOthers()` et `isVisibleTo(?User)`), `OfferPolicy` (show, review,
  favorite), `OfferBrowseListing`, `OfferFavoritesListing`, `OfferReviewController` ;
  formulaire Filament ; traductions ; tests.
- App : bouton « Voir tous les avis » quand `count > latest.length` ; test.

## Critères de succès (état observable)

- La fiche sert 1 avis ; changer la configuration change ce nombre sans toucher l'app.
- Le back-office « Application » a la case « Appliquer le paiement », enregistrée.
- Paiement non appliqué : une offre publiée du pays est visible, quelle que soit sa date.
- Paiement appliqué : une offre publiée dont la date d'expiration est passée ou absente
  répond 404 à la fiche, disparaît de l'accueil, des villes, des favoris, de la liste des
  avis, et refuse avis et favori ; son auteur la voit toujours (fiche, « Mes logements »).
- La colonne s'appelle `expires_at`, libellée « Date d'expiration » ; les dates existantes
  sont gardées.
- Suites serveur et app vertes, OpenAPI régénéré, QA indépendante.

## Points à trancher (défaut appliqué si validé tel quel)

1. Étendre la règle aux listes (accueil, villes, favoris, avis) et pas seulement à la
   fiche (défaut : **oui**, sinon une offre invisible resterait dans l'accueil).
2. Paiement appliqué et aucune date d'expiration : **non visible** (défaut).
3. Le nombre d'avis est une configuration du code (défaut), pas un réglage du back-office.

---

## Ce que j'ai décidé seul

- La règle est une portée `Offer::visibleToOthers()` et sa jumelle `isVisibleToOthers()` pour une offre chargée ; la policy (fiche, avis, favori), l'accueil (liste, épinglées, villes) et les favoris les utilisent. La liste des avis passe par la policy de la fiche.
- Avis et favori sur une offre invisible : 404 avant le 403 « c'est la vôtre » (l'auteur d'une offre expirée reçoit donc 404 s'il tente de la noter, ce qu'il ne peut de toute façon pas faire).
- `Setting::paymentRequired()` lit la colonne à chaque appel (une requête par liste ou par fiche), sans cache.
- « Appliquer le paiement » n'est pas servi à l'app (`GET /v1/app` inchangé).
- Le texte d'aide de la date d'expiration et de la case explique l'effet (sans date : non visible).
- Le bouton « Voir tous les avis » suit la règle « plus d'avis que ceux servis », ce qui remplace « dès deux avis » sans changer le résultat avec 1.

## Ce qui a été fait

- Web 1b13046 : config `offer.reviews_shown` = 1 ; migration `settings.payment_required` et case « Appliquer le paiement » (section « Paiement » de la page Application) ; migration `paid_until` → `expires_at` (« Date d'expiration ») ; règle de visibilité dans `Offer`, utilisée par `OfferPolicy`, `OfferBrowseListing`, `OfferFavoritesListing` ; tests `OfferVisibilityApiTest` (sans et avec paiement), page Application, avis (nombre servi selon la configuration).
- Mobile f3d9dd4 : bouton « Voir tous les avis » quand il y a plus d'avis que ceux servis.
- Migrations exécutées en local. Simulateur : la fiche montre un avis, « (2) » et le bouton.
- Suites : serveur 505 verts, app 235 verts, tsc et lint propres ; OpenAPI inchangé (forme des réponses inchangée).

## Ce que je n'ai pas vérifié

- La case « Appliquer le paiement » dans un vrai navigateur (test Livewire seulement).
- Le masquage au simulateur avec le paiement appliqué (couvert par les tests d'API) : la base locale n'a pas été modifiée.

## Ce qui reste en suspens

- Cocher « Appliquer le paiement » masque d'un coup toutes les offres sans date d'expiration future : à faire seulement une fois les dates saisies.
- Rien n'avertit l'auteur que son offre a expiré (statut dans « Mes logements », rappel) : hors périmètre.
