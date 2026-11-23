Remplacée en partie par : 2026-11-23-partenaire-pays-dans-contact.md
Remplace en partie : 2026-11-23-module-partenaires.md (le nom, le formulaire).

# `2026-11-23` — Partenaires : le nom traduit, le formulaire en onglets

- **Type** : décision
- **Question posée** : « finalement le nom multilingue et utiliser des tabs pour le formulaire filament ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - le nom est traduit, comme la description, le lien et le libellé du bouton ; obligatoire dans la langue par défaut ; les noms existants deviennent ceux de la langue par défaut (migration `2026_11_23_210000_translate_partner_names`) ;
  - le formulaire du back-office en onglets : Partenaire (pays, logo, puis par langue le nom, la description, le lien et le bouton), Campagne, Contact, Statistiques (en modification) ;
  - la recherche de la liste porte sur le nom dans toutes ses langues.
- **Motif** : la demande de l'humain.
- **Portée** : modèle `Partner`, `PartnerForm`, `PartnerTable`, `PartnerResource` de l'API.
- **Corollaires** : l'API sert le nom dans la langue de l'app.
- **Tranché par** : humain
