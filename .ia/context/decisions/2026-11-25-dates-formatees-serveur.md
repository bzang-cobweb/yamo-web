# `2026-11-25` — Toutes les dates montrées à l'app sont formatées par le serveur, dans le fuseau du pays

Remplace : 2026-11-25-avis-date-formatee-app.md

- **Type** : décision
- **Question posée** : « maintenant que les fuseaux horaires sont rajoutés, faire tous les formats de date en backend », puis « je dois être sûr que si je veux changer le format d'une date quelque part, je ne dois pas rebuild l'app ».
- **Ce qui est retenu** :
  - **Un seul formateur**, `App\Support\DisplayTime`. Il formate dans la langue de la requête et dans le fuseau de l'espace pays courant (`X-Country` → `countries.timezone`, décision 2026-11-25-fuseau-par-pays), sinon dans celui de l'application. Le module Location lui donne ce fuseau (`resolveZoneUsing`).
    - `date()` : un instant (« 18 nov. 2026 », « 18 novembre 2026 »).
    - `day()` : un jour sans heure, jamais décalé (une date de suppression).
    - `relative()` : « à l'instant », « il y a 5 min », « 10:30 », « hier à 10:30 », « 3 janv. 2026 11:00 ».
    - `stamp()` : l'heure seule aujourd'hui, sinon la date et l'heure ; complet, avec le jour (« mercredi 18 novembre 2026 22:30 »).
  - **Les textes servis** (la date brute reste à côté) :
    - fiche : `published_on`, « Membre depuis » ;
    - avis de la fiche : `date` ; liste des avis : `detail` ; les deux sont toujours identiques ;
    - cartes des listes : `date_text` ;
    - notifications : `date` (carte), `stamp` (détail), `stamp` de chaque étape ;
    - récupération du compte : `deletion_date_text`.
  - **L'app** affiche ces textes. Ses fonctions de formatage (`relativeTime`, `formatStamp`, `reviewDate`) sont retirées.
  - **Les champs date des formulaires** : leurs trois formats sont servis avec le champ (`date_formats`, depuis `config('form.date_formats')`), et l'app les applique tels quels (options d'`Intl.DateTimeFormat`) :
    - `value` : la date choisie dans le champ (« 12 avril 1990 ») ;
    - `month` : l'en-tête du calendrier (« novembre 2026 ») ;
    - `weekday` : les jours de la semaine (« lun. »).
    Les formats intégrés à l'app ne servent que si l'API n'en sert pas.
  - **Garantie** (« si je veux changer le format d'une date quelque part, je ne dois pas rebuild l'app ») : aucune date affichée n'a de format décidé par l'app. Changer un format se fait côté serveur seulement : dans `DisplayTime`, dans une ressource, ou dans `form.date_formats`.
  - **À savoir** : une date relative (« il y a 5 min ») est calculée à la réponse ; elle se met à jour au rechargement de l'écran.
- **Tranché par** : humain (tout côté serveur, sans nouvelle version de l'app) ; session (le fuseau du pays courant, les formats des champs date en options d'`Intl.DateTimeFormat`)
