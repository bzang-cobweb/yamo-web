# `2026-11-27` — Contact d'urgence : un par compte, dans « Informations personnelles »

- **Type** : décision
- **Question posée** : « Menu profile ajouter un lien emergency contact (name required, relationship, email, phone
  required, language required) ».
- **Ce qui est retenu** :
  - lien « Contact d'urgence » dans « Informations personnelles », après l'adresse de résidence, icône
    `cross.case` / `emergency` ;
  - formulaire `auth.profile.emergency-contact` (`PUT /api/v1/auth/me/emergency-contact`) : nom (obligatoire), lien
    de parenté (texte libre), e-mail (facultatif), téléphone (pays + numéro, obligatoire), langue (obligatoire, parmi
    les langues de l'app, `Locales::options()`) ; prérempli par le contact enregistré ;
  - stockage : table `emergency_contacts` (un par compte, `user_id` unique, supprimé avec le compte), modèle
    `EmergencyContact`, `User::emergencyContact()` ; téléphone en E.164 avec le pays de l'indicatif
    (`PhoneNumbers`, comme le numéro du compte), créé ou modifié par `EmergencyContactService`.
- **Décidé seul (mode nuit)** :
  - le pays du téléphone est le pays courant d'office, la langue celle du compte ;
  - le numéro n'est pas vérifié par un code (c'est celui d'un tiers) ;
  - un lien de parenté et un e-mail vides sont enregistrés comme absents ;
  - pas encore de lecture au back-office ni de suppression depuis l'app (le formulaire remplace).
- **Portée** : module Auth (`ProfileMenuKey`, `ProfileMenuService`, `EmergencyContactForm`, contrôleur, service,
  modèle, migration), aucune version de l'app.
- **Tranché par** : humain (demande), session de nuit pour le détail
