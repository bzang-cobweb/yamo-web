# Le nombre de personnes d'une demande s'affiche à part

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-texte-sans-verbe.md, 2026-11-20-notification-generique.md

## Décision

- Le texte d'une demande ne dit plus le nombre de personnes : « Séjour du … (2 jours) pour
  « Studio », 20 000 FCFA / jour. » (fr, en, de ; `summary_people` supprimé,
  `summary_stay` sans `:people`).
- Sur la page d'une notification, le nombre de personnes s'affiche sur sa ligne, après
  le texte (« 1 personne »), lu dans `booking.guests_label`, rédigé par
  `BookingResource` dans la langue de la requête.

- Pour un séjour (meublé), les dates d'arrivée et de départ s'affichent aussi sur leur
  ligne, après le texte et avant le nombre de personnes (« Du 5 déc. 2026 au 7 déc. 2026 »),
  lues dans `booking.period_label`. Demande de l'humain : « afficher pour un meublé la date
  check_in au check_out après le summary ».

- Le prix du logement s'affiche aussi sur sa ligne, juste avant le total (« 20 000 FCFA /
  jour »), lu dans `booking.price_label`. Demande de l'humain : « afficher le prix avant le
  total ».

- La durée d'un séjour s'affiche sur sa ligne, avant le nombre de personnes (« 2 jours »,
  `booking.days_label`). Le texte de la demande ne garde que le type et le logement :
  « Séjour pour « Studio ». », « Visite pour « Maison ». » (`summary_stay` et
  `summary_visit` supprimés ; prix et durée retirés). Ordre sur la page : texte, dates,
  durée, personnes, prix, total. Demande de l'humain : « afficher nombre de jours avant le
  nombre de personnes ; oui retire le [prix du texte] » ; retirer aussi la durée du texte,
  devenue en double : décidé seul.

## Motif

Demande de l'humain du 2026-11-20 : « afficher après le summary le nombre de personne »,
puis, le nombre apparaissant deux fois : « retire-le » (du texte).

## Portée

Le texte d'une demande partout (page, cartes de la liste, calendrier) ; la ligne à part,
sur la page d'une notification seulement.

## Corollaires

- Les cartes de la liste et du calendrier ne disent plus ni le nombre de personnes, ni la
  durée, ni le prix.
- Les textes déjà enregistrés de la base locale ont été réécrits le 2026-11-20.
