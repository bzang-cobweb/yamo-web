# `2026-11-24` — Photo de profil : deux conversions, `list` et `detail`, et l'original à 720 px

Remplace en partie : 2026-11-24-avatar-photos-offre-conversions.md, 2026-11-24-partenaire-logo-sans-original.md

- **Type** : décision
- **Question posée** : « avatar config add original_size to 720, list to 144, detail to 288 and remove the others, backend use detail in form and list in list ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - `AUTH_AVATAR_ORIGINAL_SIZE` = 720 : la photo envoyée est gardée, recadrée en carré, plafonnée à 720 px ;
  - deux conversions : `list` (`AUTH_AVATAR_LIST_SIZE`, 144 px) et `detail` (`AUTH_AVATAR_DETAIL_SIZE`, 288 px) ; `profile`, `member`, `actor` et `admin` retirées (configuration, `.env`, `.env.example`) ;
  - `detail` : le profil (`UserResource`), la page d'un membre (`MemberProfile`), le formulaire du back-office ; `list` : qui agit dans une notification (`ActorData`), la liste du back-office, sa barre du haut (`getFilamentAvatarUrl()`) ;
  - le minimum à l'envoi reste la plus grande conversion : 288 px.
- **Motif** : la demande de l'humain.
- **Portée** : configuration du module Auth, `.env.example` ; `User`, `UserResource`, `MemberProfile`, `ActorData`, `UserForm` et `UserTable` (Filament) ; tests de la photo de profil.
- **Corollaires** : une photo de profil enregistrée avant n'a pas les conversions `list` et `detail` (repli sur la photo) tant qu'elle n'est pas régénérée (`media-library:regenerate "Modules\Auth\Models\User" --force`) ; aucune en local. Les fichiers des conversions retirées restent sur le disque à supprimer en production.
- **Tranché par** : humain
