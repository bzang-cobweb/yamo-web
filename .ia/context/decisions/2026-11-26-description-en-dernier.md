# `2026-11-26` — Les modifications d'un partenaire : la description toujours en dernier

Complète : 2026-11-26-partenaire-dernieres-modifications.md

- **Type** : décision
- **Question posée** : « mettre toujours le champ description en dernier ».
- **Ce qui est retenu** : dans les modifications d'un partenaire, la description vient toujours après les autres champs (nom, slogan, lien, libellé du bouton, e-mail et téléphone de contact). L'ordre est posé une fois, à la source, dans `PartnerService::values()` : `last_changes`, les lignes de la page et l'e-mail le suivent.
- **Motif** : le texte le plus long ne cache pas les autres changements, qui restent dans les deux premières lignes visibles.
- **Corollaire** : les modifications déjà enregistrées gardent leur ordre jusqu'à la prochaine modification du partenaire.
- **Tranché par** : humain
