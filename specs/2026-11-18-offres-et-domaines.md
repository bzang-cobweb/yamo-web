# Module Offer et profil de domaine — socle et premier domaine (location de logements)

Demande du 2026-11-18 :

> ajouter un module Product qui définit un Model Product. Le but ici est qu'en fonction du domaine (site de rencontre, recherche de meublé ou de logement à louer, shop, ...) on puisse définir une skill qui initialise le projet selon le bon domaine.
>
> yes, utilise donc le nom Offer partout et non Product.

## Ce que j'ai compris

Un seul code. Le domaine du projet (location, boutique, rencontre…) est un **profil déclaré en code**, qui adapte l'offre et les écrans servis à l'app sans toucher au socle. Ce chantier pose le socle et **un premier domaine, la location de logements**, qui valide le mécanisme. La commande d'initialisation et le skill viendront au chantier suivant, une fois le mécanisme éprouvé.

### Module `Offer`

**Table `offers`**

| Colonne | Type |
|---|---|
| `id` | clé |
| `user_id` | propriétaire. Clé étrangère ; une offre est supprimée avec le compte purgé |
| `category_id` | catégorie, nullable |
| `address_id` | adresse partagée (module Location), nullable ; supprimée avec elle → `null` |
| `title` | texte, 255 |
| `description` | texte long, nullable |
| `status` | `draft`, `published` ou `archived` (énumération `OfferStatusEnum`) |
| `price` | décimal (12,2), nullable |
| `currency` | 3 lettres, nullable |
| `attributes` | JSON : attributs propres au domaine |
| `published_at` | date, nullable |
| horodatage | |
| suppression douce | |

- **Table `offer_categories`** :
  - un nom traduit (fr, en) ;
  - un slug unique ;
  - une position, pour l'ordre d'affichage.
- **Photos** : une collection `photos`, gérée par spatie/medialibrary (déjà installé). Dans ce chantier, on ne les gère que dans le back-office.

### Profil de domaine

- **L'interface `Modules\Offer\Domains\Domain`** déclare :
  - le libellé de l'offre, au singulier et au pluriel, traduit (« Logement », « Logements ») ;
  - le **schéma des attributs** : une liste de `Field` du module Form (type, libellé, bornes, options). Ce même schéma produit le formulaire de l'app, la validation de l'API et les champs Filament ;
  - les **capacités** activées : prix, adresse, un seul produit par compte ;
  - les **catégories de départ** ;
  - les **permissions de base du rôle `member`** sur les offres.
- **Le domaine actif est choisi par la configuration** `config('offer.domain')`, une clé de domaine.
  - Un domaine inconnu est une erreur explicite au démarrage, jamais un domaine par défaut silencieux.
  - Sans domaine choisi, le module Offer n'expose rien.
- **Les domaines sont inscrits dans un registre** (`DomainRegistry`), comme les formulaires et les listes.
- **Premier domaine : `rental`** (location de logements, meublés ou non).
  - Attributs :
    - type de logement : appartement, maison, studio, chambre ;
    - meublé : oui ou non ;
    - surface (m²), nombre de pièces, nombre de chambres ;
    - date de disponibilité ;
    - loyer mensuel en prix ; charges.
  - Capacités : prix et adresse obligatoires.
  - Catégories : location longue durée, location meublée, colocation.

### Permissions et rôles

- `PermissionEnum` gagne `offers.view`, `offers.create`, `offers.update` et `offers.delete`. Ce sont les droits du back-office ; le rôle `admin` les reçoit.
- **Dans l'app** :
  - un compte connecté gère **ses** offres. L'offre d'un autre compte répond 404, comme pour les adresses ;
  - créer une offre exige que le domaine le permette au rôle `member`. Pour `rental`, c'est permis.
- **`OfferPolicy`** suit le modèle de `UserAddressPolicy`.

### API et app (aucun changement de l'app)

- **Menu du profil** : un lien vers la liste **« Mes logements »** (`offer.mine`). Son libellé vient du domaine.
- **Liste** `offer.mine` (liste générique) :
  - titre, statut et prix ;
  - recherche dans le titre ;
  - lien de création, édition et retrait.
- **Formulaire** `offer.offer` (formulaire générique) :
  - titre, description, catégorie, prix, adresse (choisie parmi les adresses du compte), statut ;
  - puis les attributs du domaine.
- **Routes** :
  - `POST /v1/offers` ;
  - `PATCH /v1/offers/{offer}` ;
  - `DELETE /v1/offers/{offer}` : suppression douce.
- Publier une offre fixe `published_at`.

### Back-office

- **`OfferResource`** :
  - **liste** : filtres par statut, catégorie et propriétaire ;
  - **formulaire** :
    - les champs communs ;
    - les attributs du domaine, construits depuis le même schéma ;
    - les photos.
- **Les catégories** se gèrent dans le back-office, sous les permissions `offers.*`.

## Critères de succès

1. Avec `offer.domain = rental`, un membre crée, modifie et retire ses logements depuis l'API. Les attributs sont validés par le schéma du domaine : une surface négative ou un type inconnu sont refusés sous leur champ.
2. Un compte ne voit ni ne modifie l'offre d'un autre compte (réponse 404).
3. Le formulaire servi à l'app contient les champs communs puis les attributs du domaine, avec leurs libellés traduits. Le menu et la liste portent le libellé du domaine.
4. Un second domaine **de test** (`test.shop`, déclaré dans les tests seulement) change le formulaire, la validation et les libellés sans qu'une ligne du socle ne change. C'est la preuve du mécanisme.
5. Sans domaine choisi, aucune route, aucun menu ni aucune liste « offres » n'existe. Un domaine inconnu lève une erreur claire.
6. Dans le back-office, un admin gère toutes les offres, leurs photos et les catégories. Un compte sans `offers.*` n'y a pas accès.
7. Les tests web passent, Pint est propre et `openapi.json` est à jour. Les tests mobiles passent, sans modification de l'app.

## Hors périmètre (chantiers suivants)

- La commande `project:init` et le skill d'initialisation.
- Les domaines boutique et rencontre.
- Les modules optionnels Booking, Order et Messaging.
- La consultation publique des offres dans l'app : liste de toutes les offres, fiche détaillée, recherche par critères.
- L'ajout de photos depuis l'app : il faudrait un type de champ « fichier » dans les formulaires génériques.
- Les colonnes indexées sur les attributs pour la recherche.
