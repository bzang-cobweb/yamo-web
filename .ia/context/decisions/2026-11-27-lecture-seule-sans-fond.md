# `2026-11-27` — Un champ en lecture seule : sans fond ni marges intérieures

- **Type** : décision
- **Question posée** : « quand un champ texte ou select est en readonly faire le background transparent », puis « enlever aussi les padding ».
- **Ce qui est retenu** : un champ en lecture seule (`readonly`) n'a plus le fond gris ni les marges intérieures d'une zone de saisie : sa valeur se lit alignée sur son libellé (la date d'une demande dans « Annuler »). Vaut pour toutes les zones de saisie (texte, zone de texte, nombre, liste, date, téléphone…), dans le style commun `useInputStyle`.
- **Tranché par** : humain
