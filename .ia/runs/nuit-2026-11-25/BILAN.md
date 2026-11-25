# Nuit du 2026-11-25 — YaMo (web + mobile)
Branche : aucune (nuit annulée avant l'ouverture), sur main : web@ac0fd69, mobile@66d1715
État : interrompue — arbre non propre, nuit annulée

## Ce qui attend votre attention

Les deux dépôts avaient des changements non commités au lancement. Ils attendent votre accord de commit, que je ne peux pas recevoir la nuit. Le mode nuit interdit de les commiter, de les mettre de côté ou de changer de branche par-dessus. Je n'ai donc rien fait.

Changements en attente :

- **Chevauchements de démo du membre**, dans web :
  - `database/seeders/DemoSeeder.php`
  - `tests/Feature/ResetBookingsCommandTest.php`
  - `.ia/context/decisions/2026-11-25-demo-chevauchements-du-membre.md`
- **Carte « Devenir partenaire » sur l'accueil**, dans web :
  - `Modules/Partner/app/Http/Controllers/Api/PartnerController.php`
  - `Modules/Partner/tests/Feature/Api/PartnerApiTest.php`
  - `.ia/context/decisions/2026-11-25-accueil-carte-devenir-partenaire.md`
- **Carte « Devenir partenaire » sur l'accueil**, dans mobile :
  - `src/features/offer/screens/HomeScreen.tsx` et son test
  - `src/features/partner/components/PartnersStrip.tsx` et son test
  - `src/features/partner/hooks/usePartners.ts`
  - `src/features/partner/services/PartnerService.ts`
  - `src/features/partner/types.ts`

Pour relancer la nuit :
1. commiter ces changements, ou dire de les commiter ;
2. relancer `/bza:nuit` avec les mêmes demandes.

## Fait

Rien.

## Reste à faire

Les demandes de la nuit, toutes à faire :

1. Fiche d'une offre : toucher une photo du carrousel ouvre une galerie d'images.
2. Utiliser les conversions d'images pour les photos des offres partout.
3. Le bloc « À la une ». Option retenue dans la discussion : les vedettes d'abord, tirées au hasard, complétées par des offres récentes bien notées ; 2 par défaut, réglable jusqu'à 4.
4. Compteurs de vues et de clics.

## Décidé seul

Aucune décision.

## Défauts trouvés

Aucun.
