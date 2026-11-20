# Calendrier : les demandes d'un jour en cartes de notification

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« dans le calendrier, utiliser card de notification + prix total en gras pour location meublé + boutons accepter et refuser » ; « enlever le click sur le membre depuis le card » ; « mettre prix total en gras dans notification detail »)
- **Complète** : 2026-11-19-contrat-calendrier-offre.md, 2026-11-19-carte-notification.md

## Décision

**Dans le panneau d'un jour du calendrier**, chaque demande est une carte de notification :
- qui demande (photo, nom, en ligne) et la date de la demande ; le membre n'y est **pas cliquable** ;
- la pastille du type (« Demande de logement en attente », « … acceptée ») ;
- le même texte que la notification (dates, durée, prix, voyageurs) ;
- le message ;
- le total en gras (logement meublé) ;
- Accepter et Refuser, tant que la demande est en attente.

**Sur la page d'une notification**, le total figé de la demande s'affiche en gras sous le texte (logement meublé).

**Le code partagé** :
- le texte d'une demande : `BookingText` ;
- les données de qui agit : `ActorData` ;
- l'en-tête de carte : `NotificationActor`, dont le lien est facultatif.

## Corollaires

- **Téléphones de démonstration** : les membres de démonstration ont un téléphone (`DemoSeeder::PHONES`), jamais vérifié, pour qu'aucun SMS ne parte.
