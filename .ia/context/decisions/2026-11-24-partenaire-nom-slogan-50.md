# `2026-11-24` — Le nom et le slogan d'un partenaire : 50 caractères au plus

- **Type** : décision
- **Question posée** : « limiter le nom et le slogan du partenaire à 50 caractères max, mais garder 255 en base de données ».
- **Ce qui est retenu** :
  - `Partner::NAME_MAX_LENGTH` et `Partner::SLOGAN_MAX_LENGTH` valent 50. Ils servent au formulaire de l'app, qui valide aussi la requête de l'API, et au formulaire du back-office. Avant, la limite était de 255 pour le nom et de 120 pour le slogan.
  - La base ne change pas. Le nom et le slogan sont des traductions en JSON, sans longueur de colonne : rien n'y est réduit.
  - Aucun partenaire existant ne dépasse 50 caractères (vérifié sur la base locale).
- **Tranché par** : humain
