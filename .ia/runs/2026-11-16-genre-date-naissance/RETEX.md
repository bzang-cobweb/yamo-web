# RETEX — chantier 2026-11-16-genre-date-naissance

## Ce qui s'est produit deux fois → règles candidates

1. **L'essai réel a encore trouvé un défaut que les tests ne voyaient pas**, pour le
   quatrième chantier de suite : un formulaire rouvert affichait des valeurs périmées
   (cache React Query). Le défaut datait du chantier précédent, où l'enregistrement
   réussi n'avait pas pu être joué. Règle confirmée : **jouer le chemin de succès
   complet, avec réouverture, sur un compte de test**. Quand l'app est connectée au
   compte réel de l'humain, le prévoir au contrat, comme ici.
2. **Un outil qui type le code refuse ce qu'il ne sait pas typer** : Scramble avec
   `birth_date?->format()`, comme `plainTextToken` au premier chantier. Règle candidate :
   dans une `JsonResource`, une valeur calculée passe par une méthode au type de retour
   déclaré.
3. **Des fichiers de langue publiés par un paquet** (laravel-lang) portent maintenant
   des ajouts du projet. Règle candidate : à chaque mise à jour des traductions, relire
   le diff de `lang/*/validation.php`.

## Autres leçons

- **Hermes formate bien les dates avec `Intl`** (« 12 novembre 2026 »). Le calendrier
  `display="inline"` d'iOS suit la langue passée en `locale`.
- **Un champ vide part en `''`** depuis l'app. Laravel le convertit en `null` grâce au
  middleware `ConvertEmptyStringsToNull`, ce qui permet d'effacer une date facultative.

## Règles : ré-implémentée ou domiciliée ?

- **Domiciliées** :
  - initiales et nom complet dans le modèle `User`, l'app ne les recalcule plus ;
  - borne basse de la date dans `PersonalInfoForm`, partagée avec Filament ;
  - conversions de dates dans `features/form/utils/dates.ts`.
- **À surveiller** : la gestion des erreurs d'API reste dupliquée (3 copies),
  signalée à chaque chantier.
