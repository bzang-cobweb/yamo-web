Remplacée en partie par : 2026-11-25-adresse-sans-pays.md

# `2026-11-25` — La région de l'adresse : obligatoire, filtrée, et les carrousels de l'accueil par région

- **Type** : décision
- **Questions posées** :
  - « ajouter le champ region obligatoire dans l'adresse » ;
  - « ajouter la sélection (avec recherche) de la région après la ville dans le formulaire des offres » ;
  - « mettre à jour la base de données pour les offres existantes en mettant la region centre pour les adresses qui ont la ville yaoundé et littoral pour la ville douala » ;
  - « se baser sur les regions au lieu des villes sur le carousel d'accueil » ;
  - « ajouter le filtre region avant le filtre ville partout où il y a le filtre ville ».
- **Ce qui est retenu** :
  - **Colonne** : `addresses.region_id`, une région (table `regions`, gérée au back-office), supprimée : vide. Migration additive. La colonne accepte le vide pour les adresses déjà enregistrées qu'aucune règle ne rattache à une région ; c'est la saisie qui l'exige.
  - **Reprise** : migration de données sur les adresses du Cameroun sans région.
    - Yaoundé passe en « Centre », Douala en « Littoral », sans tenir compte de la casse ni des accents.
    - La région est retrouvée par son nom français dans le pays, créée si elle manque.
    - En local, les 15 adresses ont été rattachées.
  - **Formulaire d'une offre (app)** : « Région » juste après la ville.
    - C'est une liste avec recherche des régions actives du pays de l'offre, dans l'ordre du back-office.
    - Elle est obligatoire avec l'adresse ; une région d'un autre pays est refusée.
    - Une adresse déjà connue prend la région choisie : la région suit la ville, pas le compte.
  - **Filtre « Région »** : il passe avant le filtre « Ville » partout où celui-ci existe :
    - la recherche, « Mes logements », les favoris ;
    - « Mes réservations » et les demandes reçues.
    - Il ne propose que les régions où se trouve une offre de la liste.
  - **Accueil** : un carrousel par région, dans l'ordre du back-office, avec les offres de toutes ses villes. Cela remplace un carrousel par ville.
    - L'API sert `title` (le nom de la région) et `filter` (`{region: id}`) à la place de `city`.
    - « Voir tout » ouvre la recherche filtrée sur la région.
  - **Fabrique de test** : une adresse sans région prend celle de son pays qui porte le nom de sa ville.
- **Pas fait** : la région n'est pas encore demandée dans le carnet d'adresses du compte (`auth.address`), ni dans les formulaires d'adresse du back-office. Ces adresses restent sans région tant qu'on ne les y ajoute pas.
- **Remplace** : les carrousels par ville de 2026-11-23-accueil-carrousels-par-ville, et le « Voir tout » sur la ville de 2026-11-23-accueil-voir-tout.
- **Tranché par** : humain (la région obligatoire, sa place, la reprise, l'accueil, le filtre) ; session (colonne vide permise pour l'existant, le formulaire des offres seulement, l'ordre du back-office)
