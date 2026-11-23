Remplace en partie : 2026-11-23-module-partenaires.md (contact « au back-office seulement »).

# `2026-11-24` — Page d'un partenaire : son e-mail et son téléphone

- **Type** : décision
- **Question posée** : « afficher email et téléphone sur la page de contact », puis « mettre la description après », « mettre : devant email et téléphone » → « annuler : ».
- **Réponse** : les demandes elles-mêmes ; les deux-points ont été retirés.
- **Ce qui est retenu** :
  - la page d'un partenaire (`GET /api/v1/partners/{id}`, `PartnerResource::withContact()`) sert son e-mail et son téléphone de contact ; ni l'accueil ni les listes ne les servent ;
  - dans l'app, sous le nom : « E-mail » et « Téléphone », la valeur en lien ; les toucher ouvre l'e-mail ou l'appel ; sans l'un, sa ligne n'est pas affichée ; puis la description ;
  - libellés sans deux-points.
- **Motif** : les demandes de l'humain.
- **Portée** : `PartnerResource`, `PartnerController::show()` ; `PartnerDetailScreen` de l'app ; textes `partners.email`, `partners.phone`.
- **Corollaires** : le contact saisi par un membre devient public sur la page du partenaire.
- **Tranché par** : humain
