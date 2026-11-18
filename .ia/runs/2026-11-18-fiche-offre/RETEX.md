# RETEX — Fiche d'une offre

## Ce qui s'est produit

- **Un libellé a perdu son unité dans le formulaire.** En déplaçant « (m²) » du libellé vers une unité (`suffix`) servie par l'API, j'ai oublié que l'app ne savait pas encore l'afficher. La QA l'a vu en cherchant `suffix` dans l'app. Un changement de contrat d'un champ servi exige de vérifier son rendu côté app.
- **Scramble a de nouveau perdu des types** (le libellé du prix via un registre, les URL de photos via une collection). Je l'ai corrigé avec des transtypages et une méthode typée. Le même piège avait déjà frappé `ListItem`.
- **Un test de défilement trop large a révélé une position non bornée** dans le carrousel.
- **Un domaine de test déclaré dans un autre fichier de test n'est pas chargeable** depuis un autre test : il faut le redéclarer, ou le ranger dans un fichier de support de tests.

## Règles candidates

- **Projet** : toute nouvelle clé servie à l'app (champ, item, fiche) a son rendu et son test côté app, dans le même chantier.
- **Projet** : les domaines de test réutilisés vont dans `Modules/Offer/tests/Support/`.
- **Marketplace (bza-laravel:generate-openapi)** : une valeur qui vient d'un registre ou d'une collection se transtype, ou passe par une méthode à type de retour documenté.

## Règle ré-implémentée, règle domiciliée

- **Domiciliée** : la valeur lisible d'un champ (`Field::displayValue`), réutilisable par toute fiche ou tout export.
- **Domiciliée** : l'adresse des photos (`MediaUrl`), réutilisée par la fiche.
