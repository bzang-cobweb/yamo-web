# `2026-11-20` — Contrat du chantier profil (téléphone, titre), sélecteur de langue et barre du haut, validé

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-20-profil-telephone-selecteur-langue.md`, avec trois questions : l'écran visé par la barre à retirer, l'affichage du téléphone, la validation.
- **Réponse** : « simple dropdown icon et centré au dessus du logo. Noter que les sélecteurs de langues ne s'affiche ne sont pas affichés si on a une seule langue ».
  - **Sélecteur de langue** : une liste déroulante simple avec une icône, centrée au-dessus du logo, sur l'écran d'accueil de l'app et sur la page web `/`. Il est masqué s'il n'y a qu'une langue.
  - **Questions restées sans réponse**, tranchées par les défauts du contrat :
    - le téléphone s'affiche au format international s'il existe, rien sinon ;
    - la barre du haut est retirée de l'onglet Accueil seulement.
- **Motif** : un choix de langue qui tient avec un nombre quelconque de langues, et un profil plus sobre.
- **Portée** : app mobile (auth, welcome), page d'accueil web, API (`phone_international`).
- **Corollaires** : ajouter une langue côté API ne demande aucun changement d'interface.
- **Tranché par** : humain, et défauts du contrat pour les deux points sans réponse
