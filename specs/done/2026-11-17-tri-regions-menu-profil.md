# Tri des régions, menu du profil dynamique

Demande du 2026-11-17.

## Ce que j'ai compris

### 1. Trier les régions d'un pays

- Chaque région a une **position** dans son pays.
- **Back-office** : sur la fiche d'un pays, l'onglet Régions se réordonne par glisser-déposer. La liste s'affiche dans cet ordre.
- Une région créée se place à la fin de son pays.
- **Partout ailleurs** : `Country::regions()` renvoie les régions dans cet ordre, et le champ Région du formulaire « Informations personnelles » aussi. L'ordre alphabétique de ce champ disparaît, et l'app suit l'ordre servi par l'API.
- **Régions existantes** : la migration leur donne l'ordre alphabétique français, ce qui garde l'ordre affiché aujourd'hui.

### 2. Menu du profil dynamique

- La liste des liens du profil est enregistrée en base et gérée dans le back-office, sous **Paramètres › Menu du profil**. Pour chaque lien, l'administrateur fixe :
  - sa position, par glisser-déposer ;
  - son activation, actif ou masqué.
- **Les liens sont les écrans de l'app** : informations personnelles, e-mail, téléphone, mot de passe, préférences. On ne peut ni en créer ni en supprimer, puisqu'un lien sans écran ne mène nulle part. La migration les crée, dans l'ordre actuel.
- **API** : `GET /api/v1/auth/me/menu` (connecté). Le contrôleur passe par un service (`ProfileMenuService`), qui renvoie la liste à afficher pour l'utilisateur, chaque lien avec une `key` et un `label` traduit :
  - seulement les liens actifs, dans l'ordre ;
  - sans les liens e-mail et téléphone si ce canal est désactivé dans la config ;
  - avec le libellé selon l'état du compte : « Ajouter mon e-mail » ou « Changer mon e-mail », et de même pour le téléphone.
- **App** : le Profil charge cette liste et affiche un lien par entrée. Chaque `key` ouvre l'écran correspondant, et une `key` inconnue de l'app est ignorée. Pendant le chargement ou en cas d'erreur, aucun lien ne s'affiche ; la déconnexion et « Supprimer mon compte » restent toujours visibles.
- Les libellés restent dans les fichiers de traduction du serveur, en français et en anglais.

## Critères de succès

- Dans le back-office, réordonner les régions d'un pays change l'ordre du champ Région dans l'app et dans `GET /api/v1/forms/personal-info`.
- Dans le back-office, masquer ou déplacer un lien du menu du profil change le Profil dans l'app au chargement suivant.
- Un canal désactivé dans la config retire son lien du menu, quel que soit le réglage du back-office.
- Les permissions du back-office suivent celles des régions ou des réglages, sans permission nouvelle.
- Tests web et mobile verts, tsc, lint et Pint propres. OpenAPI régénéré. Aucune dépendance ajoutée.

## Questions ouvertes (défaut appliqué sans réponse)

1. **Libellés modifiables dans le back-office ?** Défaut : non, ils restent dans les traductions du serveur, parce que les liens e-mail et téléphone ont deux libellés selon l'état du compte.
2. **Liens vers une page web** (CGU, aide) ? Défaut : non dans ce chantier ; le modèle pourra en accueillir plus tard.

## Ce que j'ai décidé seul

- **Permissions** : réordonner les régions demande la permission de les modifier (`reorder` dans `RegionPolicy`) ; le menu du profil demande celle des réglages (`settings.update`).
- **Ni création ni suppression des liens, même pour le super administrateur** : sa règle générale (« tous les droits ») est levée pour ces deux actions, après la remarque de la vérification.
- **Libellés de l'API** : repris des traductions déjà servies à l'app (`auth::mobile.profile.*`), sans nouvelle source. Les noms des écrans du back-office ont leur propre fichier (`profile_menu.php`).
- **Rechargement du menu** à chaque retour sur le Profil, et quand la langue, l'e-mail ou le numéro du compte change.
- **Migration des régions** : ordre alphabétique français pour les régions existantes ; une région créée sans position va à la fin de son pays.

## Ce qui a été fait

- Web `3f69ae8` : colonne `position` des régions, glisser-déposer dans l'onglet Régions d'un pays, `Country::regions()` et le champ Région triés par position.
- Web `a374eb0` : table `profile_menu_links` (5 écrans), page Paramètres › Menu du profil (ordre, activation), `GET /api/v1/auth/me/menu` via `ProfileMenuService`, OpenAPI régénéré.
- Mobile `ce4e2b6` : le Profil affiche le menu servi par l'API ; une clé inconnue est ignorée.
- Vérifié dans le simulateur : le menu suit un changement en base (Préférences en tête, mot de passe masqué), puis l'état d'origine a été remis ; un lien ouvre son écran.
- Tests : web 259, mobile 106 ; tsc, lint et Pint propres. Vérification indépendante : GO (`.ia/runs/2026-11-17-tri-regions-menu-profil/01-qa-check.md`).

## Ce que je n'ai pas vérifié

- Le glisser-déposer à l'écran dans le back-office : le navigateur intégré refuse le certificat DDEV ; seul l'appel de réordonnancement est testé.
- La migration des régions sur une base avec beaucoup de régions réelles (les 26 cantons n'ont pas été comparés un à un).

## Ce qui reste en suspens

- Dans le simulateur, le titre de l'écran Préférences s'affiche « Préférencess », alors que l'API et la traduction disent « Préférences ». Problème de rendu antérieur à ce chantier, non analysé.
- Liens vers une page web et libellés modifiables dans le back-office : écartés de ce chantier, possibles plus tard.
