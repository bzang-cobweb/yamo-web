# Nouvelle demande : « Vous avez reçu une demande de séjour / de visite »

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« Nouvelle demande : Vous avez reçu une demande de séjour ... / Vous avez reçu une demande de visite ... »)
- **Complète** : 2026-11-22-textes-visite-sejour.md

## Décision

Sur la carte du propriétaire, le texte d'une nouvelle demande devient :

- séjour : « Vous avez reçu une demande de séjour pour « :offer » (:period, :people). » ;
- visite : « Vous avez reçu une demande de visite pour « :offer » (:period). ».

En anglais : « You received a … request for … ». En allemand : « Sie haben eine …anfrage für … erhalten. ».

## Portée

Seulement le texte de la carte (`requested.summary`, `summary_stay`). Le texte complet (e-mail, page), le SMS et la copie du membre ne changent pas. Les notifications enregistrées ont été réécrites en local (migration 2026_11_30_200000, relancée).
