# `2026-11-19` — Modèle économique : seul le propriétaire paie ; coordonnées permises dans une offre

- **Remplace** : `2026-11-19-contrat-annonces-sans-coordonnees.md`.
- **Type** : décision
- **Question posée** : comment gagner de l'argent (commission à la réservation, paiement pour consulter, paiement pour publier) ? Faut-il garder la règle « pas de coordonnées » ?
- **Réponse** : « je vise à terme un paiement uniquement pour les propriétaires. Donc pour le lancement tout sera gratuit. quand ce sera payant, les offres des propriétaires qui n'ont pas payé ne seront pas visibles. il faut donc annuler le no contact details rule ».
- **Ce qui est retenu** :
  - **à terme**, seul le propriétaire paie ; une offre d'un propriétaire qui n'a pas payé n'est pas visible ; la mise en avant d'une offre sera payante elle aussi ;
  - **au lancement**, tout est gratuit ;
  - les coordonnées sont de nouveau **permises** dans les textes d'une offre (app et back-office) : on ne gagne rien sur la mise en relation, il n'y a donc rien à protéger.
- **Motif** : le choix de l'humain ; avec un paiement à la publication, la mise en relation directe n'est pas une fuite de revenu.
- **Portée** : formulaire `offer.offer`, ressource Filament des offres.
- **Corollaires** :
  - l'outil générique reste dans le module Form (`ContactDetails`, `NoContactDetails`, `Field::withoutContactDetails()`), testé mais appliqué nulle part. On pourra le réutiliser (avis, profil) ou le supprimer ;
  - le paiement des propriétaires et la visibilité selon le paiement feront l'objet d'un chantier à part, plus tard.
- **Tranché par** : humain
