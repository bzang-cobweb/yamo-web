# Afficher l'adresse ou non : sur l'offre et sur chaque demande

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-19-fiche-localisation.md, 2026-11-21-detail-reservation-adresse.md, 2026-11-21-detail-reservation-voir-plus.md

## Décision

### Colonne `show_address`
Les tables `offers` et `bookings` ont une colonne booléenne `show_address`, vraie par défaut.
Les modèles ont la même valeur par défaut. Rien ne change pour l'existant.

### Sur l'offre
- Le formulaire du logement a un interrupteur « Afficher l'adresse », dans l'étape
  « Localisation », après l'indication.
- Quand il est éteint, la fiche ne sert ni adresse ni indication : pas de bloc « Localisation ».
- Le propriétaire, lui, voit toujours ce bloc. La ville reste servie (`location`).

### Sur la demande
- Une nouvelle demande prend la valeur de son offre.
- Le propriétaire la change sur chaque carte de « Réservations reçues », quel que soit le
  statut, par l'action « Afficher l'adresse » ou « Masquer l'adresse », placée après
  « Accepter » et « Refuser ». Ces actions appellent `POST /api/v1/bookings/{id}/address/show`
  et `/address/hide`.
- Ces routes sont réservées au propriétaire de l'offre (capacité `manageAddress`) ; pour un
  autre membre, elles répondent 404.
- L'action apparaît aussi dans le panneau d'un jour du calendrier, qui reprend ces cartes.

### Back-office
- Le formulaire d'une offre a le même interrupteur, sous l'adresse et son indication.
- La fiche d'une demande montre en lecture seule si l'adresse est affichée au voyageur. Ce
  réglage se change dans l'app.

### Ordre des lignes d'une demande
1. Adresse : la première information après le titre. Pour le demandeur, elle est retirée si la
   demande la masque ; le propriétaire la voit toujours.
2. Hôte ou Membre, Téléphone, E-mail. Sans adresse, ces lignes passent en tête.
3. Personnes, puis les dates.
4. Derrière « Voir plus » : la durée, les prix et le total.

## Motif
Demandes de l'humain du 2026-11-21 :
- « ajouter un champ boolean dans booking qui permet au propriétaire d'afficher ou non
  l'adresse » ;
- « ajouter aussi le même champ au niveau de l'offre ; si ce champ est à false, le bloc
  localisation du détail de l'offre ne s'affiche pas » ;
- « par défaut, quand un utilisateur fait une réservation, le show address prend la valeur de
  l'offre » ;
- « mettre adresse avant nombre de personnes », puis « adresse si visible dans booking est la
  première information dans détail réservation après le titre » ;
- « l'hôte ou le membre, puis téléphone, puis email apparaissent après l'adresse » ;
- « Oui, ajoute-le » (le champ dans le back-office).

## Ce que j'ai décidé seul
- Le propriétaire voit toujours l'adresse, sur la fiche comme sur la demande.
- Le geste pour la demande : une action sur la carte de « Réservations reçues ».
