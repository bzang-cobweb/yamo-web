Remplacée en partie par : 2026-11-24-partenaires-policy-routes.md, 2026-11-24-mes-partenaires-cartes.md, 2026-11-24-partenaires-statuts.md
# `2026-11-24` — Les membres proposent et gèrent leurs partenaires

- **Type** : décision
- **Question posée** : « comment un utilisateur peut ajouter un partenaire ? est-ce qu'on permet qu'il puisse en ajouter plusieurs ? » → « option 2 » (les membres gèrent leurs partenaires), puis « prévoir un partenaire peut avoir plusieurs users qui l'administrent », « rajouter un boolean is_admin dans partner_user, l'utilisateur qui crée en premier est is_admin true », « l'utilisateur qui n'est pas partenaire ne peut pas supprimer le partenaire ».
- **Réponse** : les demandes elles-mêmes.
- **Ce qui est retenu** :
  - table `partner_user` (`is_admin`) : plusieurs comptes par partenaire, plusieurs partenaires par compte ; celui qui propose est administrateur ;
  - dans l'app, « Mes partenaires » (groupe « Partenaire » du profil, liste `partner.mine`) : « Proposer un partenaire » ouvre le formulaire `partner.partner` (nom, logo, description, lien, libellé du bouton, contact), dans la langue du compte, pour le pays courant ;
  - un partenaire proposé n'est pas publié ; toute modification depuis l'app le repasse en attente : l'équipe valide avant d'afficher ; statuts « En attente de validation », « Affiché », « Pas affiché » ;
  - les comptes rattachés modifient ; seuls les administrateurs retirent (403 sinon, sans bouton « Retirer ») ; pour un compte étranger, le partenaire n'existe pas (404) ;
  - au back-office : onglet « Administrateurs » (rattacher, détacher, faire administrateur), filtre « Proposé depuis l'app » ;
  - `User::partners()` est déclarée par le module Partner (`resolveRelationUsing`), le module Auth ne connaît pas les partenaires.
- **Motif** : les demandes de l'humain ; la validation par l'équipe parce qu'un partenaire s'affiche sur l'accueil et se paie.
- **Portée** : module `Partner` (`PartnerForm`, `MyPartnerListing`, `MyPartnerController`, `PartnerLogoService`, `PartnerPolicy`, `UsersRelationManager`) ; menu du profil.
- **Corollaires** : rattacher un autre compte se fait au back-office seulement ; pas encore d'invitation depuis l'app, ni de notification à l'équipe quand un partenaire est proposé ; le paiement reste hors de l'app.
- **Tranché par** : humain
