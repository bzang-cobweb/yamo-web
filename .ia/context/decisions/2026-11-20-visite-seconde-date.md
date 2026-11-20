# Demande de visite : une seconde date facultative

- **Type** : décision de conception
- **Date** : 2026-11-20
- **Décidé par** : l'humain (« garder check_in pour date de visite ; utiliser aussi le champ check_out pour pouvoir proposer de façon optionnelle une seconde date de visite »)

## Décision

- **Les colonnes** : une demande de visite garde sa date dans `check_in`. Elle peut proposer une seconde date, facultative, dans `check_out`.
- **Le formulaire** : la seconde date (« Seconde date (facultatif) ») ne peut pas être la même que la première, ni tomber sur un jour bloqué.
- **Le texte** : « demande une visite le 23 nov. 2026 ou le 25 nov. 2026 pour … ».
- **Le calendrier** : les deux jours sont colorés, et le panneau de chaque jour montre la demande.
- **Pour une visite**, `check_out` n'est ni un départ ni une durée : `Booking::days()` vaut `null`, et elle ne bloque aucun séjour.
