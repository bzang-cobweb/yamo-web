# Carte d'une notification : qui agit, texte, message, statut

- **Type** : décision de conception
- **Date** : 2026-11-19
- **Décidé par** : l'humain (« card d'une notification : 1. la photo de l'utilisateur, son nom et en dessous la date, sur la même ligne, bordure verte s'il est en ligne ; 2. demande un séjour… ; 3. message envoyé ; 4. statut »)

## Décision

Dans la liste, une carte de notification montre, dans cet ordre :

1. **Qui agit** : sa photo (ou ses initiales), cerclée de vert s'il est en ligne, et à côté son nom, avec la date dessous. Son nom ouvre sa page.
   - C'est le membre pour le propriétaire (une demande, une annulation) ;
   - c'est le propriétaire pour le membre (une réponse).
2. **Le texte, sans son nom** (« demande un séjour du … pour « Studio photos » »). Le nom de l'offre reste un lien.
3. **Le message envoyé**, en citation.
4. **Le statut**, en pastille.

Le titre (« Demande de logement ») ne figure plus sur la carte ; la page de la notification le garde, avec le texte entier.

## Mise en œuvre

- La notification garde `actor_id` et `summary` (le texte sans le nom).
- L'API lit qui agit **à l'affichage** : sa photo, son nom et son état en ligne (vu dans les 5 dernières minutes) sont toujours à jour, et non figés à l'envoi.
- Une page de notifications charge tous ses acteurs en une requête.

## Portée

Les notifications créées à partir de maintenant. Une ancienne notification, sans `actor_id`, garde son titre en tête et son texte entier.
