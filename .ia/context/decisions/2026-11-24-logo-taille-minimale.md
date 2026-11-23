# `2026-11-24` — Logo d'un partenaire : 960 px au moins à l'envoi

- **Type** : décision
- **Question posée** : « exige un logo de 960 px minimum à l'envoi et reset les logos ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - un logo envoyé mesure au moins 960 px **sur son plus grand côté** : un bandeau (1600 × 400) ou un logo en hauteur (640 × 960) est accepté ; le minimum est la taille enregistrée (`PartnerLogoService::minUploadSize()` = la plus grande conversion, décision 2026-11-24-partenaire-logo-mine-960), sans réglage à part ;
  - règle de validation générique `MinImageSide` (module Form), utilisée par le champ de l'app (`Field::minImageSide()`, vérifiée à l'envoi du fichier) et par le champ du back-office ; message « L'image doit mesurer au moins 960 px sur son plus grand côté. » ;
  - une aide sous le champ, dans l'app et le back-office : « Au moins 960 px sur le plus grand côté. » ;
  - les logos des partenaires ont été retirés en local (7), à renvoyer.
- **Motif** : un logo plus petit serait agrandi, donc flou, sur la carte de « Mes partenaires ».
- **Portée** : `Field`, `MinImageSide` et traductions du module Form ; `PartnerLogoService`, `PartnerForm` (app et Filament), traductions du module Partner.
- **Corollaires** : changer `PARTNER_LOGO_MINE_SIZE` (ou la plus grande conversion) change le minimum ; l'app ne vérifie pas les dimensions avant l'envoi : le refus vient du serveur, avec son message.
- **Tranché par** : humain
