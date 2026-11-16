# Contrat — Textes de l'app servis par l'API, titre et sous-titre des formulaires

- **Chantier** : `2026-11-16-textes-servis-par-api`
- **Demande** (chat, 2026-11-16) : « Tous les messages doivent venir de l'API locale de
  Laravel. Seul le message affiché quand l'utilisateur n'a pas internet doit être local.
  Un form peut définir en option un titre (Sign-In dans le formulaire de login), un
  sous-titre (à afficher en dessous du titre). Chaque champ peut définir un helper text à
  afficher en dessous du champ. »
- **Arbitrages tranchés** (chat, 2026-11-16) :
  - sans réseau, l'app affiche un **écran hors ligne**, sans garder de textes sur le
    téléphone et sans nouvelle dépendance ;
  - cet écran est **entièrement local** : le message et le bouton Réessayer ;
  - les textes sont définis dans les **fichiers de langue** des modules Laravel.
- **Corrections de l'humain avant validation** (chat, 2026-11-16) : « Il faut
  différencier le message d'erreur du helper text. » ; « Le storage des médias doit
  toujours se faire dans un répertoire qui porte le nom du module/le nom du champ. »
- **Statut** : validé le 2026-11-16 (décision `2026-11-16-contrat-textes-servis-par-api.md`)

## Ce que j'ai compris

### Textes de l'app (web et mobile)

- **Aujourd'hui**, l'app embarque 22 textes en français et en anglais (`messages.ts`).
  Les libellés des formulaires, eux, viennent déjà de l'API.
- **Demain, tous les textes viennent de l'API**, à une exception près, qui reste
  **locale** :
  - le message « pas de connexion » ;
  - le bouton Réessayer de l'écran hors ligne.

  Ces deux textes s'affichent en français ou en anglais selon l'appareil. Le même
  message sert à l'écran hors ligne et aux erreurs réseau en cours d'usage, par exemple
  un formulaire envoyé sans réseau.
- **Web** :
  - chaque module déclare ses textes pour l'app dans `lang/{fr,en}/mobile.php` (Auth :
    accueil, profil, photo ; Form : chargement, envoi, choix, mot de passe), et
    l'application ses textes communs (erreur inconnue) ;
  - nouvelle route publique **`GET /api/v1/messages`**, qui renvoie tous ces textes en
    une fois, aplatis par clé (`home.greeting` → « Bonjour :name »), dans la langue du
    compte, sinon `Accept-Language` ;
  - les variables suivent la convention Laravel, `:name` ;
  - un test garantit que le français et l'anglais ont exactement les mêmes clés.
- **Mobile** :
  - au démarrage, l'app charge les langues puis les textes de la langue retenue ;
  - pendant le chargement, elle affiche un indicateur, sans aucun texte ;
  - quand la langue change dans le profil, elle recharge les textes ;
  - une clé absente de la réponse s'affiche telle quelle (`home.greeting`), ce qui rend
    l'oubli visible au lieu de planter ;
  - `messages.ts` ne garde que les deux textes hors ligne et la liste des clés (pour le
    typage).
- **Messages d'erreur de l'API** : le 401 « Unauthenticated. », resté en anglais au
  chantier précédent, est traduit par l'API comme les autres.

### Titre et sous-titre d'un formulaire

- `Form` gagne `title()` et `subtitle()`, tous deux facultatifs (`null` par défaut). La
  réponse de `GET /api/v1/forms/{form}` les expose sous les noms `title` et `subtitle`.
- `DynamicForm` affiche le titre, puis le sous-titre en dessous, au-dessus des champs.
- **Formulaire de connexion** :
  - titre : « Se connecter » / « Sign in » ;
  - sous-titre : « Accédez à votre compte yamo. » / « Access your yamo account. ».

  L'écran de connexion n'a plus de titre propre : il affiche celui du formulaire.

### Aide et erreur sous un champ

