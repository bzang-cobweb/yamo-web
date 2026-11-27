# `2026-11-27` — Onglets d'un visiteur : une invitation, plus la connexion intégrée

- **Type** : décision
- **Remplace en partie** : 2026-11-27-visiteur-onglets.md (la connexion affichée dans l'onglet)
- **Question posée** : « Si invité clique sur un lien footer notifications ou profile, on ouvre une page avec le titre
  (profile ou notifications), un message d'invite puis un bouton login or sign up. »
- **Ce qui est retenu** : les onglets Notifications et Profil d'un visiteur montrent le titre de l'onglet, un message
  propre à l'onglet et un bouton « Se connecter ou s'inscrire » qui ouvre `/sign-in` par-dessus (l'inscription y suit
  d'elle-même, décision 2026-11-27-connexion-sans-mot-de-passe). La langue reste en dessous. Le menu du bas commun aux
  visiteurs et aux membres est gardé.
- **Portée** : app, `GuestTab`, routes `(tabs)/notifications` et `(tabs)/profile` ; textes `auth.signInOrUp`,
  `auth.guest.*`.
- **Tranché par** : humain (demande), session de nuit pour les textes
