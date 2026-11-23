Remplacée en partie par : 2026-11-24-partenaire-en-validation.md
Remplace en partie : 2026-11-24-partenaires-par-les-membres.md ; 2026-11-23-module-partenaires.md (le booléen « publié »).

# `2026-11-24` — Partenaires : un statut de publication

- **Type** : décision
- **Question posée** : « gérer les statuts de publication de partner avec un enum ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - `PartnerStatusEnum` à la place du booléen `published` : en attente de validation (`pending`), publié (`published`), refusé (`rejected`), archivé (`archived`) ; les données existantes : publié → `published`, sinon `pending` ;
  - seuls les publiés sont visibles dans l'app (selon leur campagne) ;
  - proposé ou modifié depuis l'app : en attente ;
  - au back-office, le statut se choisit dans l'onglet Campagne, s'affiche en badge et se filtre ;
  - dans « Mes partenaires », la pastille donne le statut ; publié, « Affiché » ou « Pas affiché » selon la campagne.
- **Motif** : la demande de l'humain ; « refusé » et « archivé » pour dire au membre où en est son partenaire.
- **Portée** : modèle `Partner`, `PartnerService`, `MyPartnerListing`, `PartnerForm` et `PartnerTable` du back-office ; migration `2026_11_24_110000_replace_partner_published_by_status`.
- **Corollaires** : aucun motif de refus n'est saisi pour l'instant.
- **Tranché par** : humain
