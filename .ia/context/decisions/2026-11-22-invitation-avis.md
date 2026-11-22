# Invitation à donner son avis, le lendemain de la fin

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« faire les étapes 3 et 4 »)
- **Complète** : 2026-11-22-avis-apres-demande-terminee.md

## Décision

Chaque jour à 10:00, la commande `booking:invite-reviews` invite à donner son avis chaque membre dont la demande est terminée (acceptée, dernier jour passé) :

- une seule fois par demande (`bookings.review_invited_at`) ;
- pas s'il a déjà donné son avis sur ce logement, ni si le logement n'est plus publié ;
- seulement pour une demande terminée depuis 7 jours au plus : les anciennes ne sont pas rattrapées.

La notification `BookingReviewInvitation` arrive dans l'onglet, et par e-mail et SMS selon les canaux du membre. Exemple : « Votre séjour à « … » (…) est terminé. Laissez un avis. », ou « Votre visite de « … » (…) est terminée. … ». Son bouton « Laisser un avis » ouvre le formulaire d'avis. Elle est hors du fil de la demande, sans `booking_id`, pour ne pas changer la carte de la demande.

## Risque assumé

La tâche a besoin du planificateur Laravel : en production, `* * * * * php artisan schedule:run` ; en local, ddev ne le fait pas tourner (pas de démon `schedule:work`). C'est la même chose pour `notification:prune` et `model:prune`.
