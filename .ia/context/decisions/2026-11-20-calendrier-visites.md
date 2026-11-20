# Calendrier d'un logement non meublé : le même style que les séjours

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« calendrier garder le même style que les location meublé »)
- **Remplace en partie** : 2026-11-19-contrat-calendrier-offre.md (« une visite est un point sur son jour »)

## Décision

- **Une visite colore son jour**, comme un séjour colore ses nuits :
  - en attente, orange ;
  - acceptée, verte ;
  - en attente sur un jour bloqué, rouge (chevauchement).
- **Plus de point sous le jour** : l'API sert `dot` à `null`, gardé pour les anciennes versions de l'app.
- **La légende** a des cases colorées, comme pour un logement meublé : « Visite acceptée », « Visite en attente », « Chevauchement », « Bloqué ».
