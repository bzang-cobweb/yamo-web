# Offres multilingues et catégories en arbre

Demande du 2026-11-18, après le chantier `2026-11-18-offres-et-domaines` :

> 1. la traduction d'une offre, categorie, ... pour le multilingue (details peut avoir des propriétés traduisible)
> 2. les catégories structurer en arbre et la possibilité de les trier

## Ce que j'ai compris

### 1. Traductions

Les langues sont celles de l'application (`config('app.supported_locales')`, aujourd'hui fr et en).

- **Offre** : `title` et `description` deviennent traduisibles, en JSON par langue, avec spatie/laravel-translatable (déjà utilisé pour les pays et les catégories).
  - Une migration convertit les offres existantes : le texte actuel est rangé sous la langue par défaut (`Locales::default()`).
- **Catégories** : le nom est déjà traduit. Rien ne change, sinon l'arbre (point 2).
- **Attributs du domaine (`details`)** : un `Field` peut être marqué **traduisible** (`->translatable()`), seulement pour le texte court et le texte long.
  - Sa valeur est alors rangée par langue dans `details` (`{"note": {"fr": "…", "en": "…"}}`).
  - Les autres attributs (nombres, choix, dates) gardent une seule valeur.
- **Saisie dans l'app (recommandé)** :
  - un membre écrit **dans la langue de son compte**. Le formulaire envoie un seul texte par champ traduisible, rangé sous cette langue ;
  - les autres langues déjà saisies, par exemple dans le back-office, sont conservées ;
  - le formulaire est prérempli avec le texte de la langue du compte, ou à défaut celui de la langue de repli.
- **Lecture** : l'app reçoit le texte dans la langue du compte. S'il manque, elle reçoit celui de la langue par défaut, puis de n'importe quelle langue disponible : jamais de vide quand un texte existe.
- **Back-office** :
  - chaque champ traduisible a un onglet par langue (`TranslatableTabs`, décision 2026-11-19-traduction-par-champ-filament) ;
  - seule la langue par défaut est obligatoire.
- **Recherche dans « Mes offres »** : elle porte sur le titre, toutes langues confondues.
- **Domaine `rental`** : aucun attribut traduisible n'y a de sens aujourd'hui. Le mécanisme est prouvé par le domaine de test, qui en déclare un.

### 2. Catégories en arbre, triables

- **`offer_categories` gagne `parent_id`**, nullable et lié à elle-même. Une catégorie a un parent ou aucun, ce qui fait d'elle une racine. La profondeur est libre.
  - **Les boucles sont refusées** : une catégorie ne peut pas devenir l'enfant d'elle-même ou d'un de ses descendants.
- **`position` ordonne les catégories sœurs**, celles qui ont le même parent.
- **Une offre peut être rangée dans n'importe quelle catégorie de l'arbre**, nœud ou feuille.
- **Supprimer une catégorie** qui a des sous-catégories est **refusé**, avec un message. Les offres d'une catégorie supprimée perdent leur catégorie, comme aujourd'hui.
- **Back-office** :
  - la liste montre les **catégories racines**, triables par glisser-déposer ;
  - la fiche d'une catégorie a un onglet **« Sous-catégories »**, lui aussi triable par glisser-déposer, d'où l'on crée, modifie et supprime ses enfants. On descend ainsi dans l'arbre ;
  - le parent se choisit ou se change dans le formulaire d'une catégorie ;
  - le fil d'Ariane affiche le chemin (« Location › Meublée »).
- **App et filtres** : le choix de catégorie liste l'arbre dans l'ordre, avec le chemin complet en libellé (« Location longue durée › Studio »). Il en va de même pour le filtre et le formulaire du back-office.
- **Domaine** : `Domain::categories()` peut déclarer des enfants (`children`). Le seeder crée l'arbre et reste rejouable : il ne recrée ni ne déplace une catégorie existante.

## Critères de succès

