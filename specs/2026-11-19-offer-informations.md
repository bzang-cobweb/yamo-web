# Contrat — Informations d'une offre : une table, des blocs titre + description

Validation : en attente

Demande : « annuler le champ autres informations et créer une table offer_informations qui prend un titre et une description, champs traduisibles. Possibilité de rajouter dynamiquement plusieurs dans la step des règles et informations. Chaque record constitue une section de l'accordéon dans la page de détail après les reviews ».

## Ce que j'ai compris

- **Fin du champ « Autres informations »** (`other_information`, attribut du domaine).
- **Table `offer_informations`** : `offer_id` (supprimé avec l'offre), `title` et `description`
  traduisibles (comme le titre et la description de l'offre), `position` (ordre), horodatage.
  Titre : 100 caractères au plus, obligatoire. Description : 2000 au plus, obligatoire.
  10 blocs au plus par offre.
- **Formulaire de l'app**, étape « Règles et informations » : après les règles, une liste
  « Informations » où le propriétaire **ajoute** un bloc (titre + description), en **retire**
  un, et les voit dans l'ordre de saisie. Il écrit dans sa langue, comme pour le titre de
  l'offre ; les autres langues d'un bloc existant sont gardées.
- C'est un **nouveau type de champ générique** du module Form, `repeater` : une liste de
  groupes de sous-champs décrits par Laravel (ici `title` texte et `description` texte long),
  avec un nombre maximal. L'app l'affiche sans rien savoir des offres.
- **Back-office** : dans l'onglet « Règles », la même liste (ajout, retrait, réordonner,
  un onglet par langue).
- **Fiche** : chaque bloc est une section repliable, **après « Avis »**, titrée par son
  titre, contenant sa description (retours à la ligne gardés). La configuration
  `offer.sections` gagne `informations` (fermées par défaut) : sa place dans la liste
  place tous les blocs, et son état vaut pour chacun.
- **Données existantes** : un texte « Autres informations » déjà saisi devient un bloc
  titré « Autres informations » (« Other information »), dans les mêmes langues ; puis
  l'attribut est retiré.

## Découpage

- Web :
  - migration (table, recopie des textes existants) ;
  - modèle `OfferInformation` et fabrique ;
  - `Field::repeater()` (règles de validation des sous-champs, maximum, valeur servie) ;
  - `OfferForm` (app), `OfferService` (enregistrer les blocs : créer, modifier, retirer, ordre) ;
  - `RentalDomain` (étape ; fin de `other_information` et de `informationAttribute()`) ;
  - `OfferDetailResource` (`informations`, sections) ; formulaire Filament ;
  - traductions, tests, OpenAPI.
- App :
  - type `repeater` (`RepeaterField` : ajouter, retirer, sous-champs rendus par le champ générique) ;
  - sections d'informations sur la fiche ;
  - tests.

## Critères de succès (état observable)

- L'étape « Règles et informations » permet d'ajouter, modifier et retirer des blocs
  titre + description ; ils sont enregistrés dans `offer_informations`, dans l'ordre.
- La fiche montre un bloc par section repliable, après « Avis », dans la langue demandée.
- Le back-office édite les mêmes blocs.
- Plus de champ « Autres informations » ; un texte déjà saisi est devenu un bloc.
- Le type `repeater` est générique (aucune référence aux offres dans l'app).
- Suites serveur et app vertes, OpenAPI régénéré, simulateur vu, QA indépendante.

## Points à trancher (défaut appliqué si validé tel quel)

1. Titre et description obligatoires dans un bloc ; 10 blocs au plus (défaut).
2. Ordre = ordre de saisie ; dans l'app, ajout et retrait seulement (réordonner : back-office) (défaut).
3. Les sections d'informations sont fermées à l'arrivée (défaut, réglable dans `offer.sections`).
4. Recopier les textes « Autres informations » existants en un bloc (défaut).
