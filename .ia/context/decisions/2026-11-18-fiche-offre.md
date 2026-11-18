# `2026-11-18` — Fiche détaillée d'une offre, décrite par le serveur

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-18-fiche-offre.md`.
- **Réponse** : « Approved ».
- **Ce qui est retenu** :
  - `GET /api/v1/offers/{offer}` est ouvert sans connexion. Il montre les offres publiées du pays courant ; le propriétaire connecté voit aussi ses brouillons et archives. Toute autre offre répond 404 ;
  - la fiche est décrite par le serveur : titre, prix et son libellé, ville, photos, description, caractéristiques { libellé, valeur } tirées du schéma du domaine, date de publication ;
  - la rue n'est jamais servie ;
  - `Field` gagne une unité facultative (`suffix`) et sait rendre une valeur lisible ;
  - dans l'app, toucher une offre de l'accueil ouvre sa fiche (carrousel de photos, puis le reste). « Mes logements » ouvre toujours le formulaire.
- **Motif** : la vitrine mène à une fiche, sans que l'app ne connaisse le domaine.
- **Portée** : modules Form (`Field`) et Offer ; app mobile.
- **Corollaires** : le contact, les favoris, le partage, la carte et le zoom des photos viendront plus tard.
- **Tranché par** : humain
