# `2026-11-27` — E-mail et téléphone masqués partout où ils s'affichent

- **Type** : décision
- **Question posée** : « Cacher une partie de l'email partout où on affiche l'email (identique pour le numéro de
  téléphone) » ; « cacher une partie de l'email (du téléphone) à l'édition ».
- **Ce qui est retenu** :
  - un seul domicile du masquage, `Modules\Auth\Support\Masks` (`email()`, `phone()`, `destination()`), qui remplace
    `VerificationCodeService::mask()` / `maskDestination()` (retirées) : e-mail « j•••@example.com », numéro « +41 ••• 67 »
    (même format que la destination d'un code, déjà en place) ;
  - `GET /auth/me` (`UserResource`) sert en plus `email_masked`, `phone_masked`, `email_verified`, `phone_verified` ;
    l'app (`ProfileScreen`) affiche les versions masquées ; `email` et `phone` restent servis (le compte lui-même) ;
  - « Changer d'adresse e-mail » et « Changer de numéro » montrent la valeur actuelle masquée, en lecture seule (un champ
    texte ; le numéro n'est plus un champ téléphone avec pays) ;
  - membres d'un partenaire (`PartnerResource::members`) : `email_masked`, `phone_masked` affichés par l'app ;
  - contact d'une demande (`BookingThreadDetailsService`, panneau « Info » d'une notification de demande) : le téléphone
    et l'e-mail affichés masqués, chacun avec un lien `tel:` / `mailto:` ; l'app (`SummaryLineRow`) les ouvre par le
    système.
- **Décidé seul (mode nuit)** :
  - quand un lien d'action (appeler, écrire) a besoin de la vraie valeur, elle reste dans le lien (`tel:`, `mailto:`,
    ou la valeur brute servie à côté, pour les membres d'un partenaire) et seul le texte est masqué : masquer sans lien
    rendrait le contact inutilisable ;
  - le contact de l'offre (`offers.phone`, `offers.email`) est masqué lui aussi dans ce panneau, comme celui de l'hôte
    dont il prend la place ;
  - les coordonnées de contact d'un partenaire (`contact_email`, `contact_phone` de sa page) ne sont pas celles d'un
    compte : non masquées ;
  - la recherche parmi les membres d'un partenaire porte toujours sur les vraies valeurs ;
  - la valeur par défaut du champ « e-mail de contact » d'un partenaire (l'e-mail du compte, modifiable) n'est pas un
    affichage : non masquée.
- **Hors périmètre** : le back-office (Filament) montre les valeurs entières.
- **Portée** : module Auth (`Masks`, `UserResource`, formulaires de changement), Partner (`PartnerResource`), Offer
  (`BookingThreadDetailsService`), app (`ProfileScreen`, `PartnerMembers`, `SummaryLines`).
- **Tranché par** : humain (demande), session de nuit pour le détail
