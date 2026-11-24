# Nuit du 2026-11-24 — Yamo
Branche : nuit/2026-11-24 (web depuis main@16b147a, mobile depuis main@0bacba0)
État : interrompue (en cours)

## Ce qui attend votre attention
(en cours)

## Fait
- **1. Notifications de mise en validation** (web `bbf0f18`) : ajout ou modification depuis l'app → `PartnerSubmitted` → tous les comptes rattachés, auteur compris, chacun son texte ; plus de notification « statut changé » en double ; la publication s'annonce « accepté ». Tests du module Partner : 32 passent.
- **2. Validation au back-office** : action « Valider » (liste et page), visible si en validation et droit de modifier ; publie, notifie les comptes (« Partenaire accepté »). Tests du module Partner : 34 passent.
- **3. « Devenir partenaire »** : barre fixe sur « Mes partenaires » (formulaire) et sur la page publique « Partenaires » (connecté : formulaire ; visiteur : connexion), décrite par l'API ; `openListFooter()` commun dans l'app. Tests : 35 web (Partner), 72 app (partner, listes du profil).

## Reste à faire
1. Ajout ou modification d'un partenaire : en validation, notifications aux autres administrateurs et à l'auteur.
2. Validation au back-office : statut accepté, notifications aux administrateurs.
3. Bouton fixe « Devenir partenaire » (connecté : formulaire ; sinon : connexion).
4. Notifications : retrouver celles des partenaires, par statut.

## Décidé seul
(en cours)

## Défauts trouvés
(aucun pour l'instant)
