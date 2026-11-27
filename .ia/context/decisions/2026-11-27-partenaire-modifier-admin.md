# `2026-11-27` — Seul un administrateur modifie un partenaire depuis l'app

Remplace en partie : 2026-11-24-partenaires-par-les-membres.md (« tous les comptes rattachés le modifient »)

- **Type** : décision
- **Question posée** : « seuls les admins peuvent modifier un partenaire ».
- **Ce qui est retenu** : dans l'app, `PartnerPolicy::update` demande d'être administrateur (`is_admin`) du partenaire : 404 pour un étranger, 403 pour un compte rattaché qui ne l'est pas — la même règle que « Retirer » et la gestion des membres (`PartnerPolicy::admin()`, commune aux trois). Elle couvre la modification (`PATCH /api/v1/partners/{id}`) et le formulaire d'édition (`GET /api/v1/forms/partner.partner?record=`). Sur une carte de « Mes partenaires », « Modifier » n'est servi qu'à un administrateur, comme « Retirer ».
- **Corollaires** : un compte rattaché non administrateur voit toujours le partenaire, sa page, son fil, ses membres ; il écrit dans le fil (`message`). Le back-office garde ses permissions (`partners.update`).
- **Portée** : web : `PartnerPolicy`, `MyPartnerListing` ; tests adaptés (l'autre compte qui modifie devient administrateur). Aucun changement mobile.
- **Tranché par** : humain
