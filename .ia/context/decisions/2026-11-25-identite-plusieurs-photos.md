# `2026-11-25` — Identité : plusieurs photos, celles validées gardées

- **Type** : décision
- **Question posée** : « rendre le champ de la photo d'identification multiple et la modification depuis le compte ne supprime pas l'existant s'il est déjà validé ».
- **Ce qui est retenu** (précise la décision 2026-11-25-identification) :
  - **Le champ** accepte jusqu'à 5 photos, choisies ensemble dans la photothèque (« Ajouter des photos »).
  - **Les photos déjà envoyées** sont montrées dans le champ, par un lien signé valable 10 minutes : `GET /api/v1/auth/identity-documents/{uuid}`, sans signature refusée. Elles restent sur le disque privé.
  - **Pas encore identifié** : le compte peut retirer des photos et en ajouter.
  - **Déjà identifié** : un envoi ne fait qu'ajouter des photos. Celles sur lesquelles l'équipe a validé restent, même retirées du champ. La note le dit.
  - Ajouter des photos ne retire pas l'identification.
- **Tranché par** : humain ; session (5 photos, le lien signé, la note)
