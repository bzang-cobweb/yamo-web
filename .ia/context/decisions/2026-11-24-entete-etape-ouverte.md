# `2026-11-24` — La page d'une notification s'ouvre sur l'étape de sa carte

Remplace : 2026-11-20-entete-premiere-etape.md

- **Type** : décision de conception
- **Question posée** : « NotificationResource : à quoi sert $head ? est-ce que c'est encore utilisé dans l'app ? » ; réponse : « oui, supprime $head ».
- **Ce qui est retenu** :
  - `NotificationResource` ne lit plus la première étape du fil (`$head`). Tout vient de la notification servie, rédigée pour le lecteur (`dataFor()`) : titre, texte, résumé, pastille, qui agit, passages liés, message, liens.
  - La page d'une notification s'ouvre donc sur l'étape de sa carte. Le suivi (`history`) montre toutes les étapes.
- **Motif** :
  - Pour une réservation, l'en-tête tiré de la première étape ne s'affichait plus : le titre est donné par `BookingThreadDetailsService`, et la page montre les lignes de la demande, pas le texte.
  - Pour un partenaire, la carte montrait la dernière étape et la page la première : une incohérence.
- **Corollaires** :
  - Chez le propriétaire, la page d'une demande à laquelle il a répondu a un résumé vide et pas de « qui agit » : c'est sa propre réponse. Ni l'un ni l'autre n'est affiché sur la page d'une demande.
  - Chez l'auteur d'un partenaire, la page montre sa dernière action.
- **Tranché par** : humain
