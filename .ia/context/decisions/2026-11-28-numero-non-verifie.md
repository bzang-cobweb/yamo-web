# `2026-11-28` — Seul un numéro prouvé par code identifie un compte ou le retient

- **Type** : décision
- **Remplace en partie** : 2026-11-27-connexion-sans-mot-de-passe (point « numéro jamais vérifié » : il ne se connecte
  plus par SMS) ; lève le risque assumé de 2026-11-28-connexion-panneau
- **Question posée** : risque noté la nuit du 2026-11-28 (`.ia/runs/nuit-2026-11-28/serveur.md`) : l'autre contact
  d'une inscription (numéro non vérifié) mène à ce compte par SMS ; son vrai titulaire peut y entrer par « Mot de passe
  oublié ? » et ne peut pas s'inscrire avec son numéro.
- **Ce qui est retenu** :
  - la connexion par SMS ne trouve que les comptes dont le numéro principal est vérifié (`phone_verified_at` non nul) :
    la branche « numéro jamais vérifié » de `LoginService::start` est retirée ; un numéro non vérifié mène au défi
    `REGISTER` ;
  - un numéro non vérifié ne tient pas le numéro : `PhoneOwnership::isTaken` (donc `UniquePhone`, `Challenges::ensureFree`
    et l'ajout d'un numéro) l'ignore, comme celui d'un compte inactif ; de même un numéro supplémentaire non vérifié
    (`isTakenAsExtra`) ;
  - quand un numéro est prouvé par code (inscription par SMS, changement de numéro, ajout d'un numéro supplémentaire,
    vérification du numéro actuel), `PhoneOwnership::release` le retire des comptes qui le portaient sans l'avoir
    vérifié : numéro principal (`phone` et `phone_country_id` à null), numéros supplémentaires non vérifiés supprimés.
    L'e-mail de prévenance (`PhoneReleasedMail`) reste réservé au compte inactif ;
  - le compte qui garde un numéro non vérifié peut toujours le vérifier depuis son profil (« Renvoyer le code »,
    décision 2026-11-27-verifier-email-telephone) tant que personne ne l'a prouvé avant lui.
- **Décidé seul (mode nuit)** :
  - un numéro saisi **sans preuve** (l'autre contact de l'inscription, `auth.register`) ne retire le numéro à personne :
    il est refusé dès qu'un autre compte le porte, vérifié ou non (`UniquePhone(proven: false)`,
    `PhoneOwnership::isHeld`, `Challenges::ensureFree(..., proven: false)`), ou le tient comme numéro supplémentaire
    vérifié. Sans cela, l'index unique de `users.phone` aurait buté ; le vrai titulaire, lui, s'inscrit par SMS ;
  - un compte en attente de suppression ne retient pas non plus un numéro qu'il n'a jamais vérifié : il le perd s'il
    est prouvé ailleurs ;
  - la vérification du numéro actuel passe par `Challenges::persist` (tout ou rien) pour retirer le numéro des numéros
    supplémentaires non vérifiés des autres comptes ;
  - `LoginService::verified` garde le marquage `phone_verified_at` par SMS pour le seul cas où la vérification a été
    retirée pendant que le code attendait (défi `LOGIN` né d'un numéro vérifié).
- **Corollaire** : un compte inscrit par e-mail avec un numéro qu'il ne vérifie pas peut le perdre au profit de qui le
  prouve, sans en être prévenu.
- **Portée** : module Auth (`PhoneOwnership`, `UniquePhone`, `Challenges`, `LoginService`, `RegisterService`,
  `RegisterForm`, `ContactVerificationService`, commentaires de `PhoneChangeService` et `UserPhoneService`), tests
  `UnverifiedPhoneTest`, `SignInPanelApiTest`, `VerificationCodesApiTest`.
- **Tranché par** : humain (règle et correction), session de nuit pour le détail
