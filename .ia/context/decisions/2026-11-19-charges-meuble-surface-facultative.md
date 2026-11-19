# `2026-11-19` — Pas de charges pour un meublé ; surface et pièces facultatives

- **Type** : décision
- **Question posée** : demande directe : « les charges ne s'affichent pas dans le formulaire pour un meublé », « la surface et nombre de pièces ne sont pas obligatoires ».
- **Réponse** : la demande.
- **Ce qui est retenu** :
  - logement : le champ « Charges mensuelles » ne s'affiche que si « Meublé » n'est pas coché (`visibleWhen('furnished', 'false')`) :
    - dans l'app et dans le back-office ;
    - un meublé enregistré n'a pas de charges ;
    - d'anciennes charges d'un meublé ne s'affichent plus sur la fiche ;
  - la surface et le nombre de pièces deviennent facultatifs ;
  - correctif générique : une condition d'affichage sur une case compare « true »/« false » (`Field::conditionValue()`, `isVisible` de l'app). Avant, une case ne remplissait jamais une condition.
- **Motif** : un meublé se loue charges comprises ; la demande de l'humain.
- **Portée** : `RentalDomain`, `Field`, `FieldComponent` (back-office), `OfferDetailResource`, `isVisible` de l'app.
- **Corollaires** : dans le back-office, une case (switch, checkbox) est `live()`, pour que les champs qui en dépendent apparaissent ou disparaissent aussitôt.
- **Tranché par** : humain
