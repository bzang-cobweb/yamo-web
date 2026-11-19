- **Remplace en partie** : `2026-11-18-adresse-app-simplifiee.md`, `2026-11-18-suggestions-retirees-de-l-app.md` et `2026-11-18-suggestions-retirees-du-serveur.md` : les suggestions de rue reviennent dans l'app et sur le serveur (`Field::suggestions()`, `GET /v1/addresses/suggestions`), pour l'adresse d'une offre seulement ; le carnet d'adresses reste sans suggestions.

# `2026-11-19` — Contrat « adresse d'une offre : recherche par la rue », validé ; risque assumé

- **Type** : décision et risque assumé
- **Question posée** :
  - valider le contrat `specs/2026-11-19-adresse-offre-recherche.md` ;
  - avant cela : quelles adresses suggérer, et quels champs saisir ?
- **Réponse** : « Every address », « Street, postal code, city », puis « Approved ».
- **Ce qui est retenu** :
  - dans le formulaire d'un logement de l'app, l'adresse se saisit en trois champs : rue (avec suggestions), code postal (facultatif) et ville ; le pays est le pays courant ;
  - choisir une suggestion remplit le code postal et la ville ;
  - à l'enregistrement, une adresse de même empreinte (`fingerprint`) dans le pays est reprise ; sinon elle est créée ;
  - le carnet d'adresses du compte n'est plus utilisé, et rien n'y est ajouté ;
  - le champ texte générique accepte des suggestions servies par une route (`Field::suggestions()`).
- **Risque assumé** : les suggestions portent sur **toutes** les adresses du pays, y compris les adresses personnelles des carnets des membres. Un membre peut donc voir des rues réelles d'autres membres (rue, code postal, ville), sans nom ni compte. Je recommandais de s'en tenir aux adresses des offres ; l'humain a choisi toutes les adresses.
  - **Garde-fous** : un compte connecté, 3 caractères au moins, 10 résultats au plus, un nombre d'appels limité, et aucune donnée personnelle servie.
  - **Pour revenir en arrière** : il suffit de restreindre la requête aux adresses liées à une offre.
- **Motif** : la demande de l'humain ; réutiliser les adresses existantes.
- **Portée** : `OfferForm`, `OfferService`, module Location (route des suggestions), module Form (`Field::suggestions()`), app (champ texte).
- **Corollaires** : le back-office est inchangé (choix d'une adresse existante).
- **Tranché par** : humain
