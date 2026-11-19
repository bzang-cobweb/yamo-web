# Contrat — Champ `upload` et étape « Photos » du formulaire de logement

Demande : « ajouter une étape photos qui affiche un nouveau type de champ upload
(le type de fichier, la taille, ... sont définis par laravel) ».

## Ce que j'ai compris

1. Le module Form gagne un **type de champ générique `upload`**, utilisable par
   n'importe quel formulaire. Laravel déclare tout ce qui le contraint, et l'app
   ne fait qu'appliquer ce qu'on lui sert :
   - les types de fichier acceptés (types MIME) ;
   - la taille maximale d'un fichier (en Ko) ;
   - le nombre maximal de fichiers (1 = un seul fichier) ;
   - l'adresse d'envoi.
2. Le formulaire du logement gagne une **5ᵉ étape « Photos »**, la dernière,
   après « Adresse ». Elle reprend les réglages de l'onglet Photos du
   back-office : JPEG, PNG ou WebP, 20 photos au plus, dans l'ordre choisi.
3. Les photos ajoutées depuis l'app sont celles que montrent déjà la liste, la
   fiche de l'offre et le back-office (collection `photos` existante).

## Découpage prévu

**Web (module Form)**

- `Field::upload()` avec `accept([...])`, `maxSize($kb)` et `maxFiles($n)`.
- La définition servie porte `upload: {accept, max_size_kb, max_files, endpoint}`.
- La validation est générée à partir de cette même définition (`file`,
  `mimetypes`, `max`), à l'envoi d'un fichier comme à l'enregistrement du
  formulaire. Les limites n'existent donc qu'à un seul endroit.
- **Envoi immédiat** : chaque photo choisie part tout de suite vers
  `POST /api/v1/forms/{form}/uploads/{field}`.
  - Le fichier est validé selon les règles du champ, puis gardé comme fichier
    **temporaire** au nom de son auteur.
  - L'appel rend `{id, url}` ; la valeur du champ est la liste ordonnée de ces
    `id`.
  - Un fichier temporaire non utilisé est supprimé après 24 h, par une commande
    planifiée.
- Version back-office : `FieldComponent` affiche un `upload` par un `FileUpload`
  Filament avec les mêmes limites.

**Web (module Offer)**

- `RentalDomain` : champ `photos` de type `upload`, 5ᵉ étape « Photos ».
- À l'enregistrement (création ou modification), les fichiers temporaires de
  l'auteur sont rattachés à la collection `photos`, dans l'ordre reçu.
  - L'image est **optimisée comme la photo de profil** : 1600 px au plus sur le
    grand côté, réencodée en WebP, original non conservé.
  - Une photo existante absente de la liste est retirée.
  - Un `id` qui n'appartient pas à l'auteur est refusé (422).

**Mobile**

- Composant `UploadField` :
  - choix dans la **galerie seulement**, plusieurs à la fois (`expo-image-picker`,
    déjà installé) ;
  - vignettes avec retrait (×) et indicateur d'envoi ;
  - ordre : la première photo est la photo principale ;
  - il applique `accept`, `max_size_kb` et `max_files` avant même l'envoi, avec
    un message clair ;
  - l'étape ne passe pas tant qu'un envoi est en cours.
- **Aucune nouvelle dépendance.**

## Critères de succès (état observable)

- Le formulaire de l'offre dans l'app a 5 étapes ; la dernière, « Photos »,
  permet d'ajouter, retirer et ordonner des photos de la galerie.
- Après l'enregistrement, ces photos apparaissent sur la liste (1ʳᵉ photo), la
  fiche de l'offre et l'onglet Photos du back-office, dans le même ordre.
- Changer, dans Laravel seulement, les types acceptés, la taille maximale ou le
  nombre maximal change le comportement de l'app (message affiché) et le refus
  du serveur, sans modifier l'app.
- Un fichier non conforme envoyé directement à l'API (mauvais type, trop lourd,
  photo de trop) est refusé avec une erreur sous le champ.
- Un utilisateur ne peut pas rattacher le fichier temporaire d'un autre.
- Les photos enregistrées sont en WebP, 1600 px au plus.
- Tests serveur (suite complète) et tests de l'app verts ; contrôle de
  `bza-dev:qa-check` ; vérification au simulateur.

