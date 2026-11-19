# Contrat — Langue de saisie d'une offre, et l'allemand

Validation : en attente

Demande, après discussion : « à la création, pas d'étape de sélection de langue, on écrit dans la langue de l'utilisateur connecté ; à l'édition, on rajoute l'étape pour sélectionner la langue », avec ma proposition retenue : ne pas recopier le texte dans les autres langues, mais pré-remplir une langue manquante avec le texte d'origine. Contexte : ajouter l'allemand aux langues de l'app.

## Ce que j'ai compris

### 1. Langue d'origine
- Une offre retient sa **langue d'origine** (`offers.locale`) : celle de son auteur au moment de la création.
  Les offres existantes reçoivent la langue de leur titre le plus ancien connu
  (français s'il existe, sinon la première langue renseignée).
- À la lecture, un texte manquant dans la langue demandée s'affiche dans la **langue
  d'origine**, puis dans la langue par défaut, puis dans une autre (aujourd'hui : demandée,
  défaut, une autre). Vaut pour le titre, la description, les blocs d'informations et les
  attributs traduisibles, dans la fiche, les listes et le formulaire.

### 2. Création : pas d'étape de langue
- Inchangé : le propriétaire écrit dans la langue de son compte, seule cette langue est
  enregistrée. Rien n'est recopié dans les autres langues.

### 3. Édition : une première étape « Langue »
- Étape 1 « Langue » : « Dans quelle langue modifiez-vous cette annonce ? ». Les langues
  de l'app, la langue d'origine marquée, chaque langue marquée « traduite » ou « à traduire »
  (ex. « Français — langue d'origine », « English ✓ », « Deutsch — à traduire »). Choisie
  d'office : la langue du compte.
- Passer à l'étape 2 recharge le formulaire dans la langue choisie : les champs traduisibles
  montrent le texte de cette langue, ou, s'il manque, **le texte d'origine en pré-remplissage**
  (le propriétaire traduit à partir de lui). Les valeurs non traduisibles (type, prix,
  équipements, adresse, photos…) sont les mêmes pour toutes les langues.
- Enregistrer remplace **seulement la langue choisie** ; les autres sont gardées.
- Revenir à l'étape 1 et changer de langue après avoir modifié des textes : l'app demande
  confirmation (les textes modifiés seraient perdus).
- Une seule langue dans l'app : pas d'étape.
- Mécanisme générique (module Form) : le serveur déclare le champ de langue
  (`content_locale`) et qu'il recharge le formulaire quand il change (`reloads`) ; l'app
  recharge la définition avec `?content_locale=xx` et envoie la langue avec l'enregistrement.
  Valable pour tout formulaire à champs traduisibles.

### 4. Back-office
- Exige le titre dans **au moins une langue**, au lieu du français obligatoire.
- Le titre et la description affichés dans les listes suivent la langue d'origine si la
  langue du back-office manque.

### 5. L'allemand
- `de` rejoint les langues de l'app (`app.supported_locales`).
- Tous les textes en allemand : fichiers `lang/de` de l'app et des modules (Auth, Form,
  Location, Offer : textes de l'app, validation, back-office, domaine), catégories
  (`RentalDomain::categories()` et données existantes), données de démonstration si utile.
  Noms des pays : déjà tirés d'ICU, à vérifier en allemand.
- Traductions rédigées par moi ; à relire par un germanophone.

## Découpage

- Web :
  - migration `offers.locale` (et remplissage des offres existantes) ;
  - repli par la langue d'origine ;
  - champ de langue rechargeant (module Form) ;
  - étape « Langue » à l'édition (`OfferForm`, `RentalDomain`) ;
  - `OfferService` (langue choisie, `informations` comprises) ;
  - back-office ;
  - `lang/de` ;
  - tests, OpenAPI.
- App :
  - rechargement du formulaire au changement de langue ;
  - envoi de la langue ;
  - confirmation avant de perdre des textes ;
  - tests.

## Critères de succès (état observable)

- Créer une offre en allemand : seul l'allemand est enregistré ; un lecteur francophone voit
  le texte allemand (langue d'origine), pas un champ vide.
- Éditer : l'étape « Langue » montre l'état de chaque langue ; choisir « Français — à
  traduire » pré-remplit avec le texte allemand ; enregistrer ajoute le français sans toucher
  l'allemand.
- Le back-office enregistre une offre qui n'a qu'un titre allemand.
- L'app est utilisable entièrement en allemand (textes, erreurs, listes, catégories).
- Rien ne change pour un formulaire sans champ traduisible ni pour une app à une langue.
- Suites serveur et app vertes, OpenAPI régénéré, simulateur vu (création et édition), QA
  indépendante.

## Points à trancher (défaut appliqué si validé tel quel)

1. Langue d'origine des offres existantes : français si présent, sinon la première renseignée (défaut).
2. ~~L'étape « Langue » apparaît à l'édition même quand l'offre n'a qu'une langue~~ — **tranché** : l'étape n'apparaît à l'édition que si **l'app** propose plusieurs langues ; dans ce cas elle apparaît même si l'offre n'est écrite que dans une langue (c'est là qu'on traduit).
3. Traductions allemandes rédigées par moi, sans relecture humaine avant livraison (défaut ; relecture conseillée).
4. Pas de traduction automatique (défaut).