1. Une offre créée depuis l'app par un compte anglophone a son titre en `en`. Lue par un compte francophone, elle montre ce titre tant qu'il n'existe pas de version `fr`.
2. Une traduction ajoutée dans le back-office n'est pas effacée quand le membre modifie son offre dans sa langue.
3. Dans un domaine de test, un attribut traduisible est rangé par langue, relu dans la bonne langue, et saisi par onglets dans le back-office.
4. Les offres existantes sont converties sans perte. Le retour en arrière de la migration redonne un texte simple.
5. Un arbre de trois niveaux se crée, se trie et se réorganise dans le back-office. Une boucle et la suppression d'un parent sont refusées avec un message.
6. Le choix de catégorie, dans l'app comme dans le back-office, suit l'ordre de l'arbre et affiche les chemins.
7. Rejouer le seeder du domaine ne duplique et ne déplace rien.
8. Les tests web passent, Pint est propre et `openapi.json` est à jour. Les tests mobiles passent, sans modification de l'app.

## Hors périmètre

- La traduction automatique.
- Saisir plusieurs langues depuis l'app (un champ par langue). C'est possible plus tard si tu le souhaites, mais je le déconseille pour une app grand public.
- La navigation des offres par catégorie dans l'app, qui relève de la consultation publique, chantier à venir.

## Ce que j'ai décidé seul

- **Le repli des traductions vaut pour toute l'application.** Il est réglé dans `AppServiceProvider` : langue par défaut, puis n'importe quelle langue. Les pays, régions et catégories passent eux aussi de `en` à `fr` (la langue par défaut), puis à une autre langue.
- **La recherche par titre** vise chaque langue (`title->fr`, `title->en`) par le chemin JSON, de façon portable entre MariaDB et SQLite. Le nom d'une langue n'est donc pas trouvé.
- **Les attributs traduisibles** sont limités au texte court et au texte long. Ailleurs, une `LogicException` est levée.
- **Un texte vidé dans une langue retire cette langue.** Les autres langues restent.
- **Arbre :**
  - suppression d'un parent refusée en base (`restrictOnDelete`), dans le modèle (événements `saving` et `deleting`) et dans le back-office (notification, action unitaire et groupée) ;
  - une nouvelle catégorie se place à la fin de ses sœurs ;
  - le fil d'Ariane et le titre de la fiche montrent le chemin.
- **Les chemins de catégorie de la table des offres sont préchargés sur trois niveaux.** Au-delà, il reste une requête par niveau.

## Ce qui a été fait

- **Commits** :
  - `7bf1847` : traductions ;
  - `6db951d` : arbre ;
  - un dernier commit : test de suppression groupée et clôture.
- **Traductions** :
  - `Field::translatable()` ;
  - offres `HasTranslations` (titre, description) ;
  - migration de conversion, aller et retour ;
  - saisie dans la langue du compte ;
  - onglets par langue dans le back-office.
- **Arbre** :
  - `parent_id` et la migration associée ;
  - modèle : chemins, `options()` dans l'ordre de l'arbre, interdiction des boucles ;
  - back-office : racines triables et onglet « Sous-catégories » triable ;
  - seeder récursif, rejouable.
- **Contrôles** : 358 tests web passent, Pint est propre et `openapi.json` est inchangé. Les tests mobiles passent sans modification.
- **QA** (`bza-dev:qa-check`) : GO, rejoué sur MariaDB (`.ia/runs/2026-11-18-offres-multilingues-categories-arbre/qa-check.md`).

## Ce que je n'ai pas vérifié

- La migration de conversion aller-retour sur MariaDB. Elle n'a été rejouée que sur SQLite, dans les tests.
- Le glisser-déposer et les onglets dans un navigateur.
- Les arbres de grande taille.

## Ce qui reste en suspens

- **La recherche est sensible aux accents** : « etudiant » ne trouve pas « étudiant ». C'était déjà le cas avant ce chantier.
- **Rien n'est poussé.**
