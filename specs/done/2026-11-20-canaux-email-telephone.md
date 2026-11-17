# Canaux de compte : e-mail et/ou téléphone, au choix de l'application

Demande du 2026-11-20.

## Ce que j'ai compris

L'application décide quels canaux de compte elle utilise : **l'e-mail**, **le téléphone**, ou **les deux**, comme aujourd'hui. Il y a deux réglages booléens, et au moins un des deux doit être activé. Tout ce qui touche à l'e-mail ou au téléphone s'adapte à ce choix.

**Où se règle le choix** : dans la configuration et le `.env`, sans champ dans le back-office :
- `config('auth.channels.email')` ← `AUTH_CHANNEL_EMAIL` ;
- `config('auth.channels.phone')` ← `AUTH_CHANNEL_PHONE` ;
- les deux valent `true` par défaut ;
- si les deux valent `false`, l'application refuse de démarrer l'authentification, avec une erreur explicite.

**Ce qui s'adapte**, pour un canal désactivé :

| Endroit | Effet |
|---|---|
| Inscription (API → app) | Le champ disparaît. L'autre devient obligatoire, sans « l'un ou l'autre ». L'aide et le libellé de l'étape « Vous joindre » s'adaptent. |
| Connexion, mot de passe oublié | Le libellé « E-mail ou téléphone » devient « E-mail » ou « Téléphone ». L'aide sur le format international ne s'affiche que si le téléphone est actif. |
| Profil (app) | La ligne et le lien « Ajouter / Changer » du canal disparaissent. `POST /me/email` ou `/me/phone` répondent 403. |
| Codes de vérification | Ils ne partent que par les canaux actifs. |
| Back-office, fiche utilisateur | Le champ du canal disparaît, et l'autre devient obligatoire. La valeur déjà enregistrée reste en base. Aucun nouveau champ de réglage. |
| Commande `auth:create-user` | Elle suit les mêmes règles. |
| `GET /api/v1/app` | Expose les deux réglages (`channels: { email, phone }`), pour que l'app adapte ce qui ne vient pas d'un formulaire de l'API (le profil). |

**Comptes existants quand on désactive un canal** (défaut appliqué, la question est restée sans réponse) :
- un compte se connecte toujours avec son identifiant existant, et reçoit ses codes de connexion et de mot de passe oublié sur ce canal ;
- personne ne peut plus s'inscrire, ajouter ni changer une valeur sur le canal désactivé. Rien n'est bloqué ni supprimé.

**Les deux activés** : le comportement actuel est inchangé (e-mail et/ou téléphone, au moins un).

## Critères de succès

- Avec l'e-mail seul : l'inscription, la connexion, le mot de passe oublié, le profil et le back-office ne parlent que d'e-mail. Un téléphone ne peut être ni inscrit, ni ajouté, ni changé.
- Avec le téléphone seul : même chose, avec le téléphone.
- Avec les deux : rien ne change par rapport à aujourd'hui.
- Il est impossible de désactiver les deux canaux.
- Un compte existant qui n'a que le canal désactivé se connecte toujours.
- Tests web et mobile verts. OpenAPI régénéré. Aucune dépendance ajoutée.

## Ce que j'ai décidé seul

- **Comptes existants** : défaut appliqué faute de réponse. Ils se connectent toujours avec l'identifiant du canal désactivé, et reçoivent leurs codes de connexion et de mot de passe oublié sur ce canal. Rien n'est bloqué.
- **Libellé de l'identifiant** : avec un seul canal, il reprend le libellé existant du champ, « Adresse e-mail » ou « Téléphone ».
- **Table des utilisateurs du back-office** :
  - la colonne e-mail suit le canal e-mail ;
  - une colonne téléphone (format international) s'ajoute quand le canal est actif. Elle est masquée par défaut si l'e-mail est aussi actif ; avec le téléphone seul, elle est visible.
- **Recherche globale** : elle suit les canaux (e-mail et/ou téléphone).
- **Deux canaux désactivés** : `AuthChannels::enabled()` lève une `LogicException` explicite au premier usage (API, formulaires, back-office), pas au démarrage de Laravel.
- **Domicile** : les règles « quel canal est actif » et « quel libellé pour l'identifiant » vivent dans `Modules/Auth/app/Support/AuthChannels.php`.
- **Field::help** accepte `null` (module Form), pour qu'un champ n'affiche pas d'aide selon le réglage.

## Ce qui a été fait

- Config `auth.channels.email|phone`, et `AUTH_CHANNEL_EMAIL` / `AUTH_CHANNEL_PHONE` dans `.env.example` et `.env`.
- Formulaires de l'API :
  - inscription : champs, obligation, aide et étape « Vous joindre » ;
  - connexion et mot de passe oublié : libellé, aide, sous-titre ;
  - changement d'e-mail ou de téléphone : 403 si le canal est inactif.
- Back-office : fiche utilisateur (champ masqué, l'autre obligatoire), table et recherche globale.
- Commande `auth:create-user`.
- `GET /api/v1/app` expose `channels`, et `openapi.json` est régénéré.
- App : le profil masque la ligne et le lien du canal inactif.
- Tests : web 225 (dont `AuthChannelsTest`, 9 tests), mobile 95.
- qa-check : NO-GO au premier passage (recherche globale), puis GO.
- Simulateur, avec le téléphone désactivé : la connexion ne demande qu'une adresse e-mail, sans aide sur le téléphone. Le `.env` a été remis à `true` ensuite.

## Ce que je n'ai pas vérifié

- L'inscription à l'écran avec un seul canal : couverte par les tests de l'API, puisque l'app affiche le formulaire tel qu'il est servi.
- Le back-office à l'écran : le navigateur intégré refuse le certificat DDEV.
- L'envoi réel d'un SMS (aucun fournisseur, `LogSmsSender`).

## Ce qui reste en suspens

- **Clavier de l'identifiant** : avec l'e-mail seul, le champ de connexion reste de type texte, donc sans clavier e-mail. Le passer en type e-mail serait une petite amélioration.
- **Cache de configuration** : en production, changer `AUTH_CHANNEL_*` demande `php artisan config:cache`, ou un redéploiement.
