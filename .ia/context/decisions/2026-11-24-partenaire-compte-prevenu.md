# `2026-11-24` — Le compte prévenu des demandes de partenaire, choisi sur la page Application

- **Type** : décision de conception
- **Question posée** : « le compte back-office qui reçoit les ajouts et modifications faits depuis l'app, comment le désigner ? ajouter la sélection d'un utilisateur sur l'application ».
- **Ce qui est retenu** :
  - Un réglage `settings.partner_notify_user_id` (clé étrangère vers `users`, mise à vide si le compte est supprimé).
    - Il est choisi au back-office, page Application, onglet « Partenaires », champ « Compte prévenu des demandes de partenaire ».
    - On choisit parmi les comptes du back-office : super-administrateurs et comptes qui ont l'accès au back-office.
    - Il n'est pas servi à l'app.
  - `Setting::partnerNotifyUser()` le lit, et `PartnerNotifierService::submitted()` le prévient. Vide : personne.
  - La variable `PARTNER_NOTIFY_EMAIL` et `partner.notify_email` sont retirées. Elles n'avaient jamais été commitées.
- **Motif** : la demande. Le compte se change au back-office, sans toucher au `.env`.
- **Tranché par** : humain
