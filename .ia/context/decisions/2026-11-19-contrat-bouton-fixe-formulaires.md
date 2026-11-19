Remplace en partie : 2026-11-16-contrat-menu-et-profil.md (le menu du bas restait visible sur les formulaires du profil).

# `2026-11-19` — Contrat « formulaires : bouton d'envoi fixe en bas, à la place du menu », validé

- **Type** : décision
- **Question posée** : « pour tous les formulaires, j'aimerais remplacer le menu fixe du pied de page par le bouton submit. est-ce une bonne idée ? » ; avis favorable pour les formulaires ouverts comme un écran à part ; contrat `specs/2026-11-19-bouton-fixe-formulaires.md` avec trois défauts.
- **Réponse** : « Approved » (défauts retenus).
- **Ce qui est retenu** :
  - sur tout formulaire ouvert comme un écran à part (profil, offre, adresses, avis, connexion, inscription, mot de passe oublié, filtres), le menu du bas disparaît et le bouton principal est fixé en bas ;
  - accueil, profil et listes gardent le menu ;
  - le contenu défile au-dessus de la barre ; liens secondaires dans la page ; bouton rouge pour une action destructive ; désactivé pendant un envoi ;
  - la barre remonte au-dessus du clavier ; un refus défile jusqu'au premier champ en erreur ;
  - pas de bouton « Précédent » dans la barre (la flèche de l'en-tête suffit) ;
  - aucun changement côté Laravel, aucune dépendance.
- **Motif** : le bouton toujours à portée sur les longs formulaires ; moins de sorties par erreur ; la pratique courante des sous-écrans.
- **Portée** : app (`DynamicForm`, écrans de formulaire, onglets, écran des filtres).
- **Corollaires** : la navigation par onglets n'est plus possible depuis un formulaire ; on revient par la flèche.
- **Tranché par** : humain
