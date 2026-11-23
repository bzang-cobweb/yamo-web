# Page d'une demande terminée : « Inviter à donner un avis »

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« si une demande est acceptée et que les dates sont passées, dans la page de détail mettre un bouton pour inviter le membre à mettre un avis au même endroit que le bouton traiter la demande »)
- **Complète** : 2026-11-22-traiter-si-en-attente.md, 2026-11-22-invitation-avis.md

## Décision

Sur la page d'une demande **terminée** (acceptée, dernier jour passé) dont le membre n'a pas encore donné d'avis sur le logement, le propriétaire a, dans la barre du bas, à la place de « Traiter la demande », le bouton « Inviter à donner un avis » :
- le bouton ouvre un formulaire sans champ : le membre et les dates sous le titre, puis « Envoyer l'invitation » ;
- l'envoi part à `POST /api/v1/bookings/{booking}/invite-review` et envoie au membre la même invitation que la tâche du matin (`BookingReviewInvitation`), dans l'onglet, par e-mail et par SMS selon ses canaux. Il note `review_invited_at`. Puis : « Invitation envoyée. » ;
- on peut renvoyer l'invitation tant que le membre n'a pas donné son avis (10 envois par minute au plus).

Dès que le membre a donné son avis, le bouton disparaît, et l'envoi est refusé (403).

## Portée

Route `invite-review`, `BookingPolicy::inviteReview`, `Booking::canInviteReview`, `BookingController::inviteReview`, `BookingInviteReviewForm` (nouveau), `AddBookingToNotificationThread`, et les textes `invite_review.*` (fr, en, de). Rien ne change côté mobile.
