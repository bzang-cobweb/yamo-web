# RETEX — bouton d'envoi fixe en bas des formulaires

- **`KeyboardAvoidingView` mesure sa place dans son parent** : sous un en-tête, ou dans un écran d'onglet, il sous-estime le recouvrement du clavier ; le déplacer autour de tout l'écran a empiré les choses dans les onglets. Règle candidate pour l'app : pour un élément fixé en bas, mesurer à l'écran (`measureInWindow`) au lieu d'utiliser `KeyboardAvoidingView`.
- **Le simulateur reste indispensable** : les tests passaient avec la barre cachée par le clavier (trois essais, vus seulement à l'écran). Les tests unitaires ne voient pas la mise en page.
- **Maquettes de bibliothèques dans les tests** : `react-native-safe-area-context` simulé sans `useSafeAreaInsets` a cassé trois suites dès qu'un composant commun l'a utilisé. Règle candidate : une maquette de bibliothèque partagée, plutôt qu'une maquette partielle par fichier.
- **Prettier** : pas de configuration dans le dépôt mobile, les fichiers existants ne passent pas `prettier --check` ; ne pas lancer `--write` (déjà noté dans un RETEX précédent : deuxième occurrence, à transformer en règle du projet).
