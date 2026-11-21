# Détail d'une réservation : ce que voit chaque partie, et dans quel ordre

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-21-afficher-adresse.md, 2026-11-21-contact-toujours.md

## Décision
Sous le titre d'une demande :
- **pour le membre qui l'a faite** : l'adresse de l'offre si la demande l'affiche, puis le
  téléphone et l'e-mail de l'offre, c'est-à-dire ceux de l'hôte. Le nom de l'hôte ne s'affiche
  plus ;
- **pour l'hôte** : l'adresse de l'offre (toujours), puis le nom du membre (lien vers sa page),
  son téléphone et son e-mail.

La suite ne change pas : personnes, dates, puis « Voir plus » pour la durée, les prix et le
total. La clé de texte `offer::booking.lines.host` est retirée.

## Motif
Demande de l'humain du 2026-11-21, qui précise l'ordre pour chaque partie. « Téléphone de
l'offre » et « e-mail de l'offre » sont lus comme ceux de l'hôte : l'offre n'a pas ses propres
coordonnées.
