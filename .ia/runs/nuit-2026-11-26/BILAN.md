# Nuit du 2026-11-26 — Yamo
Branche : nuit/2026-11-26 (web depuis main@275a558, mobile depuis main@8ae3c2b)
État : terminée avec réserves

## Ce qui attend votre attention
- **La solution choisie** : un message est une notification, dans un fil par offre et par membre ; la discussion se lit
  dans l'onglet Notifications (une carte par discussion, la page en bulles, « Répondre »). Pas de module ni de liste
  « Mes messages ». Si vous préférez une vraie messagerie à part, c'est un autre chantier.
- **Pas vu dans le simulateur** : toutes les offres de démo sont à Awa, le membre connecté ; le bouton n'apparaît donc
  sur aucune (c'est voulu sur sa propre offre). Pour essayer : se connecter avec un autre compte, ouvrir une offre
  d'Awa, « Envoyer un message » ; puis, avec Awa, la notification « Message », « Répondre ».
- Rien n'est poussé ni mergé : les deux branches `nuit/2026-11-26` attendent votre relecture.

## Fait
- Écrire à l'éditeur depuis la fiche (contrat `specs/2026-11-26-messagerie-offre.md`) :
  - web `043e3db` : formulaire `offer.message`, droits, route, notification `OfferMessageReceived`, bouton de la
    section « Éditeur », traductions, 4 tests ; décision `2026-11-26-messages-editeur` ;
  - mobile `5e7fb69` : le bouton d'une section (`section.action`), connexion d'abord pour un visiteur ; 2 tests ;
  - preuves : web 762 tests verts, Pint propre ; mobile tsc sans erreur, 401 tests verts ; qa-check : GO.

## Reste à faire
- Voir le parcours dans l'app avec un second compte.
- Si besoin : une liste « Mes messages », le type « Message » dans le filtre des notifications, un test « un tiers ne
  voit pas le fil des autres ».

## Décidé seul
- Fil de notifications plutôt qu'un module de messagerie : réutilise la carte, la page en bulles, les non-lus et les
  canaux, sans table ni écran nouveaux.
- L'éditeur ne peut pas écrire le premier ; il répond à un membre qui lui a écrit.
- Bouton servi aussi aux visiteurs (connexion d'abord), comme « Réserver ».
- E-mail avec le texte du message, SMS sans le texte.
- Détails dans la section « Ce que j'ai décidé seul » du contrat et dans la décision.

## Défauts trouvés
- `Modules/Offer/app/Notifications/OfferNotificationFilter.php` : un docblock orphelin (« La pastille d'une
  notification de demande ») collé à celui de `prepare()` ; antérieur à la nuit, noté, pas corrigé.
- Commande `php artisan tinker` lancée sans `--execute` : elle attend une saisie et ne rend pas la main (arrêtée).
