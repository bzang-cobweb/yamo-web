# Démo : un logement publié d'un autre membre

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active

## Décision

Les données de démonstration (`DemoSeeder::otherOffer`) créent un logement publié appartenant au
premier visiteur (demo-visiteur-1) : « Studio meublé à Bonanjo », meublé, 30 000 FCFA par jour,
caution 60 000, arrivée dès 14:00, départ au plus tard 11:00, deux photos, à Douala. Le compte
de démonstration peut le voir sans en être propriétaire, le mettre en favori, y faire une
demande. Rejouable : créé une seule fois. Appliqué à la base locale (DemoSeeder relancé, sans
réinitialisation ; il n'ajoute que ce qui manque).

## Motif

Question de l'humain du 2026-11-21 : « dans les données de tests y a-t-il une offre qui
n'appartient pas à mon utilisateur de test ? si non la créer » — la base locale en avait deux,
créées à la main, mais pas les données de démo. Réponse : « Oui, ajoute-la ». Logement et
propriétaire : décidé seul.
