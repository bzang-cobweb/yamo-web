# Avis sur les offres

Demande du 2026-11-19 :

> 3. ajouter une commentaire et une note
> 4. afficher le commentaire sur ListItem sur sa ligne et en gras

Précisions de l'humain : ce sont des **avis de visiteurs** (un membre laisse un commentaire et une note sur une offre) ; la note va de **1 à 5 étoiles**.

## Ce que j'ai compris

Un membre connecté donne son avis sur une offre : une note de 1 à 5 étoiles et un commentaire. Les listes d'offres affichent la note moyenne et le dernier commentaire, en gras, sur sa propre ligne. La fiche de l'offre affiche les avis, et c'est depuis elle qu'on donne le sien.

### Règles

- **Qui donne un avis** : un membre connecté uniquement. Un visiteur voit les avis ; à la place du bouton, il voit « Connectez-vous pour donner votre avis ».
- **Sur quoi** : une offre **publiée** du pays courant. Le propriétaire ne note pas sa propre offre.
- **Un avis par membre et par offre.** Le membre peut le modifier ou le supprimer ; « Donner mon avis » devient alors « Modifier mon avis ».
- **Contenu de l'avis** :
  - la note : entière, de 1 à 5, obligatoire ;
  - le commentaire : obligatoire, de 1 à 1 000 caractères, en texte brut. Il n'est pas traduit : il s'affiche dans la langue où il a été écrit.
- **Publication immédiate**, sans validation préalable. La modération se fait après coup, dans le back-office.
- **Une offre archivée ou retirée** garde ses avis en base, mais ils ne s'affichent plus nulle part dans l'app.

### Serveur (module Offer)

- **Table `offer_reviews`** :
  - colonnes : `offer_id`, `user_id`, `rating` (1–5), `comment`, dates ;
  - unicité sur (`offer_id`, `user_id`) ;
  - supprimer l'offre ou le compte supprime ses avis.
- **Calculs** : la moyenne et le nombre d'avis sont calculés dans la requête des listes, sans colonne dénormalisée.
- **`ListItem` générique (module Form)** reçoit deux champs facultatifs, que tout domaine peut utiliser :
  - `rating` : `{ average, count }`, ou `null` sans avis ;
  - `highlight` : une phrase mise en avant, ou `null`.

  Pour les offres :
  - `highlight` porte le **dernier commentaire**, tronqué à 140 caractères ;
  - les deux champs servent dans « Offres du pays » (`offer.browse`) **et** dans « Mes logements » (`offer.mine`).
- **Fiche (`GET /api/v1/offers/{offer}`)** ajoute un bloc `reviews` :
  - la moyenne et le nombre d'avis ;
  - les **5 derniers avis**, avec pour chacun le prénom de l'auteur, sa note, son commentaire et sa date ;
  - `mine` : l'avis du membre connecté, s'il en a un ;
  - `can_review` : si le membre connecté peut donner un avis.
- **Formulaire `offer.review`**, décrit par le serveur comme les autres :
  - l'identifiant de l'enregistrement est celui de l'offre ;
  - le formulaire charge l'avis du membre sur cette offre, ou part de zéro ;
  - un nouveau type de champ **générique**, `rating` (étoiles 1–5), est réutilisable par tout formulaire. Il s'affiche en 5 étoiles dans l'app et en choix de 1 à 5 dans le back-office ;
  - **supprimer son avis** se fait depuis ce formulaire.
- **Back-office**, dans le menu Offres :
  - une page « Avis » liste les avis : offre, auteur, note, commentaire, date ;
  - on peut filtrer par note et par offre ;
  - l'administrateur peut supprimer un avis ; il ne peut ni le modifier ni en créer.
  - Permissions `offer_reviews.view` et `offer_reviews.delete`, données au rôle `admin`.
- **Démo** : `DemoSeeder` crée 3 membres « visiteurs » (`@example.test`) et une quinzaine d'avis sur les logements publiés. Certaines offres restent sans avis.
- **Documentation** : `openapi.json` est régénéré.

### App

- **Item de liste**, dans l'ordre :
  1. la photo ;
  2. le titre ;
  3. le sous-titre ;
  4. la ligne de note, par exemple « ★ 4,5 (12) » ; absente sans avis ;
  5. le **dernier commentaire, en gras, sur sa ligne**, sur 2 lignes au plus.

  Le composant reste générique : il affiche `rating` et `highlight` dès que le serveur les sert.
- **Fiche, section « Avis »**, placée après la description :
  - la moyenne en étoiles et le nombre d'avis ;
  - les 5 derniers avis : prénom, étoiles, date, commentaire ;
  - le bouton « Donner mon avis » ou « Modifier mon avis » ouvre le formulaire `offer.review` ;
  - après enregistrement ou suppression, la fiche et les listes se rechargent ;
  - sans avis : « Aucun avis pour le moment ».
- **Champ `rating` du formulaire** : 5 étoiles touchables, lisibles par l'accessibilité (« 4 étoiles sur 5 »).
- **Textes** en français et en anglais.

### Hors périmètre

- Réponse du propriétaire à un avis.
- Signalement d'un avis par un membre.
- Avis réservés aux personnes ayant réellement loué.
- Notifications au propriétaire.
- Page listant **tous** les avis d'une offre au-delà des 5 derniers.
- Tri ou filtre des offres par note.

## Découpage

1. Serveur :
   - table, modèle, policy ;
   - `ListItem` (`rating`, `highlight`) et champ `rating` ;
   - formulaire `offer.review` ;
   - bloc `reviews` de la fiche ;
   - back-office, démo, OpenAPI, tests.
2. App :
   - item de liste ;
   - champ étoiles ;
   - section « Avis » de la fiche et accès au formulaire ;
   - tests.
