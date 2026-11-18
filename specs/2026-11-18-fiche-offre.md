# Fiche détaillée d'une offre

Demande du 2026-11-18 :

> faire la fiche détaillée d'une offre

## Ce que j'ai compris

Toucher une offre de l'accueil ouvre sa fiche, pour un visiteur comme pour un membre. Comme les formulaires et les listes, **la fiche est décrite par le serveur** : l'app l'affiche sans connaître le domaine. Une fiche de logement ou une fiche d'article de boutique s'affichent avec le même écran.

### Serveur

- `GET /api/v1/offers/{offer}`, **ouvert sans connexion**.
  - **Visibilité** : seules les offres **publiées** du **pays courant** sont visibles. Un brouillon, une offre archivée, retirée ou d'un autre pays répondent 404.
  - **Exception pour le propriétaire connecté** : il voit aussi sa propre offre en brouillon ou archivée, avec son statut. Il peut ainsi vérifier le rendu avant de publier.
- **La réponse décrit la fiche, dans la langue de la requête :**
  - `title` : le titre ;
  - `price` : le prix formaté (« 250 000 FCFA »), avec son libellé de domaine (« Loyer mensuel »), ou `null` ;
  - `location` : la ville, et le quartier s'il est connu. La rue n'est **pas** servie : l'adresse exacte reste privée ;
  - `photos` : toutes les photos, dans l'ordre, servies sur l'hôte de la requête ;
  - `description` : la description, ou `null` ;
  - `facts` : une liste de lignes { libellé, valeur } construite depuis le **schéma du domaine** :
    - la catégorie ;
    - chaque attribut renseigné, avec sa valeur lisible : « Appartement », « Oui », « 64 m² », « 3,5 », date au format local ;
    - les attributs vides sont omis ;
  - `published_at` : la date de publication ;
  - `status` : le statut, servi seulement au propriétaire.
- **Format des valeurs, déduit du type de `Field`** :
  - un choix : son libellé ;
  - un oui/non : « Oui » ou « Non » ;
  - un nombre : formaté selon la langue ;
  - une date : au format local ;
  - un texte traduisible : dans la langue courante.

  Un domaine peut ajouter une unité à un attribut (`->suffix('m²')` sur le `Field`, servi aussi au formulaire).
- **Documentation** : la réponse est documentée dans `openapi.json`.

### App

- **Toucher une offre de l'accueil** ouvre la fiche (`/offers/[id]`), pour un visiteur comme pour un membre.
- **Écran de la fiche** :
  - **photos** en carrousel horizontal, en pleine largeur et au format 16:9, avec un indicateur de position (« 1 / 2 ») ; sans photo, un emplacement neutre ;
  - **puis** :
    - le titre ;
    - le prix ;
    - la localisation ;
    - les caractéristiques, en lignes libellé – valeur ;
    - la description ;
    - la date de publication ;
  - **en-tête** : un bouton retour vers l'accueil.
- **Erreurs** :
  - une offre introuvable (404) affiche « Cette offre n'est plus disponible » et le retour ;
  - hors ligne, l'écran hors ligne habituel s'affiche.
- **Membre** : « Mes logements » ouvre toujours le **formulaire**. La fiche ne s'y ouvre pas, car modifier reste l'action principale du propriétaire.

### Hors périmètre

- Contacter le propriétaire (messagerie, téléphone) : chantier à venir.
- Favoris et partage.
- Carte.
- Agrandir une photo en plein écran.

## Critères de succès

1. Depuis l'accueil, toucher un logement de démonstration ouvre sa fiche. On y voit :
   - ses deux photos, que l'on fait défiler ;
   - le titre, « 250 000 FCFA » et « Loyer mensuel » ;
   - « Douala » ;
   - les caractéristiques lisibles : type, meublé, surface en m², pièces, chambres, disponibilité, charges ;
   - la description.
2. Pour un visiteur, une offre en brouillon, archivée ou d'un autre pays répond 404, et l'app affiche le message. Le propriétaire connecté voit son brouillon, avec son statut.
3. La rue n'apparaît jamais dans la réponse.
4. Un domaine de test, la boutique, sert une fiche avec ses propres caractéristiques, sans changer le socle ni l'app.
5. Les textes suivent la langue : le titre, les libellés et les valeurs sont en anglais pour une requête en anglais.
6. Tests :
   - web : les tests passent, Pint est propre, `openapi.json` est à jour ;
   - mobile : les tests passent (fiche, carrousel, 404, ouverture depuis l'accueil), tsc et le lint sont propres.
