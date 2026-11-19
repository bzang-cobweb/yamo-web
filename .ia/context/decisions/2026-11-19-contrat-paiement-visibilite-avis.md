Remplace en partie : 2026-11-19-acces-avis-backoffice.md (« Payée jusqu'au » devient « Date d'expiration », `expires_at`, et prend effet), 2026-11-18-fiche-offre.md (qui voit une fiche) et 2026-11-19-fiche-localisation-accordeon.md (bouton des avis : plus d'avis que ceux affichés).

# `2026-11-19` — Contrat « un avis par défaut, appliquer le paiement, visibilité d'une offre », validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-19-paiement-visibilite-avis.md`, avec trois défauts : la règle vaut aussi pour les listes ; paiement appliqué sans date d'expiration = non visible ; le nombre d'avis est une configuration du code.
- **Réponse** : « Approved » (défauts retenus).
- **Ce qui est retenu** :
  - la fiche sert les `offer.reviews_shown` derniers avis (configuration du module Offer, 1) ; l'app affiche « Voir tous les avis » quand il y a plus d'avis que ceux servis ;
  - réglage « Appliquer le paiement » (`settings.payment_required`), case du formulaire « Application » du back-office, décochée par défaut ;
  - `offers.paid_until` est renommée `expires_at`, « Date d'expiration » ;
  - une offre est visible par son auteur toujours ; par tout autre visiteur si elle est publiée, du pays courant et, paiement appliqué, si `expires_at` est dans le futur (absente = non visible). Sinon 404 ;
  - cette règle, portée par `Offer`, vaut pour la fiche, l'accueil (liste et villes), les favoris, la liste des avis, donner un avis, mettre en favori.
- **Motif** : la demande de l'humain ; une offre invisible ne doit apparaître nulle part pour les autres.
- **Portée** : module Offer (config, `Offer`, `OfferPolicy`, listes, `OfferReviewController`, back-office), `Setting` et `SettingResource` ; app (`OfferReviews`).
- **Corollaires** : cocher « Appliquer le paiement » masque d'un coup toutes les offres sans date d'expiration future ; le back-office n'est pas filtré.
- **Tranché par** : humain
