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

---

## Ce que j'ai décidé seul

- Le repli par la langue d'origine est porté par les modèles (`Offer`, `OfferInformation`, via `OriginalLocale`) : toute lecture d'un texte traduit en profite (fiche, listes, back-office, formulaire).
- `content_locale` est **facultatif** côté serveur : une app plus ancienne ne l'envoie pas, la langue du compte vaut alors (comme avant).
- Back-office : un titre dans au moins une langue, l'erreur sous la langue par défaut ; à la création, la langue d'origine est la langue par défaut si son titre est saisi, sinon la première saisie.
- Noms des pays, régions et catégories au back-office : toujours exigés dans **toutes** les langues (règle existante) — l'allemand y devient donc obligatoire.
- Bug trouvé au simulateur et corrigé : la validation d'une étape ignorait l'enregistrement, donc l'étape « Langue » était validée comme « Présentation ». `POST …/steps/{n}` accepte désormais `?record=`, et l'app l'envoie.
- Les textes allemands standard (validation, authentification, pagination…) viennent du paquet `laravel-lang` déjà installé (`lang:add de`) ; ceux du projet sont rédigés par moi.
- Les tests fixent les langues à fr/en (`tests/TestCase.php`) ; un test qui porte sur l'allemand le déclare.

## Ce qui a été fait

- Web 554a7d8 :
  - `offers.locale` et son remplissage ; repli par la langue d'origine ;
  - champ `reloads` du module Form ; étape « Langue » à l'édition ; enregistrement dans la langue choisie ;
  - validation d'étape avec `record` ; back-office ;
  - `de` dans `app.supported_locales`, fichiers `lang/de` (app et modules), catégories et pays en allemand (migrations de remplissage, seeder des pays) ;
  - tests (étape, pré-remplissage, enregistrement, repli, migration, back-office, parité des textes allemands, textes de l'app en allemand), OpenAPI.
- Mobile c4959db : rechargement du formulaire, confirmation, `record` avec la validation d'étape, `de` dans les langues de l'app et textes hors ligne.
- Migrations exécutées en local. API vue en allemand : « Kamerun », « Art der Unterkunft », « Langzeitmiete ».
- Simulateur : étape « Langue » à l'édition (« Français — langue d'origine », « English — à traduire », « Deutsch — à traduire »), rechargement en allemand, étape 2 pré-remplie avec le texte français ; sans enregistrer.
- Suites : serveur 518 verts, app 246 verts, tsc et lint propres.
- QA indépendante : NO-GO (OpenAPI sans `record` sur la validation d'étape), corrigé en 95f4569 (paramètre d'adresse documenté, parité des textes de l'app avec l'allemand testée), contre-vérification GO.

## Ce que je n'ai pas vérifié

- L'app entière en allemand au simulateur (la langue du compte connecté n'a pas été changée) ; les textes sont vérifiés par l'API et par un test de parité.
- La qualité de l'allemand par un germanophone.
- La confirmation avant de perdre des modifications au simulateur (test seulement).

## Ce qui reste en suspens

- Relecture de l'allemand par un germanophone.
- L'e-mail et le SMS de code en allemand dépendent de la langue du compte : vérifiés à la lecture des textes seulement.
