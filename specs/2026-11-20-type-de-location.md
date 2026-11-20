# Contrat — « Type de location » remplace les catégories d'offre

Validation : validé le 2026-11-20 (« Approved »)

Demande : « on va changer la structure, faire un enum avec les 3 catégories et proposes-moi un nom autre que catégorie », puis « Type de location ».

## Ce que j'ai compris

Les catégories d'offre (une table traduite, en arbre, triable, gérée au back-office) deviennent une liste fixe dans le code : le **type de location** d'une offre.

| Valeur | Français | English | Deutsch |
|---|---|---|---|
| `long_term` | Location longue durée | Long-term rental | Langzeitmiete |
| `furnished` | Location meublée | Furnished rental | Möblierte Vermietung |
| `shared` | Colocation | Flat share | Wohngemeinschaft |

### Une seule source de vérité

Le champ « Meublé » de chaque offre fait aujourd'hui doublon avec « Location meublée ». **Il disparaît.** Ce qu'il décidait se lit sur le type de location :

| | Location meublée | Location longue durée | Colocation |
|---|---|---|---|
| Ce qu'on demande | un séjour (dates, voyageurs) | une visite | une visite |
| Prix | par jour | par mois | par mois |
| Charges mensuelles | non | oui | oui |
| « meublé » dans le titre d'une liste | oui | non | non |

### Où ça se voit

- **Formulaire d'une offre** (app et back-office) : un choix « Type de location », obligatoire, à la place de « Meublé » et de « Catégorie ».
- **Fiche d'une offre** : « Type de location » dans les caractéristiques.
- **Filtres de l'accueil, de Mes logements et de Mes favoris** : « Type de location » (Tous, puis les 3 valeurs) remplace « Meublé ».
- **Filtres des notifications** : « Catégorie » devient « Type de location », avec les 3 valeurs.
- **Back-office** : l'écran « Catégories » disparaît, ainsi que ses droits ; la table des offres montre le type de location.

### Données existantes

Une migration :
- donne à chaque offre son type de location : `furnished` si l'offre était meublée, sinon d'après sa catégorie (« Colocation » donne `shared`), et `long_term` à défaut ;
- puis supprime la colonne `category_id`, la table des catégories et la clé « meublé » des détails.

Les demandes et notifications existantes ne changent pas.

## Découpage

- **Laravel** :
  - l'enum `RentalTypeEnum` (Offer), traduit en fr, en et de ;
  - la colonne `offers.rental_type`, et la migration des données ;
  - `RentalDomain` (type de demande, unité du prix, charges, titre de liste, filtres, étapes du formulaire) ;
  - les formulaires de l'app et du back-office, la fiche, les filtres (accueil, Mes logements, Mes favoris, notifications) ;
  - retrait de `OfferCategory` (modèle, ressource Filament, policy, factory, seeders, `project:init`) ;
  - les seeders de démonstration, les tests, l'OpenAPI.
- **App** : rien de spécifique attendu, puisque formulaires, filtres et fiche sont décrits par l'API. À vérifier au simulateur.
- **Aucune dépendance ajoutée.**

## Critères de succès (état observable)

- Une offre a un type de location parmi les 3 valeurs. Le choisir est obligatoire à la création comme à la modification, dans l'app et au back-office.
- Une location meublée se réserve par dates avec un prix par jour ; une location longue durée et une colocation se visitent, avec un prix et des charges par mois.
- Les filtres de l'accueil, de Mes logements, de Mes favoris et des notifications proposent « Type de location » et restreignent correctement.
- Plus aucune trace des catégories : ni table, ni écran au back-office, ni champ « Meublé ».
- Les offres existantes ont gardé leur sens : un meublé reste meublé, la colocation reste colocation.
- Suites web et app vertes, OpenAPI à jour, simulateur vu, QA indépendante.

## Points tranchés par défaut (à confirmer en validant)

1. **Le champ « Meublé » disparaît** : le type de location décide (tableau ci-dessus).
2. **Une colocation se visite**, avec un prix et des charges par mois, comme une location longue durée.
3. **Les catégories disparaissent entièrement**, sans sous-catégories, y compris l'écran du back-office.

## Hors périmètre

- D'autres types de location ; un ajout passerait par le code.
- Un parcours de réservation propre à la colocation (une chambre, une durée minimale).

## Révision du 2026-11-20 (décidée par l'humain)

« yes keep both but current categories data go to type de location field » :

- **Les catégories restent** : la table, l'arbre, l'écran du back-office et le champ « Catégorie » de l'offre. Elles redeviennent libres, pour de vraies catégories.
- **Les trois catégories actuelles** (Location longue durée, Location meublée, Colocation) **passent dans le type de location** : la migration les convertit, retire leur lien aux offres et les supprime. Le domaine « location » ne les recrée plus (`Domain::categories()` vide), et les seeders non plus.
- **Le reste du contrat ne change pas** : l'enum, la disparition du champ « Meublé », séjour ou visite selon le type, et les filtres.
- **Le filtre des notifications** « Catégorie » devient « Type de location ».
