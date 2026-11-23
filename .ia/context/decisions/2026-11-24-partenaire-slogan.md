# `2026-11-24` — Partenaire : un slogan

- **Type** : décision
- **Question posée** : « rajouter un slogan multilingue pas obligatoire ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** : `partners.slogan`, traduit et facultatif (120 caractères) ; au back-office, sous le nom, un onglet par langue ; dans l'app, à l'étape « Présentation » après le nom, dans la langue de saisie ; servi par l'API (`slogan`, `null` sans slogan) ; affiché sous le nom sur la page du partenaire, centré, en gris.
- **Motif** : la demande de l'humain.
- **Portée** : modèle `Partner`, `PartnerForm` (app et back-office), `PartnerService`, `PartnerResource` ; `PartnerDetailScreen` de l'app.
- **Corollaires** : le slogan n'apparaît ni sur l'accueil ni dans les listes.
- **Tranché par** : humain
