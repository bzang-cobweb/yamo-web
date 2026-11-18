# `2026-11-19` — Contrat « annonces sans coordonnées », validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-19-annonces-sans-coordonnees.md`.
- **Réponse** : « Approved » ; précisions données avant : « Refuse the save », « All offer texts ».
- **Ce qui est retenu** :
  - un texte d'offre qui contient une coordonnée ou un indice de contact est refusé (erreur 422 sous le champ), depuis l'app comme depuis le back-office, dans chaque langue ;
  - champs contrôlés : le titre, la description, et tout attribut texte du domaine ;
  - sont détectés : un téléphone (8 chiffres ou plus, sauf un montant suivi d'une devise), des chiffres écrits en lettres, un e-mail (même masqué), un lien ou un site, un réseau social ou une messagerie, une formule d'appel au contact, un pseudonyme « @… » ;
  - la règle `NoContactDetails` est générique et vit dans le module Form ; ses listes de mots sont dans les fichiers de traduction.
- **Motif** : garder les échanges dans l'application.
- **Portée** : formulaire `offer.offer`, ressource Filament des offres.
- **Corollaires** :
  - les offres déjà enregistrées ne sont pas reprises : le contrôle s'applique à leur prochain enregistrement ;
  - hors périmètre : photos, avis, profil, adresses.
- **Tranché par** : humain
