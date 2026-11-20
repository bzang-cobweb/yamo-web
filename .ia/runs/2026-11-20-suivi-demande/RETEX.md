# RETEX — Suivi d'une demande

- **Ce qui s'est produit deux fois** : des données de démonstration générées en lot dans la même seconde ont faussé un ordre chronologique (déjà vu avec les notifications de test). Règle candidate pour le projet : toute donnée de démonstration datée reprend les dates métier (création, réponse, annulation) et non l'instant de génération.
- **Règle ré-implémentée ?** Non. Le fil reprend le mécanisme de `user_id` (contrat lu par le canal `database`) au lieu d'en créer un autre.
- **Règle qui a trouvé un domicile** : « une carte par fil, non lues par fil, lecture de tout le fil » vit dans `NotificationThreads`, seul point d'appel du contrôleur.
