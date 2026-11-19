RAPPORT QA — 2026-11-19-bouton-fixe-formulaires (commit mobile aca093a)
Go : oui
Base du verdict : rejeu moi-même dans /Users/bertrandzang/home/private/yamo/mobile : `npx jest --forceExit` = 47 suites / 248 tests verts ; `npx tsc --noEmit` sans sortie (propre) ; `npm run lint` sans erreur ni avertissement ; aucun changement de package.json dans le commit, arbre propre. Lecture du diff : FixedFooterLayout (contexte set/reveal, dernier formulaire monté tient la barre, la rend au démontage), DynamicForm (bouton dans la barre via useFixedFooter, sinon sous les champs ; Suivant/libellé final/rouge destructif/désactivé conservés dans SubmitButton ; reveal du premier champ en erreur après refus 422 ; reveal au passage à une étape > 0), ProfileScreenShell et GuestScreen (plus de KeyboardAvoidingView, bottomInset, SafeAreaView sans bord bas), (tabs)/_layout (tabBarStyle display none seulement si segments = tabs/profile/[form]). FilterScreen non modifié par le commit (barre et SafeArea propres) : pas de régression possible par ce commit. Avis (offers/[id]/review) passe par ProfileFormScreen : couvert. Aucun KeyboardAvoidingView restant dans src hors commentaire.
Ne couvre pas : comportement à l'écran (simulateur/appareil) : mesure du clavier (measureInWindow), défilement réel (measureLayout/scrollTo), masquage visuel du menu, zone sûre, Android ; non rejoué, seulement lu. Seuls 2 tests nouveaux (barre fixe, deux formulaires) ; ni le clavier, ni le reveal, ni le masquage du menu, ni le rouge dans la barre ne sont testés. Le web et la preuve de simulateur du contrat.

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Retour à l'étape 1 (Précédent / flèche) : l'effet de reveal ne joue que pour step > 0 ; l'étape 1 réaffichée garde le défilement courant au lieu de repartir de son début (critère « une nouvelle étape s'affiche depuis son début » respecté en avançant seulement).
- Listes du profil (`scrolls={false}`) : retrait de KeyboardAvoidingView. Risque faible : la recherche est en haut, donc le champ n'est jamais masqué ; en revanche, clavier ouvert, le bas de la liste passe sous le clavier (les derniers éléments inatteignables tant qu'il est ouvert), alors que le KAV réduisait la liste. Le KAV y était déjà mal mesuré (dans un onglet, selon le commentaire du commit). Acceptable ; atténuer par keyboardDismissMode="on-drag" sur List si gênant.
- Couverture de tests mince sur les nouveaux comportements (clavier, reveal, masquage du menu dans _layout, bouton rouge/désactivé dans la barre) : vérifiables seulement au simulateur.
