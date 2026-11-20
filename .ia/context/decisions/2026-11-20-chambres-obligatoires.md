# Le nombre de chambres d'un logement est obligatoire

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-titre-demande.md

## Décision

Le champ `bedrooms` du domaine logement est obligatoire (`required`), entier de 0 à 50 :
0 pour un studio. Il vaut dans le formulaire de l'app comme dans le back-office.

Sur la page d'une notification d'une demande, le nombre de chambres s'affiche à droite
du nombre de personnes (`booking.offer.bedrooms`, rédigé : « 2 chambres », « Sans chambre »).

## Motif

Demandes de l'humain du 2026-11-20 : « mettre le nombre de chambre aligné à droite du
nombre de personne », puis « le nombre de chambre doit être obligatoire ».

## Portée

`RentalDomain::attributes()` ; les offres existantes ne sont pas modifiées.

## Corollaires

- Un logement enregistré sans nombre de chambres le reste jusqu'à sa prochaine
  modification, qui le demandera ; sa ligne sur la page d'une demande n'affiche alors
  pas de chambres.
