# `2026-11-27` — Notification de groupe : « Membres » du partenaire dans le menu ⋮

- **Type** : décision
- **Question posée** : « si la notification est un group, ajouter un menu item membres qui sera un composant qui va afficher le nom, le slogan, le nombre de partner_users (en indiquant s'il est admin) et au footer un bouton ajouter un membre ». Arbitrages de l'humain : « enlever le bouton ajouter un membre » ; montrer chaque membre (le nombre en titre, puis chacun avec la pastille « Administrateur »).
- **Ce qui est retenu** :
  - Le fil d'un partenaire (un groupe) : `AddPartnerToNotificationThread` ajoute au menu, après « Info », `{key: 'members', label: 'Membres', link: null, record: '<id>'}`.
  - `GET /api/v1/partners/{id}` sert en plus, à ses comptes seulement (comme la pastille et les vues) : `members_label` (« 3 membres ») et `members`, chacun `ActorData` (photo, nom, page) et `tag` « Administrateur » (ton `neutral`) ou `null` ; les administrateurs d'abord, puis par nom.
  - App : `PartnerMembers` (nom, slogan, nombre, puis une ligne par compte, qui ouvre sa page en fermant le panneau), dans un panneau à taille maximale titré « Membres ». `PartnerLoader` porte le chargement et les erreurs, commun à `PartnerDetails` et `PartnerMembers`.
- **Motif** : le partenaire est aujourd'hui le seul groupe ; c'est son module qui sait lire ses membres. Pas d'ajout de membre depuis l'app : les comptes se rattachent au back-office.
- **Portée** : web : `PartnerResource`, écouteur Partner, langues (`member.members`, `member.members_count`) ; mobile : `PartnerMembers`, `PartnerLoader`, types `Partner`/`PartnerMember`, `NotificationDetailScreen`.
- **Tranché par** : humain
