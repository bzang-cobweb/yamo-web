# `2026-11-18` — Écran d'entrée : sélecteurs, description, affiché une seule fois

- **Type** : décision
- **Remplace en partie** : `2026-11-18-ecran-entree-toujours.md`. L'écran ne s'affiche plus à chaque premier lancement sans condition ; il s'affiche tant que langue et pays ne sont pas choisis.
- **Question posée** : aucune. Consigne de l'humain après avoir vu l'écran :
  > 1. si on a déjà la langue et le pays on n'affiche plus l'écran d'accueil
  > 2. la langue doit être un sélecteur
  > 3. garder la description de l'application en dessous du logo
  > 4. faire un sélecteur pour le choix du pays avec la possibilité de rechercher
- **Ce qui est retenu** :
  - **Affichage** : l'écran d'entrée s'affiche tant que la langue (gardée sur l'appareil, ou celle du compte connecté) et le pays (gardé et toujours ouvert) ne sont pas choisis tous les deux. Ensuite, on entre directement. « Entrer » garde les deux.
  - **Contenu** : le logo, la description de l'application juste dessous, puis un sélecteur de langue et un sélecteur de pays avec recherche.
  - **Pays unique** : quand un seul pays est ouvert, il est montré choisi.
- **Motif** : un écran d'entrée qui ne se répète pas, et un choix de pays praticable avec beaucoup de pays.
- **Portée** : app mobile (écran d'entrée).
- **Corollaires** : le drapeau rouvre toujours l'écran quand plusieurs pays sont ouverts.
- **Tranché par** : humain