- **L'aide existe déjà** : `Field::help()` côté API, sous le nom `help` dans la réponse,
  et le texte s'affiche sous le champ dans l'app.
- **Correction** : aujourd'hui l'erreur **remplace** l'aide. Demain, les deux sont
  distinctes :
  - l'aide reste **toujours** affichée sous le champ, en gris, comme une simple
    indication ;
  - l'erreur de l'API s'affiche **en plus**, sous l'aide, en rouge, annoncée aux lecteurs
    d'écran comme une alerte ;
  - le champ en erreur garde sa bordure rouge.
- Aucun formulaire ne se sert encore de l'aide. J'en ajoute une sur le champ Langue du
  profil, « Langue de l'application et des e-mails. », pour la montrer à l'usage.

### Rangement des médias (correction du chantier photo de profil)

- Tout média est rangé sous **`<module>/<champ>/<uuid>/`**. Le module s'écrit en
  minuscules, avec des tirets s'il est composé (`auth`). Le champ est le nom de la
  collection medialibrary (`avatar`). La photo de profil devient donc
  `auth/avatar/<uuid>/avatar.webp`, et sa vignette
  `auth/avatar/<uuid>/conversions/avatar-thumb.webp`.
- L'UUID reste en dessous : l'adresse publique ne se devine toujours pas.
- Le module se déduit de la classe du modèle (`Modules\Auth\Models\User` → `auth`).
  Un modèle hors module (`App\…`) est rangé sous `app/<champ>/`.
- C'est porté par le générateur de chemins (`UuidPathGenerator`, renommé
  `ModuleFieldPathGenerator`), donc valable pour tout média futur sans rien à écrire.
- La base ne contient aucun média aujourd'hui : rien à déplacer.

## Découpage

1. Web : route des textes, fichiers `mobile.php`, titre et sous-titre de `Form`,
   traduction du 401, rangement des médias par module et champ, tests → `qa-check` → commit `yamo-web`.
2. Mobile : textes depuis l'API, écran hors ligne, titre et sous-titre dans
   `DynamicForm`, aide et erreur distinctes, tests → `qa-check` → commit `yamo-mobile`.
3. Vérification de bout en bout dans le simulateur iOS, y compris sans réseau
   (serveur DDEV arrêté).

## Critères de succès

1. En dehors de l'écran hors ligne, aucun texte affiché par l'app n'est écrit dans son
   code : modifier un texte dans un fichier `mobile.php` de Laravel le change dans l'app
   au prochain chargement.
2. L'app affiche ses textes dans la langue du compte connecté, sinon dans la meilleure
   langue de l'appareil. Changer de langue dans le profil change aussi les textes de
   l'app.
3. Sans serveur joignable au démarrage, l'app affiche le message et le bouton Réessayer
   locaux, dans la langue de l'appareil. Réessayer une fois le serveur revenu affiche
   l'app normalement.
4. Une erreur réseau en cours d'usage affiche le même message local.
5. Le formulaire de connexion affiche son titre puis son sous-titre, venus de l'API, en
   français et en anglais.
6. Un formulaire sans titre ni sous-titre s'affiche comme aujourd'hui.
7. L'aide d'un champ s'affiche sous ce champ (vu sur le champ Langue du profil) et
   reste visible quand une erreur s'affiche. L'erreur est distincte : en dessous, en
   rouge, en alerte.
8. Le 401 de l'API est traduit.
9. Tests web et mobile, typage, lint, Pint au vert. `RouteGuardTest` motive l'exception
   de la route publique `GET /api/v1/messages`. `openapi.json` est régénéré.

10. Un média enregistré, par l'API comme par le back-office, se trouve sous
    `<module>/<champ>/<uuid>/` (photo de profil : `auth/avatar/<uuid>/`).

## Hors périmètre

Textes modifiables depuis Filament ; textes gardés sur le téléphone pour un usage hors
ligne ; textes du back-office Filament (déjà traduits par Laravel).

