# Le total d'une demande, seulement s'il dépasse 0

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-caution-en-montant.md, 2026-11-20-caution-meuble-toujours.md

## Décision

Dans les lignes d'une demande, la ligne du total ne s'affiche que si son montant dépasse 0 :

- visite : loyer × (mois de caution + mois d'avance) ;
- séjour : total de la demande + caution.

## Motif

Demande de l'humain du 2026-11-20 : « annuler ma dernière demande et afficher total seulement
si > 0 » (la condition « caution ou avance renseignée » est annulée, commit 64ccb03). Appliqué
aussi au séjour : décidé seul.
