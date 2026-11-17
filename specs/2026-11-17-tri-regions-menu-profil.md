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
