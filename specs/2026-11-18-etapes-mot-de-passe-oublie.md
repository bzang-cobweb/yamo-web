# Contrat — Formulaires à étapes, mot de passe oublié et retouches

- **Chantier** : `2026-11-18-etapes-mot-de-passe-oublie`
- **Demande** (chat, 2026-11-16, 8 points) :
  1. enlever l'e-mail de la page de profil ;
  2. constantes de `VerificationCodeService` lues dans la config `auth`, mêmes valeurs
     par défaut si le `.env` ne les définit pas ;
  3. renommer `$codes` (type `VerificationCodeService`) en `$codeService` ;
  4. les régions deviennent un *relation manager* des pays ;
  5. lien de retour fixé en haut, libellé = titre de l'écran de départ, contenu qui
     passe dessous comme sous le menu du bas ;
  6. champ genre dans l'inscription, après le nom ;
  7. formulaires à une ou plusieurs étapes, chaque étape validée par l'API avant la
     suivante, tout le formulaire validé à la dernière (exemple : l'inscription en 3
     étapes) ;
  8. mot de passe oublié, sur le modèle de la connexion : identifiant → code → nouveau
     mot de passe et confirmation.
- **Règle permanente** : chaque point est livré côté Laravel **et** côté app.
- **Statut** : validé le 2026-11-16, **point 1 annulé** (décision
  `2026-11-18-contrat-etapes-mot-de-passe-oublie.md`)

## Ce que j'ai compris

### 1. Profil sans e-mail — **annulé**

> Annulé par l'humain à la validation : l'e-mail reste affiché dans le profil.

L'e-mail n'est plus affiché sous le nom dans l'écran Profil. **Le téléphone reste
affiché** (non demandé). Les liens « Ajouter / Changer l'adresse e-mail » restent.

### 2. Réglages des codes dans la config `auth`

- La config du module (`Modules/Auth/config/config.php`, fusionnée sous la clé `auth`)
  reçoit :
  ```php
  'verification_codes' => [
      'minutes'        => (int) env('AUTH_CODE_MINUTES', 10),
      'max_attempts'   => (int) env('AUTH_CODE_MAX_ATTEMPTS', 5),
      'resend_seconds' => (int) env('AUTH_CODE_RESEND_SECONDS', 60),
  ],
  ```
- `VerificationCodeService` lit ces valeurs ; ses constantes disparaissent.
- Les trois variables sont ajoutées à `.env.example`, commentées avec leur valeur par
  défaut. Le `.env` n'est pas touché : sans elles, les défauts s'appliquent.
- Un test vérifie qu'une valeur changée dans la config change le comportement.

### 3. Renommage

Partout où une variable ou une propriété de type `VerificationCodeService` s'appelle
`$codes`, elle devient `$codeService` : services, contrôleurs, `Challenges`.

### 4. Régions dans la fiche pays (Filament)

- La fiche d'un pays affiche un onglet **Régions** (relation manager) : liste, création,
  modification, activation, suppression selon les policies existantes.
- La ressource Régions autonome **disparaît du menu et est supprimée** : on gère une
  région depuis son pays.
- Les tests Filament des régions passent par le relation manager.

### 5. En-tête de retour fixe (app)

- Un composant d'en-tête commun : **fixé en haut**, sous la zone sûre (encoche), avec
  « ← *titre de l'écran de départ* ». Le contenu défile **dessous**, comme sous le menu
  du bas.
- Libellés :
  - formulaires du profil (informations, e-mail, téléphone, mot de passe, préférences)
    → « ← Profil » ;
  - Créer un compte et Mot de passe oublié → « ← Se connecter ».
- Le lien « Retour » placé dans l'étape de code devient **« Étape précédente »**, pour
  ne plus doubler celui de l'en-tête.
- Libellés servis par l'API (`lang/*/mobile.php`).

### 6. Genre à l'inscription

Le champ genre (`select`, obligatoire comme dans Informations personnelles) vient
**juste après le nom**. Il est enregistré à la création du compte.

### 7. Formulaires à étapes

**Module Form (générique)** :
- un formulaire peut déclarer des **étapes**, chacune avec un titre facultatif et la
  liste de ses champs. Sans étapes, le formulaire est d'un seul tenant, comme
  aujourd'hui ;
- la définition servie à l'app inclut `steps` : `[{title, fields: [noms]}]` ;
- **nouvel appel générique** `POST /api/v1/forms/{form}/steps/{n}` : il valide
  seulement les champs de l'étape `n` (règles du formulaire). La réponse est 204 si
  tout est valide, sinon 422 avec les erreurs, comme toute validation. Rien n'est
  enregistré. Les règles qui lient deux champs d'une même étape (e-mail *ou*
  téléphone, mot de passe et confirmation) s'y appliquent ;
