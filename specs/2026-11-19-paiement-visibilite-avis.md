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
