# `2026-11-19` — Titre « type - meublé - ville », langue en bas de l'accueil visiteur

- **Type** : décision
- **Question posée** : demande directe de l'humain.
- **Réponse** : « ListItem séparé le type et meublé par un tiret » ; « déplacer le sélecteur de langue sur la barre du bas après les boutons ».
- **Ce qui est retenu** :
  - logement : le titre d'un item de liste est « Studio - meublé - Douala ». Les trois parties sont séparées par « - », et une partie absente disparaît avec son tiret (« Studio - Douala »). « meublé » s'accorde au type (« Maison - meublée ») ;
  - mobile : la barre du haut ne porte plus que le logo et le drapeau du pays. La langue passe sous « Se connecter » / « Créer un compte », sur l'accueil d'un visiteur. Connecté, il n'y a toujours pas de sélecteur : la langue se règle dans les préférences du compte.
- **Motif** : la demande de l'humain.
- **Portée** : `RentalDomain::itemTitle` ; `AppTopBar` et `GuestHomeScreen` (mobile).
- **Corollaires** : remplace la forme « Studio meublé - Douala » (décision du titre à tiret du 2026-11-19) et la langue dans la barre du haut (2026-11-18-liste-photo-barre-connecte), sans les annuler sur le reste.
- **Tranché par** : humain
