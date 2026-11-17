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
