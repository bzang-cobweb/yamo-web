# `2026-11-23` — Formulaires de l'app : pas de double envoi

- **Type** : décision
- **Question posée** : « éviter le clic à double sur un bouton de soumission d'un formulaire ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** : tant qu'un envoi est en cours, un nouveau toucher sur le bouton (ou « Suivant ») est ignoré, même avant que le bouton ne s'affiche désactivé. Le bouton renvoie une fois la réponse reçue, réussie ou refusée.
- **Motif** : la demande de l'humain ; le bouton désactivé ne suffisait pas, deux touchers rapides partaient avant le nouveau rendu.
- **Portée** : `DynamicForm` de l'app, donc tous les formulaires servis par l'API.
- **Corollaires** : aucun changement côté serveur.
- **Tranché par** : humain
