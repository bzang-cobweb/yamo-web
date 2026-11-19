Remplace en partie : 2026-11-19-contrat-centre-notifications.md (la pastille n'affiche plus de nombre, ni « 0 »).

# `2026-11-19` — Onglet « Notifications » : un point s'il y a des non lues

- **Type** : décision
- **Question posée** : « mettre juste une pastille sur notifications s'il y en a des non lues ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** : sur l'onglet « Notifications », un point de couleur, sans nombre, dès qu'il y a au moins une non lue ; rien sinon. Le lecteur d'écran annonce « Notifications, Non lue » dans ce cas.
- **Motif** : la demande de l'humain.
- **Portée** : app (`app/(tabs)/_layout.tsx`) ; l'API sert toujours le nombre.
- **Corollaires** : aucun.
- **Tranché par** : humain
