# Retour d'expérience — nuit du 2026-11-24

- Une demande qui nomme un écran inaccessible à une partie des utilisateurs (« Mes partenaires » pour les visiteurs)
  se lit selon l'intention (« pour tous », « pas connecté → login ») : le bouton va aussi là où ils sont.
- Deux remplacements par script ont abîmé un fichier (un commentaire coupé, des modifications écrasées par un fichier
  relu trop tôt) : `php -l` et `tsc` les ont attrapés tout de suite. Règle candidate : relire le fichier dans le même
  script que chaque remplacement, et valider la syntaxe après chaque lot.
- Règle déjà domiciliée et réutilisée : les filtres de notifications par module (`NotificationFilter`) ; la
  validation des images (`ConfiguredImageService`) n'a pas été touchée.
- Un défaut préexistant (pint) a bloqué un critère « tout est propre » : le corriger à part, dans son propre commit.
