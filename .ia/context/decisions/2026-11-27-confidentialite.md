# `2026-11-27` — « Confidentialité » : visibilité du profil, avis, données, suppression du compte

- **Type** : décision
- **Question posée** : « Menu profile ajouter un lien privacy : Profile visibility — choose who can see your profile
  (switch comme quand on edit un partner_user is_admin) : anyone on yamo / propriétaires / connections / propriétaires
  et connections ; reviews : show my country, region & city ; data : request my personal data ; delete my account
  (formulaire avec un message obligatoire et envoi de code pour valider) ».
- **Ce qui est retenu** :
  - sous-menu « Confidentialité » : « Visibilité du profil », « Avis », « Mes données », « Supprimer mon compte » ;
  - réglages dans `users.privacy_settings` (JSON, vide = défauts), clés et défauts dans
    `Modules\Auth\Support\PrivacySettings`, `User::privacySettings()` ;
  - **Visibilité du profil** (`auth.profile.privacy.visibility`, `PATCH /auth/me/privacy/visibility`) : un choix unique
    parmi tout le monde (défaut), propriétaires, mes contacts, propriétaires et mes contacts. Appliquée par la garde
    `viewMember` de `GET /members/{id}` (`Modules\Offer\Support\MemberVisibility`) : refusé → **404** ;
  - **Avis** (`auth.profile.privacy.reviews`, `PATCH /auth/me/privacy/reviews`) : un interrupteur « Montrer mon pays,
    ma région et ma ville ». Autorisé : `author_location` (« Ville, Région, Pays », ce qui est connu) sous le prénom
    de l'auteur, sur la fiche et dans la liste des avis (avant la date) ;
  - **Mes données** (`auth.profile.privacy.data`, `POST /auth/me/personal-data`, 3 par heure) : un JSON des données du
    compte (compte, adresses, contact d'urgence, réglages ; jamais mot de passe ni jetons) part en pièce jointe à
    l'e-mail **vérifié** ; sans e-mail vérifié : 422 avec un message clair ;
  - **Supprimer mon compte** : plus de mot de passe (un compte sans mot de passe ne pouvait pas supprimer son compte) ;
    une raison obligatoire (textarea), puis un code (`VerificationPurpose::DELETE_ACCOUNT`) à l'e-mail vérifié, sinon au
    numéro vérifié ; sa validation supprime le compte comme avant (suppression douce, purge après le délai) et rend le
    compte ; l'app affiche le message et se déconnecte (suite `sign-out`). La raison est gardée dans
    `account_deletion_reasons` (`user_id` remis à vide à la purge).
- **Décidé seul (mode nuit)** :
  - « switch » pour un choix exclusif parmi quatre : **boutons radio** (un seul choix possible, sans état incohérent) ;
  - « propriétaires » = le visiteur a au moins une offre, quel que soit son état ;
  - « contacts » = le visiteur et le membre ont une demande en commun (l'un a demandé une offre de l'autre, tout état)
    ou un fil de messages sur une offre de l'un d'eux (notification `offer_message:<offre>:<membre>`) ;
  - le membre voit toujours sa propre page ; un profil non visible répond 404 (pas de page réduite) ;
  - « Avis » non par défaut (protection des données par défaut) ; la ville est celle de la première adresse, comme sur
    la page publique ;
  - « Mes données » : export immédiat par e-mail plutôt qu'une demande traitée par l'équipe (plus simple, aucun écran
    à ajouter au back-office) ; il ne couvre que le module Auth : offres, demandes, avis, messages n'y sont pas ;
  - code de suppression : à défaut d'e-mail ou de numéro vérifié, l'e-mail puis le numéro du compte tels quels (le
    compte connecté s'est connecté par un code) ; ni l'un ni l'autre : 422 ;
  - la raison n'est montrée nulle part au back-office (table seulement) ;
  - texte de l'avertissement : « reconnectez-vous pour le récupérer » (au lieu de « contactez-nous »), ce que fait
    déjà la connexion d'un compte en attente de suppression.
- **Portée** : module Auth (menu, formulaires, services, migrations `2026_12_08_120000` et `121000`), Offer (garde
  `viewMember`, avis), app (lieu sous un avis ; aucun écran nouveau : formulaires génériques).
- **Tranché par** : humain (demande), session de nuit pour le détail
