# Les messages qui suivent une action : un bandeau flottant commun

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active

## Décision

Dans l'app, le message qui suit une action (une demande envoyée, un avis publié ou supprimé, une
carte retirée, une demande acceptée, un formulaire enregistré, un code renvoyé, ou l'erreur de
l'une d'elles) s'affiche dans un bandeau flottant commun (`showToast`, `ToastHost` posé une fois
dans la mise en page racine) et non plus en texte dans le contenu de chaque écran :

- sous la barre du haut, par-dessus le contenu, il ne défile pas ;
- vert avec une coche pour une réussite, rouge pour une erreur, texte en gras blanc ;
- il reste 4 secondes ; un toucher le ferme ; un nouveau message remplace l'ancien ;
- le lecteur d'écran l'annonce ;
- il survit au changement d'écran : il s'affiche avant le retour (`backWithNotice`, `backToProfile`).

Le panneau d'un jour du calendrier, une fenêtre native qui couvre l'app, pose son propre
emplacement (sans annonce en double). Les erreurs de chargement d'un écran restent dans l'écran.

## Motif

Demande de l'humain du 2026-11-21 : « les messages après avoir fait une action […] sont perdus
dans le contenu et ne sont donc pas très visibles, proposer une solution ». Proposition (bandeau
flottant commun) acceptée : « Oui, fais-le ».
