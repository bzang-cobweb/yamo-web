# `2026-11-19` — Bornes du calendrier d'une date, servies par Laravel

- **Type** : décision (correctif découvert pendant le chantier « Réservations »)
- **Constat** : le calendrier de l'app proposait toujours du 1er janvier 1900 à aujourd'hui ; aucune date future ne pouvait être choisie (réservation, « Disponible dès le », filtre « Libre le »).
- **Ce qui est retenu** : un champ date sert ses bornes (`Field::dateRange()`, `date_range: {min, max}`) ; sans bornes, toute date. Date de naissance : 1900 → aujourd'hui (ou la dernière date qui donne l'âge minimum) ; réservation : aujourd'hui (départ : demain) → un an. L'app ouvre le calendrier sur la date choisie, sinon aujourd'hui ramené dans les bornes. La validation reste côté serveur.
- **Motif** : rendre la réservation possible ; corriger les dates futures.
- **Portée** : module Form (`Field`), `PersonalInfoForm`, `OfferBookingForm` ; app (`DateField`).
- **Corollaires** : « Disponible dès le » et « Libre le » acceptent désormais toute date.
- **Tranché par** : agent (correctif nécessaire au contrat validé)
