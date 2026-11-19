Remplacée en partie par : 2026-11-19-etapes-formulaire-reordonnees.md (le contenu des étapes)
# `2026-11-19` — Formulaire de logement par étapes, unités dans l'aide, symbole de devise : validé

- **Type** : décision
- **Question posée** : valider le contrat `specs/2026-11-19-formulaire-logement-etapes.md`.
- **Réponse** : « transformer XAF en FCFA et toujours appliquer ce principe pour toutes les monnaies et approuver ».
- **Ce qui est retenu** :
  - le contrat, tel qu'écrit : le formulaire de l'app en 4 étapes déclarées par le domaine, et l'unité dans la ligne d'aide ;
  - en plus, une règle générale : partout où une devise s'affiche à une personne (unité d'un champ, prix, charges), on montre son **symbole local** (via ICU, dans la langue de la requête), jamais son code ISO. Exemples : XAF → « FCFA », EUR → « € » ;
  - le code ISO reste ce qui est enregistré en base et échangé par l'API (`currency`).
- **Motif** : un Camerounais lit « FCFA », pas « XAF ».
- **Portée** : tout affichage d'une devise, dans l'app comme dans le back-office.
- **Corollaires** :
  - une seule fonction donne le symbole (`App\Support\Currency::symbol()`) ; aucun écran ne compose « XAF » lui-même ;
  - les prix formatés par `Number::currency` donnent déjà le symbole.
- **Tranché par** : humain
