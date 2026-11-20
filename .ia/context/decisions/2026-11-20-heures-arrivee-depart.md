# Heures d'arrivée et de départ d'un meublé ; les dates d'une demande à part

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Remplace en partie** : 2026-11-19-arrivee-depart-en-informations.md (les heures redeviennent des champs)
- **Complète** : 2026-11-20-texte-sans-verbe.md, 2026-11-20-personnes-a-part.md

## Décision

- **L'offre** : un meublé a de nouveau `check_in_from` (« Arrivée à partir de ») et
  `check_out_until` (« Départ avant »), deux listes d'heures de 06:00 à 23:00 par demi-heure,
  visibles pour `rental_type = furnished` seulement, facultatives, dans l'étape « Règles »
  (formulaire de l'app, section « Règles » de la fiche, onglet du back-office).
- **Les dates d'une demande**, une par ligne (`Booking::dateLines()`, servies dans
  `booking.dates` sur la page d'une notification), après le texte :
  - un séjour : « Arrivée le 5 déc. 2026 à partir de 14:00 », « Départ le 7 déc. 2026 au plus
    tard à 11:00 » (« au plus tard à » plutôt que « avant », demande de l'humain) ; sans heure renseignée, la date seule ;
  - une visite : « Visite le 28 nov. 2026 », ou « Visite le 28 nov. 2026 ou le 30 nov. 2026 »
    quand une seconde date est proposée (`check_out`).
- **Le texte de la demande** ne contient plus les dates : « Séjour (2 jours) pour « Studio »,
  20 000 FCFA / jour. », « Visite pour « Maison », 400 000 FCFA / mois. ». `period_label`
  est remplacé par `dates`.
- Données de démo : les meublés reçoivent 14:00 et 11:00 (`DemoSeeder::hours()`, rejouable).

**Mise en forme** (demande de l'humain : « pour la ligne du checkin mettre label checkin à
gauche et date à droite ; faire la même pour checkout ; même principe pour les autres types de
location ») : chaque ligne est un libellé et une valeur (`booking.dates[] = {label, value}`) —
« Arrivée » | « 5 déc. 2026 à partir de 14:00 », « Départ » | « 7 déc. 2026 au plus tard à 11:00 »,
« Visite » | « 28 nov. 2026 », « Seconde date » | « 30 nov. 2026 » (la seconde date d'une visite
sur sa propre ligne : décidé seul).

## Motif

Demande de l'humain du 2026-11-20 : « Pour un meublé : check_in => date d'arrivée au plus tôt
heure de checkin à mettre dans l'offre ; check_out => … heure de checkout à mettre dans
l'offre. Pour les autres : check_in => date de la visite ou check_out si remplie. Mettre après
le summary et retire-les ». La ligne du départ, écrite « date d'arrivée au plus tôt », est
lue comme la date de départ, au plus tard à l'heure de départ (décidé seul).

## Portée

`RentalDomain`, textes `offer::rental.rules` et `offer::booking.dates`, `Booking`,
`BookingResource`, la page d'une notification dans l'app, `openapi.json`.

## Corollaires

- Une heure enregistrée avant le retrait de 2026-11-19 (encore dans `offers.details`)
  réapparaît : elle était conservée.
- Les cartes de la liste et du calendrier ne disent plus les dates de la demande.
- Le texte complet (`body`, e-mail, SMS) garde les dates.
