Remplacée par : 2026-11-20-caution-dans-le-total.md

# La caution et l'avance de loyer affichées

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-caution-avance-loyer.md, 2026-11-20-caution-meuble.md, 2026-11-20-charges-visite.md

## Décision

- **Page d'une notification de demande** (lignes de `BookingResource`), après le total :
  - visite (non meublé) : « Caution » et « Avance de loyer », en mois (« 2 mois ») ;
  - séjour (meublé) : « Caution », en montant (« 100 000 FCFA »).
  Une ligne n'apparaît que si le logement renseigne la valeur.
- **Fiche d'un logement**, section « Caractéristiques » : les mêmes champs, après les charges,
  comme tout attribut renseigné du domaine (aucun code propre : la fiche les sert déjà).

## Motif

Demande de l'humain du 2026-11-20 : « affichez ces informations sur le détail de la
notification et sur les caractéristiques du détail de l'offre ». Place après le total et
absence de ligne quand la valeur manque : décidé seul.

## Corollaires

- Ce sont les valeurs actuelles du logement, pas figées à la demande.
- Sur la fiche, l'unité « mois » est fixe (« 1 months » en anglais) ; dans les lignes de la
  demande, elle s'accorde au nombre.
