# `2026-11-19` — Âge minimum réglé au back-office

- **Type** : décision
- **Question posée** : « ajouter un âge limite dans le formulaire back-office de l'application. Si ce champ est renseigné alors le champ date de naissance devient obligatoire et requiert minimum l'âge défini. »
- **Réponse** : la demande elle-même, appliquée sans contrat (un réglage et une règle de validation).
- **Ce qui est retenu** :
  - réglage « Âge minimum » (`settings.minimum_age`, entier de 1 à 120, vide = pas de limite), section « Utilisateurs » de la page « Application » du back-office ;
  - renseigné : la date de naissance est obligatoire à l'inscription (étape 1) et dans « Informations personnelles », et doit donner au moins cet âge le jour même (règle `MinimumAge`) ; l'aide du champ rappelle l'âge (« Au moins 18 ans. ») ;
  - la date de naissance est définie une seule fois (`PersonalInfoForm::birthDateField()`), partagée par les deux formulaires.
- **Motif** : la demande de l'humain.
- **Portée** : `Setting`, `SettingResource`, `PersonalInfoForm`, `RegisterForm`, `Modules/Auth/app/Rules/MinimumAge.php` ; rien dans l'app mobile (formulaires décrits par le serveur).
- **Corollaires** : un compte existant sans date de naissance (ou trop jeune) devra la saisir pour enregistrer ses informations personnelles ; les comptes existants ne sont pas bloqués pour autant ; le formulaire utilisateur du back-office n'applique pas la règle (choix par défaut, à rouvrir si besoin).
- **Tranché par** : humain (demande) ; corollaires par défaut de l'agent
