# `2026-11-27` — Faire ou défaire un administrateur depuis le panneau d'un membre

- **Type** : décision
- **Question posée** : « dans le panneau du détail du membre ajouter un moyen de toggle is_admin », puis « un membre ne peut pas changer son propre is_admin ».
- **Ce qui est retenu** :
  - `PATCH /api/v1/partners/{partner}/members/{user}` `{is_admin: bool}` → 204 (`PartnerMemberController`, `PartnerService::setAdmin`). Droit `PartnerPolicy::manageMembers` : un administrateur du partenaire (403 pour un compte rattaché, 404 pour un étranger, comme « Retirer »). Le compte visé doit être rattaché (404 sinon) ; changer son propre rôle : 422 « Vous ne pouvez pas changer votre propre rôle d'administrateur. ».
  - La fiche du partenaire sert, par membre, `is_admin` et `can_change_admin` (le lecteur est administrateur et ce n'est pas lui).
  - App : dans le panneau d'un membre, sous ses coordonnées, l'interrupteur « Administrateur » (sélection noire, comme un champ oui / non), seulement si `can_change_admin`. Basculé : envoyé, puis le partenaire est relu (pastille, ordre) ; refusé : le message du serveur en bandeau. Le panneau suit le membre par son identifiant, pour montrer l'état relu.
- **Corollaire** : un partenaire garde toujours au moins un administrateur — défaire un administrateur demande d'en être un autre ; aucune garde « dernier administrateur » n'est donc nécessaire. Le back-office reste libre (son action « Faire administrateur / Retirer l'administration »).
- **Motif** : la règle de « Retirer » (administrateur seulement) s'applique à la gestion des membres ; ne pas pouvoir se retirer soi-même évite qu'un partenaire perde son dernier administrateur.
- **Portée** : web : route `v1.partners.members.update`, `PartnerMemberRequest`, `PartnerMemberController`, `PartnerService::setAdmin`, `PartnerPolicy::manageMembers`, `PartnerResource::members()`, langues (`member.own_admin`, `partners.admin`) ; mobile : `PartnerService.setAdmin`, `PartnerMembers` (`MemberSheet`, `MemberCard`), type `PartnerMember`.
- **Tranché par** : humain
