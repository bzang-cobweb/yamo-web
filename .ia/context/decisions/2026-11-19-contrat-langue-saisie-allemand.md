Remplace en partie : 2026-11-18-offres-multilingues-categories-arbre.md (ordre de repli des textes traduits ; titre obligatoire dans la langue par défaut au back-office).

# `2026-11-19` — Contrat « langue de saisie d'une offre, et l'allemand », validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-19-langue-saisie-allemand.md`, après discussion (pas d'étape à la création ; une étape « Langue » à l'édition ; pré-remplir une langue manquante avec le texte d'origine plutôt que recopier) ; précision : l'étape n'apparaît que si l'app propose plusieurs langues (réponse « Dans l'app »).
- **Réponse** : « Approved » (autres défauts retenus).
- **Ce qui est retenu** :
  - une offre retient sa langue d'origine (`offers.locale`) ; un texte manquant s'affiche dans la langue d'origine, puis la langue par défaut, puis une autre ;
  - création : la langue du compte, rien n'est recopié ;
  - édition : étape 1 « Langue » si l'app a plusieurs langues (état de chaque langue : d'origine, traduite, à traduire) ; la langue choisie recharge le formulaire, une langue manquante est pré-remplie avec le texte d'origine ; enregistrer ne remplace que cette langue ; confirmation avant de perdre des textes en changeant de langue ;
  - mécanisme générique du module Form : champ qui recharge le formulaire (`reloads`) ;
  - back-office : titre exigé dans au moins une langue ;
  - l'allemand (`de`) rejoint les langues de l'app, textes rédigés par la session (relecture conseillée) ; pas de traduction automatique ;
  - offres existantes : langue d'origine = français s'il existe, sinon la première renseignée.
- **Motif** : la demande de l'humain ; des traductions vraies plutôt que des copies qui vieillissent.
- **Portée** : modules Form et Offer, `Locales`, fichiers `lang/de` ; app (formulaire).
- **Corollaires** : la langue par défaut reste le français.
- **Tranché par** : humain
