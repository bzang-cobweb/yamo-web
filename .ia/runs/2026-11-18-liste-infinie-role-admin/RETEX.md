# RETEX — Rôle admin, listes par curseur, composant List

## Ce qui s'est produit

- **Une demande nommait un rôle qui n'existait pas** (« le rôle admin »). C'est l'inventaire de `RoleEnum` et de la base qui l'a révélé, avant d'écrire le contrat.
- **Une permission seule ne suffit pas pour atteindre l'écran.** Un rôle n'atteint une ressource Filament qu'avec l'accès au panneau et la consultation de la ressource parente. Le contrat ne l'a pas vu, et le point reste en suspens.
- **Scramble a perdu le type des items** quand leur construction est passée par une méthode abstraite. Il lit les propriétés typées, pas les tableaux décrits en PHPDoc. Le corriger a aussi précisé `openapi.json`, où les items valaient `{}`.
- **La pagination par curseur de Laravel relit `?cursor=` dans la requête** quand on ne lui passe pas de curseur. Pour garder la main, il faut un nom de curseur absent de la requête.
- **Dans les tests de hook React Query, `result.current` n'est pas à jour juste après `act`** : les notifications sont différées. Il faut `waitFor`.
- **Le QA a repéré une requête en double au premier affichage**, causée par un rechargement au focus. C'est corrigé.

## Règles candidates

- **Projet (`.ia/rules/`)** : une permission back-office donnée à un rôle se vérifie jusqu'à l'écran. Il faut l'accès au panneau et la ressource parente ; sinon, le dire dans le contrat.
- **Marketplace (bza-laravel:generate-openapi)** : une valeur sérialisée depuis un tableau en PHPDoc sort en `UnknownType`. La porter par des propriétés typées.

## Règle ré-implémentée, règle domiciliée

- **Domiciliée** : la pagination vit dans `Listing::page()`, une seule fois pour toutes les listes.
- **Domiciliée** : le chargement de la suite, une seule demande à la fois, vit dans `List` et sert toute feature.
- **Domiciliée** : les marges d'un écran du profil vivent dans `profileContentStyle`, partagé par le `ScrollView` et la `List`.
