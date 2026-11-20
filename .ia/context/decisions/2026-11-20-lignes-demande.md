# Les lignes d'une demande : un libellé à gauche, une valeur à droite

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Remplace en partie** : 2026-11-20-personnes-a-part.md, 2026-11-20-chambres-obligatoires.md (leur mise en page)
- **Complète** : 2026-11-20-heures-arrivee-depart.md, 2026-11-20-titre-demande.md

## Décision

`BookingResource` sert `lines`, la liste ordonnée des lignes à afficher, chacune
`{key, label, value}` rédigée dans la langue de la requête ; une ligne sans valeur n'est
pas servie :

| key | label (fr) | value |
|---|---|---|
| `guests` | Personnes | « 1 » |
| `bedrooms` | Chambres | « 0 » |
| `arrival` / `visit` | Arrivée / Visite | « 5 déc. 2026 à partir de 14:00 » |
| `departure` / `second` | Départ / Seconde date | « 7 déc. 2026 au plus tard à 11:00 » |
| `days` | Durée | « 2 jours » |
| `price` | Prix | « 20 000 FCFA / jour » |
| `total` | Total | « 40 000 FCFA » |

L'app affiche chaque ligne, libellé à gauche, valeur à droite, le total en gras. Elle n'a
qu'une boucle. Les champs servis un à un (`dates`, `days_label`, `guests_label`,
`price_label`, `total`, `offer.bedrooms`) sont retirés.

## Motif

Demande de l'humain du 2026-11-20 : « pour le prix mettre le label à gauche et le prix à
droite, faire pareil pour les autres ». Une liste unique plutôt qu'un champ par ligne :
décidé seul, pour que l'ordre et les libellés viennent d'un seul endroit.

## Portée

`BookingResource`, `Booking::dateLines()` (avec `key`), textes `offer::booking.lines`, la
page d'une notification dans l'app.

## Corollaires

- Les personnes et les chambres valent un simple nombre : leur libellé dit l'unité.
