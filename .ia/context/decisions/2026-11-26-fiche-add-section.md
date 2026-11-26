# `2026-11-26` — Les sections de la fiche construites groupe par groupe (`addSection`)

- **Type** : décision
- **Question posée** : « OfferDetailResource ligne 100, il faut écrire une function addSection et construire le tableau de section. Ça évite de passer tous ces paramètres et favorise l'ajout d'une nouvelle section ou la suppression d'une. » Puis : « boucler en lisant le group dans la config et faire un match(group) ».
- **Ce qui est retenu** :
  - `toArray()` parcourt `offer.sections` (groupe → ouverture) et, pour chaque groupe, un `match` appelle `addSection($key, $type, $open, $content, $title = null)` ; un contenu `null` n'ajoute rien ;
  - les blocs d'informations passent par `addInformations()` : une section `text` chacun, clé `information-{id}`, son titre, l'ouverture du groupe `informations` ;
  - l'ordre est celui de la boucle ; `sections()` ne prend plus de paramètres et ouvre seulement la première section si aucune ne l'est ;
  - la réponse de l'API ne change pas (sections comparées avant et après sur deux offres).
- **Motif** : ajouter ou retirer une section touche un seul endroit, sans allonger une liste de paramètres.
- **Portée** : `OfferDetailResource`.
- **Corollaires** : une nouvelle section = sa clé dans `offer.sections` et un bras du `match` ; un groupe de la config sans bras n'ajoute rien (`default`). Son titre par défaut est `offer::offer.section_titles.<clé>`.
- **Tranché par** : humain (la méthode, la boucle sur la config avec `match`) ; session (la signature, `addInformations`)
