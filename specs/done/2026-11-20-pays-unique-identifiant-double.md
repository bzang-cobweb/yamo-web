# Pays unique verrouillé ; identifiant quand les deux canaux sont actifs

Demande du 2026-11-20.

## Ce que j'ai compris

### 1. Un seul pays actif : présélectionné et verrouillé, partout

Quand la liste des pays actifs n'en compte qu'un, chaque champ qui fait choisir un pays le **sélectionne d'office** et ne permet pas d'en changer. La valeur est tout de même envoyée et validée : ce n'est pas un champ en lecture seule ignoré.

| Endroit | Champ |
|---|---|
| Inscription | pays du téléphone |
| Connexion et mot de passe oublié (téléphone) | pays du numéro |
| Informations personnelles | pays (la liste des régions suit) |
| Changement de numéro | pays du nouveau numéro |
| Back-office, fiche utilisateur | pays, et pays du téléphone |

- **Domicile** : le module Form, une seule fois pour tous les formulaires. Un champ liste ou téléphone dont il ne reste qu'une option est marqué `locked` dans le JSON, avec cette option par défaut.
- **L'app** : elle affiche un champ `locked` sans permettre d'en changer, et l'envoie quand même.
- **Le back-office** : il applique la même règle aux deux listes de pays de la fiche utilisateur.
- Avec deux pays actifs ou plus, rien ne change.

### 2. Les deux canaux actifs : un identifiant mieux saisi

Aujourd'hui, avec les deux canaux, c'est un texte unique, « E-mail ou téléphone » au format international.

**Réponse à la validation** : un sélecteur à deux choix, « E-mail | Téléphone », au-dessus du champ.
- « E-mail » affiche le champ e-mail, avec le clavier e-mail.
- « Téléphone » affiche le pays et le numéro sur une ligne.
- Un seul champ est visible à la fois.

**Mise en œuvre prévue** :
- Le module Form gagne un type de champ `segmented` (choix à boutons) et une condition d'affichage `visible_when`. Un champ masqué n'est pas validé côté serveur (`exclude_unless`).
- La connexion et le mot de passe oublié envoient `email`, ou `phone` et `phone_country`, à la place de `identifier`, dans les trois modes. L'erreur s'affiche sous le champ utilisé.

## Critères de succès

- Avec un seul pays actif, chaque champ de pays de l'app et du back-office est déjà rempli et ne peut pas être changé. L'enregistrement fonctionne.
- Avec plusieurs pays actifs, rien ne change.
- Avec les deux canaux, la connexion et le mot de passe oublié montrent le sélecteur « E-mail | Téléphone » et le seul champ choisi. Avec un seul canal, la présentation reste la même : champ e-mail, ou pays et numéro.
- Tests web et mobile verts, OpenAPI régénéré, aucune dépendance ajoutée.

## Ce que j'ai décidé seul

- **Verrouillé, pas en lecture seule** : `locked` est un nouvel indicateur, distinct de `readonly`. Un champ en lecture seule n'est ni envoyé ni validé ; un champ verrouillé l'est. Pour un téléphone, seul le pays est verrouillé, et le numéro reste à saisir.
- **Seuls les listes et les téléphones** se verrouillent, et jamais une liste dépendante (région) : ses options suivent le pays choisi.
- **Le champ du sélecteur s'appelle `channel`**, avec les valeurs `email` et `phone`, et « E-mail » par défaut. Son libellé reste « E-mail ou téléphone ».
- **L'API change de contrat** : `identifier` est remplacé par `email`, ou par `phone` et `phone_country`, dans les trois modes. Les tests existants sont adaptés. La limite de débit de connexion utilise ces champs.
- **Back-office** : le pays du téléphone verrouillé n'est enregistré que si un numéro est saisi.
- **Texte d'aide supprimé** : l'aide sur le format international (`identifier_help`) ne sert plus à rien, puisque le numéro se saisit au format national avec son pays.

## Ce qui a été fait

- Module Form :
  - type `segmented` ;
  - `visibleWhen()`, qui met `exclude_unless` dans les règles et `visible_when` dans le JSON ;
  - `isLocked()`, avec `locked` et un `default` sur l'option unique ;
  - `help()` accepte `null`.
- Auth :
  - `HasIdentifierField::identifierFields()` (sélecteur et champs) ;
  - `Identifiers::resolve(array)` ;
  - connexion et mot de passe oublié ;
  - textes `channel_email` et `channel_phone`.
- Back-office : `SingleCountry::lock()` (module Location) sur le pays et le pays du téléphone de la fiche utilisateur.
- App :
  - `SegmentedField` ;
  - `visible_when`, pour afficher un champ et ne pas l'envoyer quand il est masqué ;
  - `locked` sur une liste ou sur le pays d'un téléphone.
- Tests :
  - web : 231 (nouveaux tests du module Form, du pays unique, du back-office et de la connexion par numéro) ;
  - app : 98, dont `FieldConditions` (3).
- OpenAPI régénéré.
- qa-check : GO.
- Simulateur, les deux canaux actifs : le sélecteur « E-mail | Téléphone » affiche le champ e-mail, puis le pays et le numéro.

## Ce que je n'ai pas vérifié

- Le verrouillage du pays unique à l'écran, dans l'app et dans le back-office. La base de développement a plusieurs pays actifs, et je ne l'ai pas modifiée. Le comportement est couvert par les tests seulement.
- Le back-office dans le navigateur, qui refuse le certificat DDEV.

## Ce qui reste en suspens

- Rien pour ce contrat.
