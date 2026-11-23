Remplacée en partie par : 2026-11-23-caution-avance-onglet-prix.md
Remplace en partie : 2026-11-19-prix-dans-general.md (le prix quitte « Général ») ; 2026-11-19-onglets-localisation-informations.md (ordre des onglets).

# `2026-11-19` — Back-office d'une offre : onglet « Prix », « Localisation » avant « Accès »

- **Type** : décision
- **Question posée** : « mettre le tab localisation avant le tab accès et créer un tab prix pour les 2 champs prix ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - onglet « Prix » (« Price », « Preis ») : le prix (« Loyer ») puis les attributs qui l'accompagnent (`Domain::priceAttributes()`, les charges pour le logement) ; masqué pour un domaine sans prix ;
  - ordre : Général, Localisation, Prix, Accès, Caractéristiques, Règles, Informations, Photos (« mettre le tab prix après localisation »).
- **Motif** : la demande de l'humain.
- **Portée** : `OfferForm` du back-office, textes `offer::offer.tabs`.
- **Corollaires** : aucun changement de données, d'API ni de l'app.
- **Tranché par** : humain
