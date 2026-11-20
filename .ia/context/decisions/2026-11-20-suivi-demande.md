# Suivi d'une demande : une carte par demande, les deux côtés, non lues par demande

- **Type** : arbitrage
- **Date** : 2026-11-20
- **Décidé par** : l'humain, en réponse aux trois questions du chantier « suivi de la demande »

## Décision

- **Liste** : une carte par demande, avec sa dernière étape et le statut réel ; elle remonte en tête à chaque nouvelle étape. L'ouvrir montre tout le suivi.
- **Suivi** : toutes les étapes de la demande, des deux côtés (membre et propriétaire), avec leur date et leur message. Il est construit à partir des notifications des deux parties, sans nouvelle table.
- **Non lues** : une demande qui a une étape non lue compte pour 1. L'ouvrir marque toutes ses étapes lues.

Contrat : specs/2026-11-20-suivi-demande.md (validé, voir plus bas).

## Validation

- **2026-11-20** : contrat validé par l'humain (« je valide »), avec une précision : sur la page de la notification, le suivi va **de la plus ancienne étape à la plus récente**.
- La question laissée ouverte n'a pas reçu de réponse : chez le propriétaire, une demande qu'il a acceptée garde comme dernière étape « demande reçue ». Le contrat s'applique tel quel : la carte ne remonte pas quand il répond, mais sa pastille suit le statut réel.
