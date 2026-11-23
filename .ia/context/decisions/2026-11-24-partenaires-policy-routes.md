Remplace en partie : 2026-11-24-partenaires-par-les-membres.md (où vit la règle de visibilité d'un partenaire).

# `2026-11-24` — Toutes les routes de l'API passent par la policy ; audit des routes

- **Type** : décision
- **Question posée** : « partner policy pourquoi update renvoie true alors qu'il faut être partner user », « est-ce que les endpoints api de partners passent bien par viewAny, view, create, update… du partner policy », puis « oui, aligne la policy et les routes et vérifie toutes les routes api ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - `PartnerPolicy` porte toute la règle de l'app : `viewAny` (public), `view` (visible, ou, quel que soit le statut, pour un compte rattaché ; sinon 404, visiteur compris), `create` (tout compte), `update` (rattaché), `delete` (administrateur `is_admin`) ; `Partner::isVisible()` est la règle de `scopeVisible()` pour un partenaire chargé ;
  - dans l'app, le super administrateur suit la règle de tout compte pour voir, modifier et retirer (`before()`), comme `OfferPolicy` pour les avis et les favoris ; au back-office, il garde tous les droits ;
  - routes : `can:viewAny` sur le carrousel ; la page et le clic appellent `view` dans le contrôleur avec le compte Sanctum facultatif (`Gate::forUser`), comme la fiche d'une offre ; le formulaire de modification appelle `update` dans `forRecord()` ; `PartnerService::find()` est supprimé ;
  - audit de toutes les routes `api/` : chaque route qui agit sur un modèle passe par une policy ou une capacité, sur la route (`can:`) ou dans le contrôleur (`Gate::forUser`, routes publiques avec compte facultatif : fiche et avis d'une offre, page et clic d'un partenaire) ; la page d'un membre reçoit la capacité `viewMember` (tout compte connecté, décision 2026-11-19-page-membre) et sa garde `can:` ;
  - restent sans policy, par conception : les parcours de connexion et d'inscription, les données de référence (pays, langues, textes, application), l'accueil, les suggestions d'adresses ; les notifications, limitées par la requête aux siennes (`$user->notifications()`, 404 sinon) ; la documentation `docs/api`, réservée à l'environnement local par Scramble.
- **Motif** : la règle des routes du projet (« chaque route qui agit sur un modèle porte la garde de sa policy ») ; un seul domicile par règle.
- **Portée** : `PartnerPolicy`, `Partner`, `PartnerController`, `PartnerForm`, routes du module Partner ; capacité `viewMember` et route des membres (module Offer).
- **Corollaires** : la page d'un membre reste ouverte à tout compte connecté, sans limite de rôle ni de pays ; la restreindre demanderait une décision.
- **Tranché par** : humain
