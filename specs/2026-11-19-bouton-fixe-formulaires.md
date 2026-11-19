# Contrat — Formulaires : le bouton d'envoi fixe en bas, à la place du menu

Validation : en attente

Demande : « pour tous les formulaires, j'aimerais remplacer le menu fixe du pied de page par le bouton submit » ; après mon avis (oui, pour les formulaires ouverts comme un écran à part), « oui, écris le contrat ».

## Ce que j'ai compris

### Où
- **Tout formulaire ouvert comme un écran à part** :
  - les formulaires du profil (informations personnelles, mot de passe, préférences, e-mail, téléphone, suppression du compte, adresses, offres…) ;
  - le formulaire d'avis ;
  - connexion, inscription, mot de passe oublié (écrans à part, déjà sans menu) ;
  - l'écran des filtres (« Appliquer les filtres »).
- **Pas** les écrans racines des onglets (accueil, profil) ni les listes (« Mes logements ») : le menu y reste.

### Quoi
- Sur ces écrans, le **menu du bas disparaît** (onglets Accueil / Profil) ; le retour se fait par la flèche de l'en-tête, comme aujourd'hui.
- À sa place, une **barre fixe en bas** avec le bouton principal : « Suivant » sur une étape, le libellé d'envoi sur la dernière (« Enregistrer », « Se connecter »…), « Appliquer les filtres » sur les filtres. Rouge pour une action destructive (« Supprimer mon compte »). Désactivé pendant un envoi (photo en cours, enregistrement), comme aujourd'hui.
- Le **contenu défile au-dessus** de la barre ; « Étape 2 sur 8 » et le titre de l'étape restent en haut du contenu.
- Les **liens secondaires** restent dans la page : « Mot de passe oublié ? », « Renvoyer le code », « Effacer les filtres »…
- **Clavier** : la barre remonte juste au-dessus du clavier ouvert, sans masquer le champ saisi.
- **Erreurs** : un envoi refusé fait défiler jusqu'au premier champ en erreur ; le message général reste juste au-dessus de la barre.
- **Zone sûre** : la barre respecte le bas de l'écran (barre d'accueil de l'iPhone).

### Comment
- Générique : le formulaire (`DynamicForm`) place son bouton dans la barre fixe ; chaque écran qui l'affiche n'a rien à faire de plus. Aucun changement côté Laravel.
- Le menu est masqué sur les sous-écrans du profil (`(tabs)/profile/[form]`, et les formulaires ouverts depuis une liste).
- Aucune dépendance ajoutée : clavier géré avec ce que React Native fournit.

## Découpage

- App :
  - barre fixe du bouton dans `DynamicForm` (et l'écran des filtres) ;
  - menu masqué sur les formulaires du profil ;
  - défilement vers le premier champ en erreur ;
  - clavier et zone sûre ;
  - tests ;
  - vérification au simulateur sur un formulaire court (mot de passe), un formulaire à étapes (offre), la connexion et les filtres.
- Web : rien (décision consignée seulement).

## Critères de succès (état observable)

- Sur chaque formulaire ouvert comme un écran à part, le menu du bas n'apparaît pas et le bouton principal est fixé en bas, visible sans défiler.
- Accueil, profil et listes gardent le menu.
- Clavier ouvert : le bouton est juste au-dessus, le champ saisi reste visible.
- Une erreur sur un champ hors de l'écran : le formulaire y défile.
- Étapes, envoi désactivé, bouton rouge, liens secondaires : comme avant.
- Suites de l'app vertes, tsc et lint propres, simulateur vu, QA indépendante.

## Points à trancher (défaut appliqué si validé tel quel)

1. Connexion, inscription et mot de passe oublié reçoivent aussi la barre fixe (défaut : oui, pour que tous les formulaires se ressemblent).
2. L'écran des filtres aussi (défaut : oui).
3. « Précédent » : la flèche de l'en-tête suffit, pas de second bouton dans la barre (défaut).
