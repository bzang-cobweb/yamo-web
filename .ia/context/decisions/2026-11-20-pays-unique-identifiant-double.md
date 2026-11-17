# `2026-11-20` — Pays unique verrouillé ; sélecteur E-mail | Téléphone pour l'identifiant

- **Type** : décision
- **Question posée** : contrat `specs/2026-11-20-pays-unique-identifiant-double.md`, et la présentation de l'identifiant quand les deux canaux sont actifs.
- **Réponse** : « Email / Phone switch (Recommended) », « Validated ».
  - **Un seul pays actif** : chaque champ de pays de l'app et du back-office est présélectionné et verrouillé, mais reste envoyé et validé.
  - **Les deux canaux** : un sélecteur « E-mail | Téléphone » au-dessus d'un seul champ visible, le champ e-mail, ou le pays et le numéro.
- **Motif** : aucun choix inutile à faire, et chaque identifiant saisi avec le bon clavier et dans le bon format.
- **Portée** : module Form (`locked`, type `segmented`, `visible_when`), formulaires Auth, back-office, app mobile.
- **Corollaires** : l'API de connexion et de mot de passe oublié reçoit `email`, ou `phone` et `phone_country`, à la place de `identifier`.
- **Tranché par** : humain
