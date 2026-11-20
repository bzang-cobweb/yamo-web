# Pour le propriétaire, le statut de l'offre à la place du cœur de favori

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-19-statut-carte-logement.md, 2026-11-19-contrat-vedette-favoris.md

## Décision

Le propriétaire ne met pas son logement en favori ; à la place du cœur, en haut à droite de la
photo, il voit le statut de l'offre en pastille de couleur (« Publiée », « Brouillon »…) :

- « Mes logements » : `ListItem::tag(..., onImage: true)` sert `on_image: true` ; l'app place
  la pastille sur la photo quand l'item en a une (sinon, sous la photo, comme avant) ;
- fiche de l'offre : `status` porte son ton (`tone`) ; l'app l'affiche en pastille sur la photo,
  à la place de la ligne de texte sous la photo.

Les autres listes (réservations, demandes) gardent leur pastille sous la photo.

## Motif

Demande de l'humain du 2026-11-21 : « quand un propriétaire visualise son offre dans mes
logements cards ou détail remplacer le bouton favori par le statut de l'offre à la même
position ». Choix d'un drapeau servi par le serveur, pour ne pas changer les autres listes :
décidé seul.
