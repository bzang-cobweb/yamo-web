# `2026-11-25` — La police de l'app : Nunito

- **Type** : décision
- **Question posée** : « je souhaite une fonte plus dans les formes ».
- **Réponse** : « Nunito (ronde, partout) » (parmi : l'arrondie du système, SF Pro Rounded sur iOS seulement ; Nunito ; Poppins).
- **Ce qui est retenu** :
  - **Dépendance** : le paquet `@expo-google-fonts/nunito`, autorisé par ce choix.
  - **Chargement** : les graisses 400, 500, 600, 700 et 800 sont chargées au démarrage (`useFonts`, dans `app/_layout.tsx`).
    - L'écran de démarrage reste affiché d'ici là.
    - Un échec de chargement ouvre l'app quand même, en police système.
  - **Textes** : chaque texte (`ThemedText`) prend le fichier Nunito de sa graisse (`fontFamilyFor()`), y compris une graisse changée par un style local. La graisse elle-même est gardée. Le texte « code » garde sa police à chasse fixe.
  - **Champs** : les zones de saisie des formulaires et la recherche des listes prennent Nunito Medium.
  - **Pas changé** :
    - les tailles ;
    - le logo « YaMo », qui est une image ;
    - les textes natifs du système : menus, alertes, sélecteurs de date.
- **Vérifié** : sur iOS, dans le simulateur. Android n'a pas été regardé : une police chargée y combine parfois mal avec une graisse donnée en plus. À vérifier sur un appareil Android.
- **Tranché par** : humain (Nunito, le paquet) ; session (les graisses chargées, les champs aussi, la graisse gardée)
