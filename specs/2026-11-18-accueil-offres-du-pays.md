# Accueil : barre du haut (logo, langue, pays) et offres du pays

Demande du 2026-11-18 :

> quand on clique sur entrer depuis la page d'accueil ou lorsqu'on a déjà la langue et le pays renseignés, on doit arriver sur une page avec :
> * le logo à gauche sur la barre fixe du haut, le sélecteur de langue et le pays qui ouvre chacun son sélecteur

Réponses aux questions posées :

- sous la barre, les **offres du pays** ;
- la **même barre partout**, connecté ou non.

## Ce que j'ai compris

Après l'écran d'entrée, on arrive sur un **accueil** qui montre les offres publiées du pays courant, ouvert aux visiteurs comme aux membres. L'actuel écran de bienvenue (grand logo, description, boutons) disparaît. La description reste sur l'écran d'entrée, et les boutons de connexion et d'inscription passent en bas de l'accueil pour un visiteur.

### Barre du haut, partout

La barre est fixe et la même pour un visiteur et pour un membre :

- à gauche, le logo YaMo ;
- à droite, deux sélecteurs :
  - la **langue** : la pastille globe et langue de l'écran d'entrée, qui ouvre la liste des langues ;
    - pour un visiteur, la langue choisie est gardée sur l'appareil ;
    - pour un membre, c'est **la langue de son compte** qui change (`PATCH /api/v1/auth/me/preferences`, comme dans Préférences) ;
  - le **pays** : le drapeau, qui ouvre directement la liste des pays avec recherche, sans repasser par l'écran d'entrée ;
    - choisir un pays change d'espace et recharge les données, sans déconnexion ;
    - avec un seul pays ouvert, le drapeau n'ouvre rien.

La barre remplace l'actuelle (logo et drapeau). Elle n'apparaît pas sur l'écran d'entrée, ni sur les formulaires du profil, qui gardent leur en-tête avec retour.

### Accueil : les offres du pays

- **Liste publique** `offer.browse` (liste générique, défilement infini) :
  - **contenu** : les offres **publiées** du pays courant, les plus récentes d'abord, tous comptes confondus ;
  - **chaque item** :
    - titre dans la langue de l'app ;
    - en sous-titre, prix et ville (« 250 000 FCFA · Douala ») ;
    - ni édition, ni retrait : c'est une vitrine ;
  - **recherche** dans le titre, toutes langues confondues ;
  - **accès** : ouverte **sans connexion**. `GET /api/v1/lists/offer.browse` répond à un visiteur ; les autres listes restent réservées aux comptes connectés ;
  - **disponibilité** : sans domaine choisi, elle n'existe pas (404). Sans pays, elle répond 422, comme le reste de l'espace pays.
- **Visiteur** : l'accueil montre la barre, la liste, et en bas deux boutons fixes, « Se connecter » et « Créer un compte ».
- **Membre** : l'onglet Accueil montre la même liste, sous la même barre. Le menu du bas (Accueil, Profil) ne change pas. « Mes logements » reste dans le profil.

### Hors de cette demande

- **Fiche détaillée d'une offre** (photos, description, attributs, contact) : toucher une offre ne fait rien pour l'instant. Ce sera le chantier suivant.
- **Filtres par catégorie, prix ou ville**, et carte.
- **Photos dans la liste.**

## Critères de succès

1. Après « Entrer », ou au lancement suivant, on arrive sur l'accueil avec la barre : logo à gauche, langue et drapeau à droite.
2. La pastille de langue change la langue de l'app : pour un visiteur, gardée sur l'appareil ; pour un membre, celle de son compte. Le drapeau ouvre la liste des pays avec recherche, et en choisir un autre recharge l'accueil sur ce pays.
3. L'accueil liste les offres publiées du pays, sans brouillon, sans archivée et sans offre d'un autre pays, avec prix en monnaie locale et ville. La recherche par titre fonctionne. Le défilement charge la suite.
4. Sans connexion, la liste est accessible, et les boutons « Se connecter » et « Créer un compte » mènent à leurs écrans. Connecté, l'onglet Accueil montre la même liste sous la même barre.
5. Avec les données de démonstration (`DemoSeeder`), l'accueil au Cameroun montre 16 logements.
6. Tests :
   - web : tests verts, Pint propre, `openapi.json` à jour ;
   - mobile : tests verts (barre, sélecteurs, accueil visiteur et membre), tsc et lint propres.

## Hors périmètre

- La fiche d'une offre, les filtres et la carte : chantiers suivants.
- Le contact entre locataire et propriétaire.
