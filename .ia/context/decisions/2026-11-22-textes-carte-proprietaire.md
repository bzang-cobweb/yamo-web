# Cartes du propriétaire : nouvelle demande enregistrée, modifiée, annulée

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (textes donnés mot pour mot pour « Nouvelle demande », « Demande modifiée », « Demande annulée »)
- **Complète** : 2026-11-22-textes-visite-sejour.md, 2026-11-22-annulation-visite-sejour.md

## Décision

Texte de la carte du propriétaire (fr ; en et de pareil) :

| Type | Séjour | Visite |
|---|---|---|
| Nouvelle demande | Une demande de séjour pour « … » (du … au …, 2 personnes) a été enregistrée. | Une demande de visite pour « … » (le … ou …) a été enregistrée. |
| Demande modifiée | La demande de séjour pour « … » a été modifiée (du … au …, 2 personnes). | La demande de visite pour « … » a été modifiée (le … ou …). |
| Demande annulée | La demande de séjour pour « … » (du … au …, 2 personnes) a été annulée. | La demande de visite pour « … » (le … ou …) a été annulée. |

Une visite avec deux dates s'écrit partout « le 5 déc. 2026 ou 7 déc. 2026 », sans second « le » (période `visit_or`, fr et de).

## Portée

Seuls les textes de carte (`summary`, `summary_stay`) des étapes `requested`, `updated` et `cancelled` changent ; `cancelled` gagne `summary_stay` avec les personnes. Le texte complet, le SMS et les copies ne changent pas. Les notifications enregistrées ont été réécrites en local.
