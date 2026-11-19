Remplace en partie : 2026-11-19-contrat-regles-informations.md (le texte libre « Autres informations » devient des blocs titre + description).

# `2026-11-19` — Contrat « informations d'une offre : table offer_informations », validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-19-offer-informations.md` (demande : « annuler le champ autres informations et créer une table offer_informations qui prend un titre et une description champs traduisibles… chaque record constitue une section de l'accordéon dans la page de détail après les reviews »), avec quatre défauts.
- **Réponse** : « Approved » (défauts retenus).
- **Ce qui est retenu** :
  - table `offer_informations` : `offer_id`, `title` et `description` traduisibles, `position` ; titre (100) et description (2000) obligatoires ; 10 blocs au plus ;
  - l'attribut `other_information` est retiré ; un texte déjà saisi devient un bloc « Autres informations » ;
  - nouveau type de champ générique `repeater` (module Form et app) : liste de groupes de sous-champs décrits par Laravel, avec un maximum ; l'app ajoute et retire, l'ordre est celui de la saisie ;
  - étape « Règles et informations » : la liste « Informations » après les règles ; back-office : la même liste dans l'onglet « Règles » (réordonnable) ;
  - fiche : un bloc par section repliable, après « Avis » ; `offer.sections` gagne `informations` (fermées par défaut).
- **Motif** : la demande de l'humain ; un type de champ générique pour ne rien coder des offres dans l'app.
- **Portée** : module Form (`Field::repeater`), module Offer (table, modèle, service, formulaires, fiche, configuration) ; app (champ `repeater`, fiche).
- **Corollaires** : `Domain::informationAttribute()` disparaît.
- **Tranché par** : humain
