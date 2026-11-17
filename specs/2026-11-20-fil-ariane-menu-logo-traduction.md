# Fil d'Ariane, menu Paramètres, accueil, logo en haut, traduction libre

Demande du 2026-11-20, cinq points, web et mobile.

## Ce que j'ai compris

1. **Fil d'Ariane du back-office**
   - Page d'édition : deux éléments seulement, la liste de la ressource (lien) puis le titre de la fiche. Exemple : `Utilisateurs > test`. Plus de doublon du titre, plus de « Modifier ».
   - Page de liste : aucun fil d'Ariane.
   - Même règle pour la page de consultation (permission), qui est l'équivalent en lecture seule. Les pages de création gardent le fil par défaut (`Utilisateurs > Créer`).
   - La règle vit à un seul endroit, partagé par toutes les ressources.
2. **Menu** : les pays (et leurs régions) quittent le groupe « Localisation » et passent dans le groupe « Paramètres », à côté de « Application ». Le groupe « Localisation » disparaît.
3. **Accueil mobile** : la salutation (« Bonjour … ») est retirée. Le texte `home.greeting` disparaît aussi de l'API.
4. **Logo en haut, mobile** : une barre fixe en haut des écrans connectés (Accueil, Profil et ses formulaires) affiche le logo de l'application. S'il n'y a pas de logo, elle affiche son nom. Ce sont les mêmes données que l'écran de bienvenue (`GET /api/v1/app`).
5. **`t` libre** : `t(key: string, params?: Record<string, string | number>)`.
   - N'importe quelle clé est acceptée, sans liste de clés typée.
   - Les paramètres remplacent les `:nom` dans le texte servi par l'API.
   - La liste `ApiMessageKey` est supprimée.

## Critères de succès

- Sur la fiche d'un utilisateur, d'un rôle ou d'un pays, le fil affiche exactement `<Ressource> > <titre>`.
- Les listes n'affichent aucun fil.
- Le menu ne contient plus de groupe « Localisation ». « Pays » est dans « Paramètres ».
- L'accueil mobile n'affiche plus de salutation.
- Connecté, chaque onglet montre en haut le logo de l'application, ou son nom, et la barre ne défile pas avec le contenu.
- `t('n'importe.quoi', { name: 'x', count: 3 })` compile et remplace `:name` et `:count`. Une clé absente s'affiche telle quelle.
- Tests web et mobile verts. Pint, tsc et eslint propres. OpenAPI à jour si l'API change.
