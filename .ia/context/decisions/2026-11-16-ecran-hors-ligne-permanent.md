# `2026-11-16` — Écran hors ligne affiché dès que l'appareil n'a pas internet

Remplace : 2026-11-16-contrat-textes-servis-par-api.md (comportement de l'écran hors ligne uniquement ; le reste reste en vigueur)

- **Type** : décision
- **Question posée** : (demande de l'humain après livraison) « l'application mobile doit
  afficher le screen offline si pas internet et afficher le screen courant si et
  seulement si il y a internet » ; puis : quelle dépendance pour connaître l'état du
  réseau ?
- **Réponse** :
  - à tout moment, sans internet, l'app affiche l'écran hors ligne local ;
  - dès que la connexion revient, elle réaffiche l'écran courant, dans l'état où il était
    (navigation et saisie conservées : l'écran hors ligne recouvre l'app sans la
    démonter) ;
  - au retour du réseau, les chargements en attente reprennent d'eux-mêmes ;
  - un démarrage impossible avec internet (serveur injoignable) garde l'écran hors ligne
    du chantier précédent ;
  - dépendance autorisée : **`expo-network`** (module officiel Expo).
- **Motif** : l'app ne montre jamais un écran qui ne peut pas fonctionner.
- **Portée** : app mobile, tous les écrans.
- **Corollaires** :
  - une erreur réseau en cours d'usage reste possible si la connexion tombe pendant une
    requête : le message local s'affiche alors à cet endroit, avant que l'écran hors
    ligne prenne le relais ;
  - `expo-network` est la seule dépendance ajoutée.
- **Tranché par** : humain
