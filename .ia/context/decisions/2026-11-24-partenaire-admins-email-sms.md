# `2026-11-24` — Rien à celui qui agit ; les administrateurs d'un partenaire par e-mail et SMS

Remplace : 2026-11-24-confirmation-auteur.md

- **Type** : décision de conception
- **Question posée** : « la notification n'est pas envoyée au membre qui fait l'action. Pour les groupes, la notification est aussi envoyée aux admins, mais sans enregistrer en base de données. »
- **Réponses** (choix de l'humain) :
  - ne plus rien envoyer à l'auteur, pour les réservations comme pour les partenaires ;
  - les administrateurs sont prévenus pour toutes les actions sur un partenaire.
- **Ce qui est retenu** :
  - **Rien pour celui qui agit.** La confirmation par e-mail et SMS (commit `50e6ced`) est retirée : le listener `ConfirmBookingActionToActor` et les textes `own_subject` et `own_sms` disparaissent. Celui qui agit retrouve son action dans son fil (2026-11-24-notifications-fil-de-groupe).
  - **Les administrateurs d'un partenaire** (`is_admin`) reçoivent l'e-mail et le SMS de chaque action (`PartnerNotification`, `inTab: false`) : ajout ou modification depuis l'app, changement de statut, « Valider », modification au back-office.
    - Ils ne reçoivent pas de notification dans l'onglet : ils lisent l'action dans le fil du partenaire.
    - Celui qui agit n'en reçoit pas, même s'il est administrateur.
    - Un administrateur qui est déjà le destinataire de l'onglet ne la reçoit pas deux fois.
  - **La notification dans l'onglet ne change pas** : une par action. Pour une demande depuis l'app, au compte choisi sur la page Application ; pour une action au back-office, à l'auteur de la dernière demande.
- **Tranché par** : humain
