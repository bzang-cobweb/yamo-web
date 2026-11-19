# Notification d'une demande : titre, statut en pastille, lien vers l'annonce

- **Type** : décision de conception
- **Date** : 2026-11-19
- **Décidé par** : l'humain (« mettre un lien vers le détail de l'offre depuis la notification » ; « titre : demande de logement avec le statut en dessous comme dans mes réservations »)

## Décision

Une notification de demande (`BookingNotification`) porte, dans l'onglet « Notifications » :

- **Titre** : la demande telle que le domaine la nomme, avec `Domain::requestLabel()`. Pour la location, c'est « Demande de logement » (en « Home request », de « Unterkunftsanfrage »). Le titre propre à chaque étape (« Nouvelle demande », « Demande acceptée »…) est retiré ; l'objet de l'e-mail garde les siens.
- **Statut** : en pastille sous le titre, avec les mêmes libellés et couleurs que dans Mes réservations (`BookingStatusEnum::label()` et `tone()`). Il est **figé à l'envoi** : la notification garde le statut de la demande au moment où elle est créée, pas celui du moment où la file la traite.
- **Lien « Voir l'annonce »** : sous le texte, il ouvre la fiche de l'offre (`/offers/{id}`) et marque la notification lue. Toucher le reste de la notification ouvre toujours la liste où retrouver la demande.

L'API des notifications expose `tag` (`{label, tone}` ou `null`) et `links` (une liste de `{label, link}`). Ces champs sont génériques, pour toute notification future.

Dans l'app, la pastille est un composant partagé, `StatusTag`, utilisé par les listes du profil et par les notifications.

## Motif

Une notification se lit comme une carte de Mes réservations : de quoi il s'agit, où en est la demande, et un accès direct à l'annonce.

## Portée

Les notifications créées à partir de maintenant. Les notifications déjà enregistrées gardent leur ancien titre, sans pastille ni lien, puisque leur contenu est figé. Elles disparaissent avec la purge à 90 jours.

## Corollaires

- Un nouveau domaine nomme ses demandes en surchargeant `requestLabel()` ; sinon, le titre est « Demande ».
- Une nouvelle notification peut ajouter `tag` et `links` à ses données sans toucher à l'app.
