# `2026-11-28` — App : se connecter ou s'inscrire dans un panneau ouvert de partout

- **Type** : décision
- **Remplace en partie** : 2026-11-27-connexion-sans-mot-de-passe (côté app : l'écran `/sign-in` et l'inscription
  d'hier), 2026-11-27-onglets-visiteur-invitation (le bouton ouvre le panneau, plus `/sign-in`)
- **Question posée** : contrat `specs/2026-11-28-connexion-panneau.md`, partie app.
- **Ce qui est retenu** :
  - un seul panneau (`BottomSheet` à taille maximale, titré `auth.signInOrUp`), ouvert par `openAuthSheet()` depuis
    n'importe où et affiché par l'unique `AuthSheetHost` posé dans `_layout.tsx` (état du module, comme le bandeau
    des messages). Entrées : `GuestTab` (Profil, Notifications), « Réserver » de la fiche, bouton de section, lien
    servi `/sign-in` d'un pied de liste (« Devenir partenaire »). La route `/sign-in`, `SignInScreen` et `GuestScreen`
    sont retirés ;
  - parcours `AuthFlow` : « Bon retour » (mémoire) ou formulaire `auth.login` → `AuthCodeStep` → mot de passe
    (`auth.session.password` + « Mot de passe oublié ? » → `auth.session.new-password` avec `reset`, ou directement
    `new-password` en mode `create`/`register`) → `auth.register?channel=…` ; `next: restore` garde
    `RestoreAccountPrompt` ;
  - mémoire du dernier moyen : `src/features/auth/utils/last-sign-in.ts`, stockage chiffré (`expo-secure-store` via
    `device-storage`), écrite après chaque connexion par le panneau ;
  - un champ `code` complet (6 chiffres) envoie le formulaire, dans tout `DynamicForm` (aussi les codes du profil) ; le
    bouton servi reste ;
  - `DynamicForm` / `FormLoader` acceptent `defaults` (valeurs de départ, seuls les champs du formulaire) et `params`
    (paramètres du premier chargement).
- **Décidé seul (mode nuit)** :
  - fournisseur global (état du module) plutôt qu'une route plein écran : un seul parcours, appelable aussi d'un
    utilitaire hors composant (`openListFooter`) ;
  - le canal mémorisé est nommé comme le formulaire (`email` | `phone`), le canal du code (`sms`) est converti ;
  - l'indicatif est gardé à part (`yamo.auth.last-phone-country`) : « Ce n'est pas vous ? » efface l'identité mais garde
    l'indicatif par défaut (il n'identifie personne) ;
  - « Essayer autrement » ne vise l'identifiant mémorisé que si le code en cours est parti vers l'identifiant mémorisé
    (même compte) ; sinon retour au formulaire, l'autre canal présélectionné ;
  - le message « code envoyé à <masqué> » d'« Essayer autrement » réutilise `auth.codeSentTo` dans le bandeau ; le
    renvoi garde `auth.resent` ;
  - le retour depuis le code revient à l'étape précédente avec les valeurs saisies ; pas de retour depuis le mot de
    passe ou l'inscription (le code est consommé) — la croix ferme ;
  - un compte récupéré (`restore`) se connecte sans mettre la mémoire à jour.
- **Portée** : app (`features/auth`, `features/form`, `_layout.tsx`, onglets, fiche, pieds de liste).
- **Tranché par** : session de nuit
