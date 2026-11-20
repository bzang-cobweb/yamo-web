# Le texte d'une demande commence par « Séjour » ou « Visite »

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-texte-demande.md

## Décision

Le texte d'une demande sans le nom de qui la fait (`offer::booking.notify.requested.summary*`)
ne commence plus par « demande un séjour / une visite » mais par le type de la demande :
« Séjour du 5 déc. 2026 au 7 déc. 2026 (2 jours) pour « Studio », 20 000 FCFA / jour
(1 personne). », « Visite le 28 nov. 2026 pour « Maison », 400 000 FCFA / mois. »
(en « Stay from … », « Visit on … » ; de « Aufenthalt vom … », « Besichtigung am … »).

Le remplacement `:kind` (`offer::booking.types.*`) est ajouté à `BookingText::replacements()`.

Le même texte sert partout : la page d'une notification, les cartes de la liste, le
calendrier d'une offre. Le texte complet (`body`, e-mail, SMS) garde « Marie Curie demande
un séjour … ».

## Motif

Demande de l'humain du 2026-11-20 : « le summary doit commencer par Séjour du ou Visite
du, enlever le début ».

## Portée

Les textes des demandes envoyées. Les étapes suivantes (« a accepté votre demande … »)
ne changent pas.

## Corollaires

- Les notifications déjà enregistrées gardent leur texte figé. Dans la base locale, il a
  été réécrit le 2026-11-20 ; une base qui en aurait d'autres devra être réécrite de même.
