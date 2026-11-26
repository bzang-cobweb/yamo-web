# `2026-11-26` — Depuis la fiche d'une offre, « Envoyer un message » s'ouvre dans un panneau à sa hauteur

Complète : 2026-11-26-messages-editeur.md, 2026-11-26-formulaires-en-panneau.md

- **Type** : décision
- **Question posée** : « détail page offre ouvrir également le formulaire envoyer un message sur le pannel mais avec une hauteur adaptée au contenu ».
- **Ce qui est retenu** : le bouton d'une section de la fiche (`SectionAction`, « Envoyer un message » dans « Éditeur ») ouvre un formulaire du profil dans un `BottomSheet` à la hauteur du formulaire (`fitContent`) au lieu d'un écran ; envoyé, le panneau se ferme et le message du serveur s'affiche. Un visiteur passe toujours d'abord par la connexion ; un autre lien ouvre toujours son écran.
- **Corollaires** : `ProfileFormSheet` prend `fitContent` ; `FixedFooterLayout` prend `fit` (hauteur du contenu, défilement seulement s'il dépasse, barre du bouton toujours visible).
- **Tranché par** : humain