## Points à trancher

1. **Moment de l'envoi** : chaque photo part dès qu'elle est choisie (prévu ci-dessus) ou toutes ensemble à la fin.
   - Recommandation : dès qu'elle est choisie. Les envois sont plus courts, les erreurs apparaissent photo par photo, l'enregistrement reste en JSON, et la modification fonctionne de la même façon.
2. **Photo obligatoire ?**
   - Recommandation : facultative au lancement.
   - Défaut : facultative.
3. **Optimisation** : 1600 px, WebP.
   - Recommandation : oui, comme la photo de profil.
4. **Appareil photo** : galerie seulement, comme pour la photo de profil (aucune permission caméra).
   - Recommandation : galerie seulement pour l'instant.

---

## Ce que j'ai décidé seul

- Photos obligatoires pour **tous** les domaines (le champ est commun à `OfferForm`), pas seulement le logement ; aucun domaine actuel n'en est privé.
- Le back-office garde son propre `SpatieMediaLibraryFileUpload` (onglet Photos), sans obligation : un administrateur peut corriger une offre sans photo. `FieldComponent` ne traduit pas le type `upload` (aucun attribut de domaine n'en est un).
- L'envoi rend `{id}` seul, pas `{id, url}` : l'app affiche l'image du téléphone. Les photos déjà enregistrées sont servies avec leur adresse dans `upload.files`.
- L'ordre se change par une étoile « Mettre en premier » sur chaque vignette (pas de glisser-déposer). Le libellé complet est lu par l'accessibilité.
- Taille maximale 10 Mo par photo (`OfferPhotoService::MAX_UPLOAD_KB`), contrôle des dimensions (10 000 px) quand seuls des types image sont acceptés.
- Purge par `model:prune` toutes les heures (`routes/console.php`), fichier en attente valable 24 h.

## Ce qui a été fait

- Module Form : type `upload`, `Field::upload()->accept()->maxSize()->maxFiles()->files()`, `fileRules()`, règles de la valeur (liste, `max`, `distinct`, identifiant connu ou fichier en attente du compte pour ce champ), `upload` servi dans la définition avec l'adresse d'envoi ; modèle `FormUpload` (disque privé, uuid, `Prunable`), migration, `POST /api/v1/forms/{form}/uploads/{field}` (connecté, `throttle:60,1`).
- Module Offer : champ `photos` obligatoire, 5ᵉ étape « Photos » ; `OfferPhotoService` (rattache, optimise en WebP 1600 px, retire les photos non citées, ordonne) appelé à la création et à la modification dans une transaction ; `OfferRequest` lié à l'offre modifiée.
- App : `UploadField` (galerie, plusieurs à la fois, limites servies appliquées, vignettes, retrait, photo principale, envoi un à un), « Suivant » / « Enregistrer » bloqués pendant un envoi.
- Tests : 10 tests serveur dédiés (`OfferPhotoApiTest`), tests existants mis à jour (photos désormais requises) ; 7 tests app (`UploadField.test.tsx`). Suite serveur 498 verte, app 227 verte, tsc et lint propres.
- Vérifié au simulateur : création avec 2 photos (WebP 1600 px, ordre choisi, fichiers en attente retirés), photo principale dans « Mes logements », modification préremplie, refus sans photo. QA indépendante : GO, 0 bloquant.

## Ce que je n'ai pas vérifié

- Que la tâche planifiée tourne en production (cron `schedule:run`).
- Des envois réellement concurrents et le limiteur de débit sous charge.
- Le rendu sur Android et sur un vrai téléphone (photos HEIC converties en JPEG par le sélecteur).

## Ce qui reste en suspens

- Deux enregistrements simultanés citant le même fichier en attente : le second peut finir en 500 au lieu de 422 (QA, mineur).
- Fichiers WebP orphelins possibles si l'enregistrement échoue après l'ajout d'une photo : la transaction couvre la base, pas le disque (QA, mineur).
- L'app considère tout fichier choisi comme JPEG (le sélecteur recompresse) : si Laravel retirait `image/jpeg` des types acceptés, l'app refuserait tout (QA, mineur).
- `OfferPhotoService::sync` vise le champ `photos` par son nom : à généraliser s'il y a un jour plusieurs champs de fichiers par formulaire.
