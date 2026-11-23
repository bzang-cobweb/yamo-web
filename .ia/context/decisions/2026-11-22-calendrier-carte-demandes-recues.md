# Calendrier d'un logement : la carte des demandes reçues dans le panneau du jour

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« utiliser le card de demandes reçues dans calendrier »)
- **Remplace en partie** : 2026-11-20-calendrier-cartes (la carte de notification du panneau du jour)

## Décision

Dans le panneau d'un jour du calendrier, chaque demande s'affiche avec la carte de « Demandes reçues », la même que dans la liste :
- le statut ;
- le nom du membre, en lien vers sa page ;
- le type de demande et les dates ;
- le dernier message et la pastille des non lues ;
- la date de la dernière notification, à droite.

Toucher la carte ouvre la page de la demande. Les boutons restent : Accepter et Refuser tant qu'elle attend, puis afficher ou masquer l'adresse. Le total n'est plus sur la carte : il est sur la page de la demande.

## Portée

- Serveur : `OfferCalendar::cards` sert les items de `ReceivedBookingListing` (relus par sa requête, avec `withAnswerActions`).
- Mobile : la carte d'un item de liste devient un composant partagé, `ListItemCard`, utilisé par `ProfileListScreen` et par le panneau du jour de `OfferCalendarScreen`. Le type `OfferCalendarCard` est retiré.
