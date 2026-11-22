# Carte de notification : le texte après le type, nom du logement en lien

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« mettre le texte de la notification sur les cards notifications après le type » ; « mettre toujours le lien vers le logement sur le nom du logement »)

## Décision
- Sous le type, la carte reprend le texte de la notification. Quand le nom de qui envoie est affiché dessous, c'est le texte sans ce nom (`summary`) ; sinon, le texte entier.
- Dans ce texte, le nom du logement ouvre l'annonce, et le nom d'un membre ouvre sa page (`text_links`). Chaque lien marque la notification lue.

## Portée
`NotificationsScreen` (app). Remplace, pour la carte, « sans liens dans le texte » (2026-11-22-carte-notification-vers-detail).
