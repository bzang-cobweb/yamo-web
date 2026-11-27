# `2026-11-27` — Vérification d'identité : une note, le pays et le type de la pièce

- **Type** : décision
- **Question posée** : « Dans le formulaire Identity verification (une note: we won't share this information),
  sélection du pays, sélection entre driving licence, passport or identity card, upload ».
- **Ce qui est retenu** (complète 2026-11-25-identification et 2026-11-25-identite-plusieurs-photos) :
  - le formulaire `auth.identity` commence par la note « Nous ne partageons pas ces informations. » (la note du
    premier champ), puis le pays qui a délivré la pièce (liste cherchable des pays actifs), le type de pièce (permis
    de conduire, passeport, carte d'identité), puis les photos, comme avant ;
  - les trois sont obligatoires ; le pays est prérempli par celui déjà choisi, sinon le pays du compte, sinon le pays
    courant ; le type par celui déjà choisi ;
  - stockage : `users.identity_country_id` (clé étrangère `countries`, vidée si le pays disparaît) et
    `users.identity_document_type` (`IdentityDocumentType`), écrits par `IdentityDocumentService::submit()` ;
  - back-office : l'onglet « Identification » de la fiche utilisateur montre le pays de délivrance et le type de pièce.
- **Décidé seul (mode nuit)** : le type est un choix en boutons radio (trois options) ; la note de l'état (à envoyer,
  en cours, identifié) reste la note du formulaire ; une nouvelle pièce envoyée remplace le pays et le type
  enregistrés (un seul couple par compte, même si plusieurs photos).
- **Portée** : module Auth (`IdentityForm`, `IdentityDocumentService`, `User`, `UserForm` Filament), aucune version de
  l'app.
- **Tranché par** : humain (demande), session de nuit pour le détail
