RAPPORT QA — 2026-11-16-photo-de-profil, étape 2 (mobile), tâche 04
Go : oui
Base du verdict : rejoué moi-même dans /Users/bertrandzang/home/private/yamo/mobile : `npm test` 5 suites / 24 tests verts ; `npm run typecheck` sans erreur ; `npm run lint` sans avertissement. Lu git diff + fichiers nouveaux ; package.json (expo-image-picker ~58.0.10, installé 58.0.10, SDK expo ~58) ; app.json (photosPermission, cameraPermission:false, microphonePermission:false) ; convertFormData.ts d'expo (une pièce est lue par `bytes()` : `form-file.ts` fournit {name,type,bytes} — cohérent) ; ApiClient (FormData passé tel quel, Content-Type non posé, Accept conservé) ; ProfileService (uploadAvatar / removeAvatar, un appel API chacun) ; pick-avatar (galerie, allowsEditing, aspect 1:1, quality 0.7) ; Avatar (photo ronde / initiales) sur UserSummary (Accueil) et AvatarPicker (Profil) ; menu Alert avec « Retirer » seulement si avatar_url ; setUser après succès ; erreur ApiError (errors.avatar[0]) affichée sous la photo ; messages fr/en : 6 clés avatar.* dans chaque langue ; README section « Photo de profil ».
Cohérence avec le constat simulateur : le code est cohérent (lecture par bytes() corrigée et documentée au README ; setUser met à jour Accueil et Profil ; la persistance dépend de avatar_url renvoyé par /me, couvert par la partie web).
Ne couvre pas : exécution réelle dans le simulateur/Expo Go (picker natif, rendu, persistance, disque) ; comportement sur Android ; web (déjà vérifié, commit 8bacb51) ; nouvelle installation (aucune faite).

Bloquants : 0

Majeurs : 0

Mineurs : 2
- package-lock.json : 422 ajouts / 400 retraits ; hors expo-image-picker et expo-image-loader (transitive, attendue), le bruit est un basculement massif "dev": true -> "devOptional": true (effet de version npm). Sans risque, mais gonfle le diff ; à relire avant commit.
- Commentaire de tête de ProfileScreen : ligne unique trop longue, sans effet.
