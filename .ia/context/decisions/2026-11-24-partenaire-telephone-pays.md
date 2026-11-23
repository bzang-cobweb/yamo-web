# `2026-11-24` — Téléphone d'un partenaire : l'indicatif de son pays

- **Type** : décision
- **Question posée** : « l'indicatif du téléphone est celui du pays du partenaire ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - dans l'app, le téléphone de contact est un champ téléphone dont le seul pays proposé est celui du partenaire (le pays courant pour un nouveau) : choisi d'office et verrouillé ; un autre pays est refusé, le numéro est vérifié pour ce pays ;
  - au back-office, l'indicatif du pays choisi s'affiche devant le numéro, saisi en national ; le numéro est vérifié pour ce pays ;
  - enregistré au format international (E.164, « +237670000005 »), comme le téléphone d'une offre ; servi lisible sur la page du partenaire (« +237 6 70 00 00 05 ») ;
  - les numéros déjà saisis sont normalisés (migration `2026_11_24_130000_normalize_partner_contact_phones`), un numéro illisible gardé tel quel.
- **Motif** : la demande de l'humain.
- **Portée** : `PartnerForm` (app et back-office), `PartnerService`, `PartnerResource`.
- **Corollaires** : changer le pays d'un partenaire au back-office ne réécrit pas son numéro déjà enregistré.
- **Tranché par** : humain
