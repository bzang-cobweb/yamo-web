# `2026-11-23` — Module Partenaires : un espace payant sur l'accueil

- **Type** : décision
- **Question posée** : « Ajouter donc un module pour ajouter les partenaires avec un champ date expires_at comme pour les offres plus les champs nom, boolean published, logo, description multilingue. Autres champs ? », puis « je voulais juste réserver un espace où quelques entreprises peuvent payer », « garde tous les champs sauf la catégorie », « mettre un lien url par langue ».
- **Réponse** : les demandes elles-mêmes.
- **Ce qui est retenu** :
  - module `Partner`, table `partners` : pays, nom, logo (média `logo`), description, lien et libellé du bouton traduits (un par langue), publié, début (`starts_at`) et fin (`expires_at`) de campagne, ordre (`position`), contact (e-mail, téléphone, back-office seulement), compteurs de vues et de clics ;
  - pas de catégorie ni de carrousels par thème : un seul espace, pour rester sur le logement ;
  - visible dans l'app : publié, du pays courant, campagne commencée et pas finie ; par ordre, puis le plus ancien ;
  - `GET /api/v1/partners` (sans compte) : chaque appel compte une vue par partenaire servi ; `POST /api/v1/partners/{id}/click` compte un clic (404 s'il n'est pas visible) ;
  - back-office : ressource « Partenaires », permissions `partners.*`, données au rôle administrateur ; les compteurs se lisent sans s'écrire ;
  - app : une bande « Partenaires » en bas de l'accueil, cartes à la largeur et à la police des cartes d'offres ; un toucher compte un clic et ouvre le lien dans le navigateur de l'app ; sans partenaire, rien.
- **Motif** : les demandes de l'humain ; le pays et le lien parce qu'un partenaire n'a de sens que dans son pays et qu'un espace payé doit mener quelque part.
- **Portée** : module `Partner`, `PermissionEnum`, `RoleEnum` ; `HomeScreen` et `features/partner` de l'app.
- **Corollaires** : les vues comptent les affichages de l'accueil, pas les cartes réellement vues à l'écran ; aucune facturation dans l'app, le paiement se gère hors de l'app.
- **Tranché par** : humain
