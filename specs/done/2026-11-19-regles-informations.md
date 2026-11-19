# Contrat — Règles et informations pratiques d'un logement

Validation : en attente

Demande : « add informations textarea rte (must be option in textarea field type). this because i want the owner to add informations like check in, checkout, limit age, required documents, ... is there a better way to do this ? where to put this ? new informations step or in existing step ? » — réponse à ma proposition : « Structured + free text ».

## Ce que j'ai compris

Le propriétaire décrit les règles et les informations pratiques de son logement par des
**champs structurés**, plus un **texte libre** facultatif. Pas d'éditeur de texte riche :
aucune dépendance ajoutée.

Nouveaux attributs du domaine « logement », tous facultatifs :

| Champ | Type | Détail |
|---|---|---|
| Arrivée à partir de (`check_in_from`) | liste d'heures | 06:00 à 23:00, par demi-heure ; **meublé seulement** |
| Départ avant (`check_out_until`) | liste d'heures | idem ; **meublé seulement** |
| Âge minimum (`min_age`) | nombre | 16 à 99 |
| Documents demandés (`documents`) | choix multiples | pièce d'identité, passeport, titre de séjour, justificatif de revenus, garant, dépôt de garantie |
| Animaux (`pets`) | Oui / Non | absent si non renseigné |
| Fumeurs (`smoking`) | Oui / Non | idem |
| Fêtes et événements (`parties`) | Oui / Non | idem |
| Autres informations (`other_information`) | texte, plusieurs lignes | traduisible, 2000 caractères, retours à la ligne gardés |

- **Formulaire de l'app** : une nouvelle étape « Règles et informations », après « Prix et
  disponibilité » et avant « Adresse ». Les étapes deviennent : Présentation,
  Caractéristiques, Prix et disponibilité, Règles et informations, Adresse, Photos.
- **Back-office** : un onglet « Règles » sur la fiche de l'offre, avec les mêmes champs.
- **Fiche de l'offre** : une section repliable « Règles du logement » après
  « Caractéristiques » : les lignes renseignées (libellé, valeur, comme les
  caractéristiques), puis le texte libre. Sans rien de renseigné, pas de section. Ces
  champs ne figurent plus dans « Caractéristiques ».
- Le domaine déclare quels attributs sont des règles (`Domain::ruleAttributes()`) et quel
  attribut est le texte libre ; la fiche sert `rules` (lignes) et `information` (texte).

## Découpage

- Web : attributs et étape dans `RentalDomain`, `Domain::ruleAttributes()` /
  `informationAttribute()`, `OfferDetailResource` (`rules`, `information`, faits sans les
  règles), onglet Filament, traductions, tests, OpenAPI.
- App : type `OfferDetail`, section « Règles du logement » dans `OfferDetailScreen`, test.

## Critères de succès (état observable)

- L'étape « Règles et informations » apparaît en 4e position ; heures d'arrivée et de
  départ seulement pour un meublé ; tout est facultatif et enregistré.
- La fiche montre « Règles du logement » avec les seules valeurs renseignées et le texte
  libre (retours à la ligne gardés), dans la langue de la saisie ; rien si vide.
- Le back-office édite les mêmes champs.
- Une offre existante, sans ces champs, s'affiche comme avant.
- Suites serveur et app vertes, OpenAPI régénéré, simulateur vu, QA indépendante.

## Points à trancher (défaut appliqué si validé tel quel)

1. Heures d'arrivée et de départ réservées au meublé (défaut : oui).
2. Oui / Non plutôt qu'un interrupteur pour animaux, fumeurs, fêtes, pour distinguer
   « non renseigné » de « non » (défaut : oui).
3. Pas de filtre sur ces champs dans l'accueil pour l'instant (défaut).

---

## Ce que j'ai décidé seul

- Les règles sont des attributs du domaine comme les autres (dans `details`) ; le domaine dit lesquels sont des règles (`ruleAttributes()`) et lequel est le texte libre (`informationAttribute()`). La fiche, le back-office et le formulaire s'en servent sans liste en dur ailleurs.
- Âge minimum affiché « 21 ans » (suffixe) ; heures en liste fixe de demi-heures, sans nouveau type de champ.
- Le texte libre n'a pas de titre dans la section : il suit les lignes, séparé par un espace.
- Une ligne « libellé — valeur » commune aux caractéristiques et aux règles (`FactRow`) dans l'app.
- Vérification au simulateur : règles ajoutées le temps de la capture à l'offre de démonstration n° 11, puis remises à l'identique (vérifié).

## Ce qui a été fait

- Web 859197d : attributs, étape, `Domain::ruleAttributes()` / `informationAttribute()`, fiche (`rules`, `information`, faits sans les règles), onglet « Règles » du back-office, textes fr/en, tests (formulaire, enregistrement et validation, fiche, back-office), OpenAPI.
- Mobile a30866b : section repliable « Règles du logement », test.
- Suites : serveur 508 verts, app 236 verts, tsc et lint propres. Simulateur : section vue sur l'offre 11.

## Ce que je n'ai pas vérifié

- La nouvelle étape du formulaire dans le simulateur (le compte connecté n'est pas propriétaire d'une offre de démonstration) ; couverte par les tests d'API.
- L'onglet « Règles » dans un vrai navigateur (test Livewire seulement).

## Ce qui reste en suspens

- Point 1 de la même demande (« rename avis by reviews ») : sans réponse sur ce qu'il faut renommer ; rien n'a été changé.
- Le diff d'`openapi.json` reflète aussi la base locale à un seul pays ouvert (énumérations de pays réduites).
