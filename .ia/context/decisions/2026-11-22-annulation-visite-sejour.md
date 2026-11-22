# Annulation : textes « demande de visite » / « demande de séjour »

- **Date** : 2026-11-22
- **Type** : décision
- **Décidé par** : l'humain (dans le chat)

## Décision

Les textes d'une demande annulée nomment le type de la demande :

| Où | Texte (fr) |
|---|---|
| Carte du propriétaire (l'expéditeur est affiché au-dessus) | La demande de visite / de séjour pour « :offer » (:period) a été annulée. |
| Copie du membre | Vous avez annulé votre demande de visite / de séjour pour « :offer » (:period). |
| SMS | :app : :guest a annulé sa demande de visite / de séjour pour « :offer » (:period). |
| Texte complet (e-mail, page) | :guest a annulé sa demande de visite / de séjour pour « :offer » (:period). |

Le remplacement `:request` (`offer::booking.notify.request_lower`, en fr, en, de) donne « demande de séjour » ou « demande de visite ». L'objet de l'e-mail ne change pas.

## Motif

Le propriétaire sait tout de suite quelle demande a été annulée. Sur la carte, le texte ne commence plus par un verbe accroché au nom de l'expéditeur.

## Portée

Seule l'étape `cancelled` change. Les autres étapes gardent leurs textes.

## Corollaires

Le calendrier de l'offre lit le même résumé (`BookingText::summary`).
