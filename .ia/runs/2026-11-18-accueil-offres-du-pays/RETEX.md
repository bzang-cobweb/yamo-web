# RETEX — Accueil : barre du haut et offres du pays

## Ce qui s'est produit

- **Le libellé du pays a changé cinq fois** (Pays, Dans quel pays utilisez-vous l'app ?, Dans quel pays ?, Pays, Quel pays ?), ainsi que la description de l'application. Comme les textes sont servis par l'API, chaque changement n'a touché qu'une ligne par langue, sans nouvelle version de l'app.
- **Un regroupement de commandes contenant `git add -A` a été refusé entièrement par le garde-fou,** y compris l'écriture du README qui le précédait. Il a fallu relancer chaque étape.
- **Le bouton de développement d'Expo masquait le drapeau dans le simulateur.** La vérification visuelle d'un élément placé en haut à droite en est gênée.

## Règles candidates

- **Projet (app)** : tout texte d'interface susceptible d'être réglé, et surtout un libellé discuté, est servi par l'API, jamais écrit en dur.
- **Marketplace (garde-fou)** : ne jamais mettre `git add` dans un regroupement de commandes. L'ajouter seul, par chemins.

## Règle ré-implémentée, règle domiciliée

- **Domiciliée** : la barre du haut (`AppTopBar`) vit à un seul endroit, pour le visiteur comme pour le membre.
- **Domiciliée** : la vitrine (`offer.browse`) est une liste générique, ouverte par `canBeViewedBy`.
- **Ré-implémentée** : la recherche avec délai est écrite deux fois, dans `BrowseScreen` et dans `ProfileListScreen`. C'est un candidat à un hook `useDebounced` partagé.
