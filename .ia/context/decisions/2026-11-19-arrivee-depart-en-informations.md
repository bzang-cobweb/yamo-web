Remplace en partie : 2026-11-19-contrat-regles-informations.md (les heures d'arrivée et de départ ne sont plus des règles).

# `2026-11-19` — Arrivée et départ : plus de champs, un bloc d'informations

- **Type** : décision
- **Question posée** : regrouper des champs de l'offre en sections définies dans Laravel (demande annulée), puis « enlever donc les champs check-in et check-out, je vais utiliser offer_informations pour cela ».
- **Réponse** : retirer les deux champs.
- **Ce qui est retenu** :
  - `check_in_from` et `check_out_until` disparaissent du domaine logement : étape « Règles » de l'app, section « Règles » de la fiche, onglet « Règles » du back-office ;
  - le propriétaire écrit ses heures dans un bloc d'informations (titre et description libres, traduisibles).
- **Motif** : la demande de l'humain.
- **Portée** : `RentalDomain`, textes `offer::rental.rules`, OpenAPI ; rien dans l'app (formulaire et fiche décrits par le serveur).
- **Corollaires** : une heure déjà enregistrée reste dans `offers.details` mais n'est plus ni servie ni affichée (aucune donnée effacée) ; l'annulation des sections de champs n'a laissé aucune trace.
- **Tranché par** : humain
