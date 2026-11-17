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
