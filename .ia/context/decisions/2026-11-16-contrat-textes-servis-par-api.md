# `2026-11-16` — Contrat du chantier textes servis par l'API, validé

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-16-textes-servis-par-api.md`, trois
  arbitrages préalables et deux corrections de l'humain.
- **Réponse** :
  - **Textes de l'app** : ils viennent tous de l'API (`GET /api/v1/messages`), à partir
    des fichiers de langue `lang/{fr,en}/mobile.php` de chaque module. Seuls le message
    « pas de connexion » et le bouton Réessayer de l'écran hors ligne restent locaux,
    selon la langue de l'appareil. Aucun texte n'est gardé sur le téléphone, et aucune
    dépendance n'est ajoutée.
  - **Formulaires** : titre et sous-titre facultatifs. Connexion : « Se connecter » /
    « Sign in », sous-titre « Accédez à votre compte yamo. » / « Access your yamo
    account. ».
  - **Aide et erreur** : distinctes. L'aide reste toujours affichée sous le champ ;
    l'erreur s'affiche en plus, en dessous, en rouge.
  - **Rangement des médias** : toujours sous `<module>/<champ>/<uuid>/`. Le module est
    en minuscules, déduit du modèle (`app` hors module). Le champ est la collection.
- **Motif** :
  - un seul endroit pour les textes, versionné avec le code ;
  - un stockage lisible par module et par champ ;
  - des adresses publiques toujours non devinables.
- **Portée** : tous les textes de l'app mobile, tous les formulaires du module `Form`,
  tous les médias (règle générale du projet).
- **Corollaires** :
  - un texte affiché par l'app sans être servi par l'API est un défaut ;
  - un nouveau média n'a rien à configurer pour être bien rangé ;
  - la décision `2026-11-16-contrat-photo-de-profil.md` reste valable, à part le
    rangement des fichiers, désormais `auth/avatar/<uuid>/`.
- **Tranché par** : humain
