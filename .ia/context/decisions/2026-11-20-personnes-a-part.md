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

## Motif

Demande de l'humain du 2026-11-20 : « afficher après le summary le nombre de personne »,
puis, le nombre apparaissant deux fois : « retire-le » (du texte).

## Portée

Le texte d'une demande partout (page, cartes de la liste, calendrier) ; la ligne à part,
sur la page d'une notification seulement.

## Corollaires

- Les cartes de la liste et du calendrier ne disent plus le nombre de personnes.
- Les textes déjà enregistrés de la base locale ont été réécrits le 2026-11-20.
