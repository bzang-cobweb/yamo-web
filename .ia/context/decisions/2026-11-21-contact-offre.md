# Le téléphone et l'e-mail de contact d'une offre

- **Date** : 2026-11-21
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-21-detail-reservation-contact-ordre.md

## Décision
- La table `offers` a deux colonnes facultatives : `phone` (format E.164) et `email`.
- Dans le formulaire de l'app, ils se saisissent dans l'étape « Localisation », après
  « Afficher l'adresse » : un champ téléphone (pays et numéro, le pays courant par défaut) et un
  champ e-mail.
- Dans le back-office, le téléphone se saisit au format international (`phone:INTERNATIONAL`),
  avec un champ e-mail à côté.
- Un champ envoyé vide efface la valeur ; un champ absent de l'envoi garde la valeur.
- Dans le détail d'une demande, le membre voit le téléphone et l'e-mail de l'offre. Quand l'un
  manque, il voit celui du propriétaire à la place.
- L'hôte voit toujours les coordonnées du membre.

## Données de démonstration
`DemoSeeder::contacts()` donne à chaque logement de démonstration le téléphone et l'e-mail de
démonstration de son propriétaire, là où ils manquent : le compte de démonstration et le premier
visiteur. Rejouable ; appliqué à la base locale.

## Motif
L'humain a demandé si l'offre avait déjà ces champs (réponse : l'adresse oui, le téléphone et
l'e-mail non). Le 2026-11-21, il a répondu : « oui ajoute téléphone et email à l'offre et
utilise-les dans la vue détail par le membre », puis « mettre le téléphone et l'email de
démo sur les offres ».

## Ce que j'ai décidé seul
- Le repli sur les coordonnées du propriétaire quand l'offre n'en a pas.
- La place des champs, dans l'étape « Localisation ».