- l'envoi final reste l'appel propre au formulaire (ex. `POST /auth/register`), qui
  valide **tout** le formulaire.

**App (générique, `DynamicForm`)** :
- elle affiche l'étape courante, avec « Étape 2 sur 3 » et le titre de l'étape ;
- « Suivant » valide l'étape par l'API. Si l'étape est valide, l'étape suivante
  s'affiche ; sinon, les erreurs s'affichent sous les champs. « Précédent » revient
  sans rien perdre ;
- à la dernière étape, le bouton d'envoi du formulaire envoie toutes les valeurs.

**Inscription** :
1. pseudonyme, prénom, nom, genre (l'ordre actuel prénom → nom est gardé) ;
2. e-mail, téléphone (au moins l'un des deux) ;
3. mot de passe, confirmation.

L'unicité du pseudonyme, de l'e-mail et du téléphone est vérifiée **dès l'étape
concernée**.

### 8. Mot de passe oublié

- Lien **« Mot de passe oublié ? »** sous le formulaire de connexion → écran dédié avec
  un en-tête « ← Se connecter ».
- **Étape 1** : formulaire `auth.password.forgot`, champ e-mail ou téléphone (format
  international, comme la connexion).
- **Étape 2** : formulaire de code, déjà existant. Le code part à l'e-mail ou par SMS
  selon l'identifiant. Il est valable 10 minutes et peut être renvoyé.
- **Étape 3** : formulaire `auth.password.reset`, avec le mot de passe et sa
  confirmation (mêmes règles qu'à l'inscription).
- **Sécurité** :
  - **la réponse ne révèle pas si un compte existe**. Pour un identifiant inconnu,
    l'API répond comme pour un connu (« Code envoyé à … », destination masquée), mais
    n'envoie rien, et aucun code ne sera accepté ;
  - le nouveau mot de passe n'est accepté que pour un défi dont le code a été validé
    et n'a pas expiré ;
  - une fois le mot de passe changé, **toutes les sessions de l'app sont
    déconnectées**.
- **À la fin, l'app revient à l'écran de connexion** avec « Mot de passe modifié,
  connectez-vous. ». On se reconnecte donc avec le nouveau mot de passe et un nouveau
  code.
- Côté Laravel : contrôleur et service `ForgotPassword` (envoi), le cas du nouveau
  motif dans la validation du code, et `ResetPassword` (nouveau mot de passe). Côté
  app : un service par appel.

## Découpage

1. **Web** : points 2, 3, 4, puis 6, 7 (module Form et inscription), 8 → qa-check →
   commit.
2. **Mobile** : points 5, 6, 7 (`DynamicForm` à étapes), 8 → qa-check → commit.
3. **Simulateur**, avec un compte de test créé puis supprimé :
   - inscription en 3 étapes, avec une erreur à chaque étape ;
   - mot de passe oublié de bout en bout, puis connexion avec le nouveau mot de passe ;
   - en-têtes fixes.

## Critères de succès

1. ~~Le profil n'affiche plus l'e-mail.~~ (annulé)
2. Sans variable dans le `.env`, les codes valent 10 min, 5 essais, renvoi après 60 s.
   Une valeur changée dans la config change le comportement (test).
3. Plus aucune variable `$codes` de type `VerificationCodeService`.
4. Les régions se gèrent depuis la fiche d'un pays. Il n'y a plus d'entrée Régions dans
   le menu Filament.
5. Dans chaque sous-écran, l'en-tête de retour reste en haut pendant le défilement,
   avec le titre de l'écran de départ. Le contenu passe dessous et n'est jamais caché
   en fin de défilement.
6. Le genre est demandé à l'inscription, juste après le nom, et enregistré.
7. L'inscription se fait en 3 étapes. Une étape invalide n'avance pas et montre ses
   erreurs. L'envoi final valide tout. Un formulaire sans étapes fonctionne comme
   avant.
8. Mot de passe oublié :
   - un identifiant inconnu reçoit la même réponse qu'un connu ;
   - le bon code permet de choisir un nouveau mot de passe, puis de se connecter avec ;
   - sans code validé, le mot de passe ne change pas ;
   - les anciennes sessions sont déconnectées.
9. Tous les textes viennent de l'API, en fr et en en.
10. Tests web et mobile, typage, lint et Pint au vert ; `openapi.json` régénéré.

## Hors périmètre

- Étapes pour d'autres formulaires que l'inscription (le mécanisme les permet).
- Mot de passe oublié dans le back-office Filament.
- Envoi réel de SMS.
