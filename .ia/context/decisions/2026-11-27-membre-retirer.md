# `2026-11-27` — Retirer un membre du partenaire depuis son panneau

- **Type** : décision
- **Question posée** : « ajouter le lien pour retirer du groupe, seul un admin peut le faire et ne peut pas le faire sur lui-même ».
- **Ce qui est retenu** :
  - `DELETE /api/v1/partners/{partner}/members/{user}` → 204 (`PartnerMemberController::destroy`, `PartnerService::removeMember`), droit `PartnerPolicy::manageMembers` : un administrateur (403 pour un compte rattaché, 404 pour un étranger). Le compte visé doit être rattaché (404 sinon) ; se retirer soi-même : 422 « Vous ne pouvez pas vous retirer vous-même. » (`errors.member`).
  - La fiche du partenaire sert, par membre, `can_remove` (le lecteur est administrateur et ce n'est pas lui).
  - App : en bas du panneau d'un membre, « Retirer du groupe » en rouge, seulement si `can_remove` ; une confirmation (« Retirer ce membre du groupe ? », « Annuler » / « Retirer du groupe »), puis le panneau se ferme, « Membre retiré. », et le partenaire est relu ; refusé : le message du serveur, le panneau reste.
- **Corollaires** : le compte retiré ne lit plus le fil du partenaire ni ne le modifie (il n'y est plus rattaché) ; il n'est pas prévenu. On ne se retire pas soi-même depuis l'app : un partenaire garde toujours un administrateur.
- **Portée** : web : route `v1.partners.members.destroy`, `PartnerService::removeMember`, `PartnerResource::members()`, langues (`member.own_remove`, `partners.member_remove*`) ; mobile : `PartnerService.removeMember`, `PartnerMembers` (`MemberCard`), type `PartnerMember`.
- **Tranché par** : humain
