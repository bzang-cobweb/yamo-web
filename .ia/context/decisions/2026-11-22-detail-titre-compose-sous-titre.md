# Page d'une demande : le titre composé sous le nom du logement

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« Déplacer le titre composé sous le titre »)
- **Complète** : 2026-11-22-detail-logement-avec-ville.md, 2026-11-22-detail-lignes-titre-dates-adresse.md

## Décision

Le titre composé avec la ville (« Location meublée · Studio · Douala ») quitte les lignes : il n'y a plus de ligne « Logement ». Il s'affiche en tête de la page, sous le nom du logement, en gris.

Les lignes commencent maintenant par les dates (Arrivée et Départ, ou Visite), puis l'adresse, le contact (après les dates s'il n'y a pas d'adresse), les personnes, « Disponible dès le », la durée, le prix et le total.

## Portée

`BookingResource` sert `composed_title` ; la ligne `composed` et son texte sont retirés. Mobile : `NotificationDetailScreen`.
