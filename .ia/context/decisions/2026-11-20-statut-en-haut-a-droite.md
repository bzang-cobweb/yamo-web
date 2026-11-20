# Notification : la pastille du statut en haut à droite ; le calendrier comme la liste

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« apply only to calendar cards » ; « mettre le statut comme une pastille en haut à droite »)
- **Complète** : 2026-11-20-type-et-statut.md, 2026-11-20-carte-sans-photo.md

## Décision

- **Les cartes de demande du calendrier** suivent la carte de la liste : sans photo ; le type et le statut, le texte, le total, le nom de qui demande (sans lien), la date alignée à droite, les boutons. Toujours sans le message.
- **La page d'une notification** garde son en-tête (photo, nom, contact).
- **Le statut** est une pastille **en haut à droite**, sur la ligne du type (en gras, à gauche), sur toutes les vues d'une notification (carte, page, calendrier) : un seul composant, `NotificationType`.
