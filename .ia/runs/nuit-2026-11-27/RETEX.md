# Retour d'expérience — nuit du 2026-11-27

- **Ce qui s'est produit deux fois → règle candidate** : `git add --pathspec-from-file` échoue sur un fichier déjà
  supprimé par `git rm` (deux fois, dont un commit partiel à compléter). Règle : filtrer les chemins absents avant
  `git add`, ou ne lister que les modifiés et laisser les suppressions indexées.
- **Jetons de données personnelles dans les fichiers** : un numéro de test au format espacé ne ressemblait pas à la
  valeur E.164 attendue ; le débogage a coûté plusieurs essais. Écrire les valeurs de test sous la forme normalisée
  que le code enregistre.
- **Parallélisme** : un sous-agent sur un périmètre disjoint (module Offer) pendant le travail sur Auth a bien marché ;
  les parties du lot B, qui partagent menu, routes et textes, ont été faites une par une.
- **Règle domiciliée** : le masquage d'un e-mail ou d'un numéro a trouvé un seul domicile (classe support d'Auth) au
  lieu de rester dans `VerificationCodeService`.
