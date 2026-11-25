Remplacée par : 2026-11-25-oui-non-trois-positions.md

Remplace en partie : 2026-11-21-choix-facultatif-effacable.md (les règles Oui / Non du logement ne sont plus facultatives).

# `2026-11-23` — Règles Oui / Non du logement obligatoires, en interrupteurs

- **Type** : décision
- **Question posée** : « rendre les champs animaux acceptés, fumeurs, fêtes et évènements obligatoires. en faire des champs switch sur laravel filament et enlever l'option tous sur le formulaire mobile », puis « faire aussi des champs switch Oui/Non sur le formulaire mobile d'une offre ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - `pets`, `smoking`, `parties` sont obligatoires ; les valeurs restent « yes » / « no » ;
  - règle générale : un choix obligatoire dont les options sont exactement « yes », « no » s'affiche en interrupteur, au back-office (`FieldComponent`) comme dans l'app (`isYesNoSwitch`) ; allumé = « yes », éteint = « no » ;
  - un logement sans valeur part sur « Non » (interrupteur éteint), au back-office comme dans l'app ;
  - plus d'option « Aucun » dans le formulaire de l'offre pour ces champs.
- **Motif** : la demande de l'humain.
- **Portée** : `RentalDomain::attributes()`, `FieldComponent`, `FormField` et `values.ts` de l'app.
- **Corollaires** :
  - le filtre de recherche garde son « Tous / Oui / Non » : ne pas filtrer reste possible ;
  - aucune migration de données : un logement sans valeur l'obtient à son prochain enregistrement.
- **Tranché par** : humain
