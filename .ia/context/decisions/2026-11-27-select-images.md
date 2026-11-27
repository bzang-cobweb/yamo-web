# `2026-11-27` — Liste déroulante : un panneau à la hauteur de son contenu, des images, une flèche

- **Type** : décision
- **Question posée** : « mes réservations filtres faire plus joli le select du logement », précisé : « c'est le même select mais avec une image, comme quand on change de pays », « utiliser le panneau avec sa hauteur adaptée au contenu », « garder l'icône sur le select ».
- **Ce qui est retenu** :
  - La fenêtre de choix commune (`OptionsModal` : toutes les listes déroulantes, le pays d'un téléphone, le pays courant, la langue, la région de la recherche) n'est plus une fenêtre centrée semi-transparente : c'est un panneau qui monte du bas (`BottomSheet`), à la hauteur de son contenu, titré du libellé du champ, avec sa recherche ; une longue liste défile jusqu'à la taille maximale. Le fonctionnement ne change pas (recherche, « Tous » / « Aucun », coche, choix qui ferme).
  - Une option peut avoir une image (`image`, servie par le paquet form avec `['label' => …, 'image' => …]`), affichée devant son libellé, comme le drapeau d'un pays : le filtre « Logement » de « Mes réservations » sert la photo de chaque logement.
  - Fermée, la liste déroulante a une flèche à droite (pas en lecture seule).
- **Portée** : web : `packages/form` (`Field` : `image` des options), `FiltersBookings::bookedOfferOptions()` ; mobile : `OptionsModal`, `SelectField`, type `FieldOption.image` ; les simulations de `react-native-safe-area-context` de cinq fichiers de tests complétées (`SafeAreaProvider`).
- **Tranché par** : humain
