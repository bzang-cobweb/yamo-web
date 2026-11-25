# `2026-11-25` — Le nom des régions du pays, libellé du champ et du filtre de la région partout

- **Type** : décision
- **Question posée** : « utiliser region_label comme label du champ région dans tous les formulaires et filtres ».
- **Ce qui est retenu** : le champ ou le filtre de la région prend le nom des régions du pays (`Country::regionLabel()`, « Région » sans réglage) :
  - formulaire d'une offre : le pays de l'offre ;
  - carnet d'adresses : le pays courant ;
  - filtres des listes d'offres et de réservations : le pays courant ;
  - profil, informations personnelles (app) : le pays du compte, sinon le pays courant. L'app ne change pas le libellé quand on choisit un autre pays dans le formulaire ;
  - back-office, fiche d'un compte : le pays choisi, et le libellé suit le choix ;
  - back-office, adresses d'un pays : le formulaire et la colonne.
- **Pas changé** : là où plusieurs pays se mêlent (la colonne « Région » des adresses d'un compte au back-office), le libellé générique reste.
- **Tranché par** : humain (la demande) ; session (le pays retenu dans chaque écran)
