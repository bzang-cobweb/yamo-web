# `2026-11-27` — « Mes partenaires » : « Membres » dans le menu d'une carte, vers une page

- **Type** : décision
- **Question posée** : « rajouter dans mes partenaires card dropdown menu un lien membres qui dirige vers une page de membres ».
- **Ce qui est retenu** :
  - `MyPartnerListing` ajoute à chaque carte, après « Messages » (quand il y en a), l'action « Membres » (`partner::partner.member.members`) vers `/partners/{id}/members`, toujours présente.
  - App : la route `/partners/[id]/members` (la page d'un partenaire passe en `partners/[id]/index.tsx`, comme les offres) ouvre `PartnerMembersScreen`, titré « Membres » (`partners.members`, comme le lien) ; dessous, `PartnerMembers`, le même contenu que le panneau « Membres » d'une notification de partenaire (le nombre, chaque compte, le panneau d'un membre).
  - Les membres viennent de `GET /api/v1/partners/{id}` (`members_label`, `members`, servis aux seuls comptes du partenaire) ; la page ne compte pas de clic.
- **Portée** : web : `MyPartnerListing`, langues `mobile.php` du module Partner ; mobile : routes `partners/[id]/`, `PartnerMembersScreen`.
- **Tranché par** : humain
