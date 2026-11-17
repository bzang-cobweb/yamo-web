# Profil : téléphone et titre ; sélecteur de langue ; barre du haut

Demande du 2026-11-20, quatre points.

## Ce que j'ai compris

1. **Téléphone sur la page Profil** : le numéro s'affiche sous l'e-mail, au format international lisible avec l'indicatif du pays (par exemple `+41 79 123 45 67`). Aujourd'hui, il ne s'affiche que s'il existe, et brut. L'API envoie ce format (`phone_international`), calculé par la bibliothèque déjà présente (`propaganistas/laravel-phone`). Sans numéro, rien ne s'affiche ; le lien « Ajouter un numéro » reste.
2. **Titre « Profil »** retiré de la page Profil.
3. **Sélecteur de langue** : sur l'écran d'accueil de l'invité, les boutons côte à côte deviennent une liste déroulante simple, avec la langue courante et une icône, **centrée au-dessus du logo**.
   - Au toucher, elle ouvre la liste des langues de l'API, dans la même fenêtre de choix que les listes déroulantes des formulaires. Avec plus de 12 langues, la liste propose une recherche.
   - Même chose sur la page web `/` : une liste déroulante centrée au-dessus du logo, au lieu des liens, qui fonctionne aussi sans JavaScript.
   - **Le sélecteur ne s'affiche pas s'il n'y a qu'une langue**, sur le web comme dans l'app.
4. **Barre du haut** : la barre du logo disparaît de l'onglet Accueil. Elle reste sur l'onglet Profil.

## Critères de succès

- Un utilisateur qui a un numéro le voit sur la page Profil, au format international. Sans numéro, rien ne s'affiche.
- La page Profil n'a plus de titre.
- L'écran d'accueil de l'invité et la page web `/` proposent la langue par un sélecteur unique, quel que soit le nombre de langues. Choisir une langue garde le comportement actuel : changement immédiat, choix mémorisé.
- L'onglet Accueil n'a plus de barre du haut ; l'onglet Profil la garde.
- Tests web et mobile verts, tsc, lint et Pint propres, aucune dépendance ajoutée.

## Ce que j'ai décidé seul

- Les deux questions sans réponse (affichage du téléphone, écran de la barre à retirer) sont tranchées par les défauts du contrat :
  - le numéro au format international s'il existe ;
  - la barre retirée de l'onglet Accueil seulement.
- Le format international est calculé par l'API (`phone_international`, `PhoneNumbers::international`), et non par l'app, pour éviter une nouvelle dépendance mobile.
- Dans l'app, le sélecteur réutilise la fenêtre de choix des formulaires (`OptionsModal`), avec ses 12 langues avant recherche. Icônes : globe et chevron.
- Sur le web :
  - le sélecteur est un formulaire `GET /locale?locale=…`, soumis au changement, avec un bouton visible seulement sans JavaScript ;
  - l'ancienne route `/locale/{code}` est remplacée.
- L'écran Accueil reprend la zone sûre du haut, puisqu'il n'a plus de barre.

## Ce qui a été fait

- API :
  - `phone_international` dans la ressource utilisateur ;
  - tests mis à jour ;
  - `openapi.json` régénéré.
- Web : liste déroulante centrée au-dessus du logo, masquée avec une seule langue, et 6 tests.
- Mobile :
  - Profil sans titre, avec le numéro au format international ;
  - `LocaleSwitcher` en liste déroulante au-dessus du logo, et 3 tests ;
  - barre retirée de l'onglet Accueil.
- Vérifications :
  - web : 202 tests, Pint propre ;
  - mobile : 91 tests, tsc et lint propres ;
  - qa-check : NO-GO au premier passage (`openapi.json`), puis GO au second.
- Vérifié dans le simulateur :
  - sélecteur, changement de langue et choix gardé après un redémarrage ;
  - Accueil sans barre ;
  - Profil sans titre, avec `+41 79 123 45 67` (compte jetable, supprimé).
- Vérifié dans le navigateur : la liste déroulante de `/` change la langue.

## Ce que je n'ai pas vérifié

- La page web sans JavaScript : couverte par la lecture du HTML et par les tests seulement.
- L'affichage du sélecteur avec plus de 12 langues, donc la recherche dans la liste.

## Ce qui reste en suspens

- Rien pour ce contrat. `TranslatableTabs` (`contained(true)`) reste une modification non commitée de l'humain.
