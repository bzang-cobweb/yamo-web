# `2026-11-25` — Offre : catégorie après le propriétaire, caractéristique « Caméras »

- **Type** : décision
- **Question posée** : « déplacer le champ catégorie après propriétaire / déplacer les champs type et type de location après le champ catégorie / dans caractéristiques ajouter un champ boolean qui indique s'il y a des caméras ».
- **Ce qui est retenu** :
  - **Back-office, onglet général d'une offre** : le propriétaire, la catégorie, le type, le type de location, puis le titre et la description. Remplace l'ordre « type et meublé avant la catégorie » de la décision 2026-11-19-backoffice-loyer-type-annonce.
  - **« Caméras de surveillance »** : attribut `cameras` d'un logement, Oui / Non, comme les règles Oui / Non.
    - Il est dans les caractéristiques : l'étape de l'app (après les salles de bain), l'onglet du back-office et les lignes de la fiche.
    - Il est **facultatif** : les offres existantes n'ont pas à y répondre, et une offre sans réponse ne montre pas la ligne.
- **Tranché par** : humain ; session (facultatif, le libellé « Caméras de surveillance », la place après les salles de bain)
