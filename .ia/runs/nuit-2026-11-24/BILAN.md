# Nuit du 2026-11-24 — Yamo
Branche : nuit/2026-11-24 (web depuis main@16b147a, mobile depuis main@0bacba0)
État : terminée avec réserves

## Ce qui attend votre attention
- **Interprétations à confirmer** (détail : contrat `specs/2026-11-24-partenaires-validation.md`, « Ce que j'ai décidé seul ») :
  - « administrateurs (édition) » = tous les comptes rattachés au partenaire ;
  - « accepté » = « Publié » (les notifications disent « accepté ») ;
  - « Devenir partenaire » aussi sur la page publique « Partenaires », car un visiteur ne voit jamais « Mes partenaires » ; sur « Mes partenaires », il remplace « Ajouter un partenaire » ;
  - « trier par status » = filtre « Statut du partenaire » dans « Notifications ».
- **Non vérifié à l'écran** : simulateur et back-office, seulement par les tests.
- **Textes allemands et anglais** à relire.
- **À intégrer** : branches `nuit/2026-11-24` dans web et mobile, rien n'est poussé ni fusionné.

## Fait
- **1. Notifications de mise en validation** (web `bbf0f18`) : ajout ou modification depuis l'app → `PartnerSubmitted` → tous les comptes rattachés, auteur compris, chacun son texte ; plus de notification « statut changé » en double ; la publication s'annonce « accepté ». Tests du module Partner : 32 passent.
- **2. Validation au back-office** : action « Valider » (liste et page), visible si en validation et droit de modifier ; publie, notifie les comptes (« Partenaire accepté »). Tests du module Partner : 34 passent.
- **3. « Devenir partenaire »** : barre fixe sur « Mes partenaires » (formulaire) et sur la page publique « Partenaires » (connecté : formulaire ; visiteur : connexion), décrite par l'API ; `openListFooter()` commun dans l'app. Tests : 35 web (Partner), 72 app (partner, listes du profil).
- **4. Notifications des partenaires** : type « Partenaire » et filtre « Statut du partenaire » dans « Notifications », pastille du statut, recherche par nom, qui agit sur la carte. Suite complète : 691 tests web, 369 tests app.

## Reste à faire
- Rien de la demande. En suspens (non demandé) : une action « Refuser » au back-office ; ouvrir le formulaire après la connexion depuis « Devenir partenaire ».

## Décidé seul
Les interprétations ci-dessus ; plus : plus de notification « statut changé » en double pour le passage en validation ; « Valider » sur la liste et la page, avec confirmation ; la notification d'ajout ou de modification nomme qui agit ; le filtre montre le statut annoncé par la notification (pas le statut actuel). Décisions : `2026-11-24-partenaires-validation-notifications`, `-validation-back-office`, `2026-11-24-devenir-partenaire`, `2026-11-24-partenaires-notifications-filtre`.

## Défauts trouvés
- Corrigé : guillemets dans `Modules/Partner/lang/*/mobile.php` (préexistant sur `main`, faisait échouer `pint --test`), commit `93cb44a`.
- Vérification indépendante : `.ia/runs/nuit-2026-11-24/qa-check.md` (critères 1 à 4 conformes ; le blocage de formatage corrigé ensuite).
