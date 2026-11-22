# Réécrire les textes des notifications de demande déjà enregistrées

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« adapter les nouveaux messages des données des notifications en base de données »)

## Décision

La migration `2026_11_30_200000_rewrite_booking_notification_texts` (module Offer) réécrit les notifications de demande déjà enregistrées avec les textes en vigueur, dans la langue de leur destinataire. Elle réécrit seulement `body`, `summary` et `text_links`, et laisse tels quels le message, le statut à l'envoi et les dates.

Une ancienne copie sans champ `own` est traitée comme sa propre copie si elle n'a pas de qui agit (`user_id` nul). Une notification sans demande ou sans destinataire reste telle quelle.

## Motif

Les cartes déjà reçues se lisent comme les nouvelles.

## Risque assumé

Le `down` ne restaure rien : les anciens textes sont perdus. Les textes sont recalculés à partir de l'état actuel de la demande (titre du logement, dates), pas de leur état à l'envoi.

## Corollaires

À chaque nouveau changement de textes, il faudra une nouvelle migration du même genre si l'on veut réécrire les anciennes notifications.
