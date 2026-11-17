# `2026-11-20` — Contrat du chantier fil d'Ariane, menu Paramètres, logo en haut et traduction libre, validé

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-20-fil-ariane-menu-logo-traduction.md`
  (5 points), et l'écran visé par « logo sur la position top menu fixe ».
- **Réponse** : « Validated » ; le logo concerne l'app mobile.
  - **Fil d'Ariane** : `<Ressource> > <titre>` sur les pages d'édition et de
    consultation ; aucun sur les listes ; création inchangée.
  - **Pays** rangés dans le groupe « Paramètres » ; le groupe « Localisation »
    disparaît.
  - **Accueil mobile** sans salutation.
  - **Barre fixe en haut** des écrans connectés de l'app : logo de l'application,
    sinon son nom.
  - **`t(key: string, params?)`** sans liste de clés typée ; les paramètres
    remplacent les `:nom` du texte de l'API.
- **Motif** : un back-office plus sobre et une app à l'identité visible ; ajouter un
  texte côté API ne demande plus de toucher un type dans l'app.
- **Portée** : back-office Filament (toutes ressources), app mobile.
- **Corollaires** : une clé mal orthographiée n'est plus détectée à la compilation ;
  elle s'affiche telle quelle à l'écran.
- **Tranché par** : humain
