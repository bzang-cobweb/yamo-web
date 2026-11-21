# Détail d'une réservation : le nom, le téléphone et l'e-mail de l'autre partie

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-lignes-demande.md, 2026-11-19-page-membre.md

## Décision
Sur la page d'une demande, après ses lignes (donc après le total), trois lignes sur l'autre
partie, servies par `AddBookingToNotificationThread` :
- pour le membre qui a fait la demande : « Hôte » (son nom complet, lien `/members/{id}`),
  « Téléphone » (format international), « E-mail » ;
- pour l'hôte : « Membre », « Téléphone », « E-mail » du demandeur.

Une ligne sans valeur est omise. Elles s'affichent quel que soit le statut de la demande.

Une ligne de demande peut porter un `link` (un écran de l'app) : l'app affiche alors la valeur
comme un lien. Les autres valeurs se sélectionnent.

## Motif
Demandes de l'humain du 2026-11-21 : « détail réservation si c'est l'utilisateur : afficher le
nom de l'hôte avec un lien, le numéro de téléphone de l'hôte, l'email de l'hôte », puis « si
c'est l'hôte alors afficher les informations du membre ».

## Portée
- Ni le numéro ni l'e-mail ne sont des liens d'appel ou d'envoi : seuls des écrans de l'app
  s'ouvrent.
- Les coordonnées s'affichent même avant l'acceptation, et même si elles ne sont pas vérifiées.
  Décidé seul, à confirmer.
