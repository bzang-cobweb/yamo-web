Remplacée en partie par : 2026-11-18-ecran-entree-selecteurs.md (conditions d'affichage et contenu de l'écran).


# `2026-11-18` — L'écran d'entrée s'affiche toujours

- **Type** : décision
- **Remplace en partie** : `2026-11-18-espace-pays.md`, sur ce point : « pas d'écran de choix tant qu'un seul pays est ouvert ».
- **Question posée** : aucune. L'humain a précisé pendant le chantier, en trois messages.
- **Réponse** :
  > Même s'il n'y a qu'un seul pays, l'écran d'accueil permet de sélectionner la langue
  >
  > si une seule langue alors en entre directement
  >
  > en fait non toujours garder l'écran d'accueil meme si un pays et une langue
- **Ce qui est retenu** :
  - l'écran d'entrée s'affiche toujours au premier lancement, même avec un seul pays et une seule langue ;
  - il propose la langue, et le pays quand plusieurs sont ouverts. Un pays seul y est montré avec son drapeau, sans choix possible ;
  - on entre en le validant. Aux lancements suivants, le choix gardé sur l'appareil fait entrer directement ;
  - le drapeau de la barre du haut rouvre cet écran.
- **Motif** : l'entrée dans l'app doit être marquée, quel que soit le nombre de pays ou de langues.
- **Portée** : app mobile (écran d'entrée).
- **Corollaires** : le serveur ne change pas. Avec un seul pays actif, il le prend toujours d'office quand l'en-tête manque.
- **Tranché par** : humain
