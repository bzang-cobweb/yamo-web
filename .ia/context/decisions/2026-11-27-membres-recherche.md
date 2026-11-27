# `2026-11-27` — Membres d'un partenaire : une recherche

- **Type** : décision
- **Question posée** : « ajouter la recherche » (sur la page des membres d'un partenaire).
- **Ce qui est retenu** : la liste des membres (`PartnerMembers`, donc la page `/partners/{id}/members` et le panneau « Membres » d'une notification) a le champ de recherche des listes (`List`, le même partout) : « Rechercher un membre ». La recherche se fait dans l'app, sur le nom, l'e-mail et le téléphone, sans accents ni majuscules ; rien trouvé : « Aucun membre trouvé. ». Le nombre de comptes en tête reste celui du partenaire.
- **Motif** : tous les membres arrivent déjà avec la fiche du partenaire ; pas besoin d'aller-retour serveur pour quelques comptes.
- **Corollaire** : si un partenaire devait compter des centaines de comptes, la liste passerait par une liste paginée du serveur.
- **Portée** : mobile `PartnerMembers` ; web : textes `partners.members_search` et `partners.members_none` (fr, en, de).
- **Tranché par** : humain