3. QA indépendante (`bza-dev:qa-check`), puis vérification au simulateur.

## Critères de succès

- Un membre connecté donne une note et un commentaire sur l'offre publiée d'un autre membre. Il le voit aussitôt :
  - sur la fiche ;
  - en gras sur sa ligne dans l'item de liste, avec la moyenne mise à jour.
- Le même membre ne peut avoir qu'un avis sur cette offre ; il peut le modifier ou le supprimer.
- Les demandes suivantes sont refusées par l'API, et pas seulement masquées dans l'app :
  - le propriétaire qui note sa propre offre ;
  - un visiteur non connecté qui envoie un avis ;
  - une note hors de 1–5 ;
  - un commentaire vide.
- Un brouillon, une offre archivée ou une offre d'un autre pays ne peut pas recevoir d'avis.
- Une offre sans avis n'affiche ni ligne de note ni commentaire.
- Un administrateur voit et supprime un avis dans le back-office.
- La suite de tests serveur et mobile passe ; `tsc` et le lint passent.

## Ce que j'ai décidé seul

- **Retirer son avis** se fait depuis la **fiche** (lien « Supprimer mon avis », avec confirmation), et non depuis le formulaire. Le serveur décrit ce retrait (libellé, confirmation, envoi) comme pour le retrait d'un item de liste. Les formulaires n'ont pas de bouton secondaire, et en ajouter un aurait touché tous les formulaires.
- **Routes** :
  - `PUT /api/v1/offers/{offer}/review` crée l'avis ou le remplace ;
  - `DELETE /api/v1/offers/{offer}/review` le retire.
- **Codes d'erreur** : le propriétaire reçoit **403** avec un message ; une offre non publiée ou d'un autre pays répond **404**.
- **Super administrateur** : il n'échappe pas aux règles d'un avis. Il ne peut pas noter sa propre offre ni un brouillon, et il ne crée ni ne modifie un avis dans le back-office.
- **« Dernier avis »** : c'est le dernier avis donné **ou modifié** (`updated_at`), pas le dernier créé.
- **Textes de l'app** :
  - la note lue par l'accessibilité vit dans les textes génériques (`list.ratingSummary`, `form.ratingValue`) ;
  - les textes propres aux offres vivent dans `offer.*`.
- **Écran du formulaire** : celui des formulaires du profil (`ProfileFormScreen`), sur la route `/offers/[id]/review`, réservée à un membre connecté. La fiche est passée de `offers/[id].tsx` à `offers/[id]/index.tsx`.
- **Étoiles** : glyphes « ★ » colorés, sans nouvelle dépendance.
- **Démo** : 3 visiteurs (`demo-visiteur-1..3@example.test`, mot de passe de démonstration) et 15 avis. La liste des avis est écrite dans `DemoSeeder`.

## Ce qui a été fait

- **Serveur** :
  - table `offer_reviews`, modèle `OfferReview` et factory ;
  - règle `review` dans `OfferPolicy`, `OfferReviewPolicy` pour le back-office ;
  - permissions `offer_reviews.view|delete`, données au rôle `admin` ;
  - champ générique `rating` (`FieldType::RATING`, `Field::rating()`, `FieldComponent`) ;
  - `ListItem::rating()` et `highlight()`, utilisés par `offer.browse` et `offer.mine` ;
  - formulaire `offer.review`, contrôleur et requête ;
  - bloc `reviews` de la fiche ;
  - ressource Filament « Avis » : liste, filtres par note et par offre, suppression ;
  - textes fr/en et `openapi.json` régénéré.
- **Tests serveur** : `OfferReviewApiTest` (7) et `OfferReviewResourceTest` (2). Quatre tests existants ont été mis à jour pour les nouveaux champs et les nouvelles permissions. La suite Offer, Form et Auth passe.
- **App** :
  - composants `Stars`, `RatingField` (type `rating`) et `ItemReviews` (note et dernier avis en gras, sur 2 lignes au plus), dans la vitrine et dans les listes du profil ;
  - section « Avis » de la fiche, route du formulaire, rechargement de la fiche et des listes après un changement.
- **Tests app** : 201 tests jest passent ; `tsc` et le lint aussi.
- **Simulateur** : le visiteur voit la note et le dernier avis dans la liste, puis « Connectez-vous pour donner votre avis » sur la fiche. Le membre donne 4 étoiles et un commentaire ; il revient sur la fiche avec le message, la moyenne 4,0 et son avis. Il supprime ensuite son avis après confirmation.
- **QA indépendante** : GO, 0 bloquant (`.ia/runs/2026-11-19-avis-offres/qa-check.md`). Deux mineurs sont corrigés ensuite, avec un test chacun : le dernier avis modifié, et le super administrateur sur la policy des avis.

## Ce que je n'ai pas vérifié

- **Back-office** : pas ouvert dans un navigateur ; seuls les tests Livewire le couvrent.
- **Lecteur d'écran** : VoiceOver n'a pas été lancé sur les étoiles ni sur la ligne de note.
- **Android** et **mode sombre** : non testés.
- **Langue anglaise** : la section « Avis » n'a pas été vue en anglais dans l'app.
- **Volume** : la moyenne est calculée à chaque requête de liste, sans mesure de performance.

## Ce qui reste en suspens

- **Offre archivée** : un membre ne peut pas retirer son avis tant que l'offre n'est pas republiée (404). Ses avis restent alors invisibles partout, donc ce n'est pas bloquant, mais à décider.
- **Retour au bon endroit** : un visiteur invité à se connecter depuis la fiche n'a pas de lien direct, et après connexion il revient à l'accueil, pas à la fiche.
- **Question restée sans réponse** (chantier de la fiche) : le propriétaire voit-il sa propre offre depuis un autre pays ?
