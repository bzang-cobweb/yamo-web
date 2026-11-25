# `2026-11-25` — Le nombre de vues sur la page d'un partenaire, pour ses comptes

- **Type** : décision
- **Question posée** : « page détail d'un partenaire ajouter le nombre de vues avant la description uniquement pour les partner_user ».
- **Ce qui est retenu** :
  - `GET /api/v1/partners/{id}` renvoie `views` (« 12 vues », « Aucune vue ») seulement à un compte rattaché au partenaire (`partner_user`), administrateur ou non. C'est la même règle que sa pastille (décision 2026-11-24-partenaire-page-statut).
  - Un visiteur ou un autre membre ne le reçoit pas, et la liste de l'accueil non plus.
  - L'app l'affiche en gras juste avant la description, après le contact.
- **Tranché par** : humain (le contenu, la place, le public) ; session (le gras)
