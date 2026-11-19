# RETEX — informations d'une offre (offer_informations, champ repeater)

- **Ordre des données validées** : pour un tableau de groupes validé par jokers (`informations.*.id`, `.title`), `validated()` garde les indices mais pas toujours l'ordre (un groupe qui a un `id` remonte). Règle candidate pour le projet : toute liste ordonnée venant de `validated()` se trie par indice (`ksort`) avant usage.
- **Jest** : une fonction utilisée dans une fabrique `jest.mock()` doit commencer par `mock` ; un nouveau test qui importe la configuration doit simuler `@/shared/config`.
- **Écran resté ouvert** : un formulaire ouvert avant le changement a montré un bloc vide ; toujours rouvrir l'écran à neuf avant de juger un rendu.
- **Scramble** : une liste construite par `collect()->map()` sort en `UnknownType` ; une méthode à type de retour documenté règle le problème (déjà vu pour `next_cursor`) — deuxième fois : règle candidate, « une valeur de ressource se construit dans une méthode typée ».
- **Règle domiciliée** : « une liste de groupes de sous-champs » a maintenant un type générique (`repeater`) dans le module Form et dans l'app, réutilisable hors des offres.
- **Mineur QA traité** : test de la migration (recopie, retrait de `details`, retour arrière). Non traité : `down()` ne remet que le premier bloc (accepté, retour arrière de secours).
