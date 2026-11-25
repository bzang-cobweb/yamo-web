# `2026-11-25` — Toucher une photo de la fiche ouvre une galerie plein écran, à la conversion `web`

- **Type** : décision
- **Question posée** : « page de détail offre le click sur une photo du carousel ouvre une galerie d'image », puis « fais la galerie plein écran avec la conversion web ».
- **Ce qui est retenu** :
  - L'API de la fiche (`GET /api/v1/offers/{offer}`) renvoie `gallery` : les mêmes photos que `photos`, dans le même ordre, à la conversion `web` (1920 px). `photos` reste à la conversion `detail` pour le carrousel.
  - Dans l'app, toucher une photo du carrousel ouvre la galerie sur cette photo : fond noir, photo entière (sans recadrage), défilement horizontal, position « 2 / 5 », bouton de fermeture. Sur iOS, on zoome en pinçant.
  - Fermer la galerie ramène le carrousel sur la dernière photo vue.
  - Une API plus ancienne, sans `gallery` : la galerie montre les photos du carrousel.
  - Aucune dépendance ajoutée : `Modal`, `ScrollView` et `expo-image`.
- **Motif** : la conversion `web` n'avait pas d'usage ; c'est la taille d'un plein écran, quand celle du carrousel (1200 px) se voit floue au zoom.
- **Portée** : la fiche d'une offre dans l'app.
- **Corollaires** : un futur site web pourra servir la même liste `gallery`.
- **Tranché par** : humain (la galerie, la conversion) ; session (la forme de la galerie, le repli sans `gallery`)
