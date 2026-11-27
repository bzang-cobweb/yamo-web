# `2026-11-27` — Un visiteur a le même menu du bas ; Notifications et Profil lui montrent la connexion

Remplace en partie : 2026-11-18-accueil-offres-du-pays.md (l'accueil d'un visiteur `/welcome`, sans menu du bas)

- **Type** : décision
- **Question posée** : « mettre le même menu footer au visiteur avec affichage page de login ».
- **Ce qui est retenu** :
  - Les onglets (Accueil, Recherche, Notifications, Profil) sont ceux de tous, visiteur compris : l'Accueil et la Recherche sont les mêmes que pour un membre (données publiques).
  - Pour un visiteur, les onglets Notifications et Profil affichent la connexion (`SignInScreen embedded`) : sous la barre du haut de l'onglet, sans en-tête de retour, le titre « Se connecter », le formulaire, « Mot de passe oublié » et « Créer un compte » (qui s'ouvrent par-dessus l'onglet), puis la langue. Connecté, l'onglet montre aussitôt son écran.
  - L'accueil d'un visiteur (`/welcome`, `GuestHomeScreen`) disparaît ; la pile démarre sur les onglets. Les liens vers `/sign-in` (fiche d'une offre, sections, pied de liste) ouvrent toujours la connexion par-dessus.
- **Portée** : mobile : `app/_layout.tsx` (onglets hors de la garde, ancre `(tabs)`), `(tabs)/notifications.tsx`, `(tabs)/profile/index.tsx`, `SignInScreen` (`embedded`), `GuestScreen` (`embedded`) ; `welcome.tsx` et `GuestHomeScreen` supprimés.
- **Vérifié dans le simulateur** : déconnecté, les quatre onglets ; Notifications et Profil montrent la connexion ; « Créer un compte » s'ouvre par-dessus et le retour ramène à l'onglet. **Non vérifié** : la connexion elle-même depuis un onglet (mot de passe et code).
- **Tranché par** : humain
