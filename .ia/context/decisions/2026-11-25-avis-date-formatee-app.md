Remplacée par : 2026-11-25-dates-formatees-serveur.md

# `2026-11-25` — La date d'un avis formatée par l'app, la même partout

- **Type** : décision
- **Question posée** : « pour les reviews les dates doivent être identiques donc format côté app ».
- **Ce qui est retenu** :
  - **Pour les avis, partout** : le serveur sert la date brute (ISO 8601), et l'app la formate dans la langue et le fuseau du téléphone, au format court (« 18 nov. 2026 »).
    - Une seule fonction : `src/shared/i18n/reviewDate.ts`.
    - Sur la fiche d'une offre : le carrousel des avis.
    - Dans la liste des avis : les cartes au `layout: review`, à partir du champ `date` de l'item. Le serveur ne sert plus de texte de date (`detail`) pour ces cartes.
  - **Pourquoi** : la fiche formatait dans le fuseau du téléphone et la liste dans celui du serveur (UTC). Un avis écrit vers minuit n'avait pas la même date sur les deux écrans.
  - **Les autres dates** (« Disponible dès le », « Membre depuis ») restent formatées par le serveur.
- **Tranché par** : humain