---

## Ce que j'ai décidé seul

- **L'écran hors ligne couvre aussi un serveur injoignable**, et pas seulement l'absence
  de réseau. L'essai réel l'a imposé : DDEV arrêté, son routeur répond 404. L'app
  démarrait alors sans textes, affichait des clés brutes et renvoyait à la connexion
  alors que la session était valide. Désormais, tout échec de chargement au démarrage
  (langues, compte, textes) affiche l'écran local, sauf un jeton refusé, qui renvoie à
  la connexion. Le message est élargi : « Connexion impossible. Vérifiez votre connexion
  internet, puis réessayez. »
- **Ordre des middlewares** : `SetLocale` passe avant l'authentification. Sans cela, un
  401 sortait dans la langue par défaut, quelle que soit la langue demandée.
- **Le 403 est traduit en plus du 401**, avec la même règle : seuls les messages par
  défaut de Laravel sont remplacés, et un message propre à une policy est conservé (test
  dédié).
- **Textes servis aplatis**, sans préfixe de module. Un test interdit deux sources de
  déclarer la même clé.
- **Bouton Réessayer d'un formulaire** : local quand le réseau manque, servi par l'API
  (`form.retry`) pour une autre erreur. Il remplace le glyphe `↻`.
- **Rangement des médias** : le module et le champ sont en minuscules avec tirets
  (`event-booking/cover-image`).

## Ce qui a été fait

- **Web** (`41f2c0d`) :
  - `GET /api/v1/messages` : 21 textes, servis depuis les fichiers `mobile.php` de
    l'application et des modules Auth et Form ;
  - titre et sous-titre de `Form` : connexion « Se connecter » / « Sign in » ;
  - aide sur le champ Langue ;
  - 401 et 403 traduits ;
  - médias rangés sous `<module>/<champ>/<uuid>/` ;
  - 91 tests, `openapi.json` régénéré.
- **Mobile** (`742e9ea`) :
  - textes servis par l'API, rechargés au changement de langue sans démonter la
    navigation ;
  - `OfflineScreen` local avec Réessayer ;
  - titre et sous-titre dans `DynamicForm` ;
  - aide et erreur distinctes, plus de double aide ;
  - 32 tests, README.
- **Simulateur iOS** :
  - connexion avec le titre et le sous-titre de l'API, dans la langue de l'appareil ;
  - textes du compte une fois connecté ;
  - aide sous le champ Langue ;
  - passage en anglais sans quitter le Profil ;
  - écran hors ligne avec DDEV arrêté, puis Réessayer → accueil, session conservée.
- **qa-check** : GO pour le web (`03-qa-check-web.md`) et GO pour le mobile
  (`04-qa-check-mobile.md`).

## Ce que je n'ai pas vérifié

- **Coupure réseau réelle** (mode avion) : seul le cas « serveur arrêté » a été joué.
  La coupure réseau passe par le même chemin dans le code.
- **Démarrage hors ligne, 401 au démarrage, conservation des textes au changement de
  langue** : vérifiés dans le simulateur, mais aucun test automatisé ne couvre cette
  logique d'`AuthProvider`.
- **Envoi réel d'une photo par Filament** dans le nouveau rangement : couvert par un
  test Livewire seulement.
- **Android**.
- **Un serveur qui ne répond jamais** : le client API n'a pas de délai d'expiration, et
  l'app attendrait sur l'écran de démarrage.

## Ce qui reste en suspens

- **Délai d'expiration des requêtes** côté mobile (voir ci-dessus).
- **Textes natifs figés au build** : celui de la demande d'accès aux photos d'iOS
  (`photosPermission`, `app.json`) ne peut pas venir de l'API.
- **Glyphes décoratifs** (`←`, `*`, `✓`) laissés dans l'app.
- **Repli `field.label ?? field.name`** dans les libellés d'accessibilité (relevé
  mineur de qa-check).
