# Page d'un membre, ouverte depuis son nom dans une notification

- **Type** : décision de conception
- **Date** : 2026-11-19
- **Décidé par** : l'humain (« ajouter le lien vers l'utilisateur dans la notification card » ; choix « New member page » et « lien sur le nom de l'utilisateur »)

## Décision

- **La page publique d'un membre** (`/members/{id}` dans l'app, `GET /v1/members/{user}` dans l'API) montre :
  - sa photo, son nom, « Membre depuis » ;
  - sa présentation ;
  - sa ville, celle de sa première adresse ;
  - son nombre de séjours acceptés.
- **Jamais** son e-mail, son téléphone ni son adresse précise.
- **Accès** : réservée aux comptes connectés.
- **Dans une notification de demande**, le nom du membre, dans le texte, est un lien vers sa page. Les données portent `text_links` (`{text, link}`), génériques : tout texte de notification peut en porter.

## Motif

Le propriétaire veut savoir à qui il a affaire avant de répondre.

## Portée

La page est servie par le module Offer, qui compte les séjours ; le module Auth n'en dépend pas. Les notifications déjà enregistrées n'ont pas de lien.

## Corollaires

- Afficher les coordonnées d'un membre (e-mail, téléphone) demanderait une nouvelle décision.
