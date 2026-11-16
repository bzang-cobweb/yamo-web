RAPPORT QA — étape 2 (mobile), tâche 04
Go : oui
Base du verdict : rejoué moi-même sur les changements non commités de /Users/bertrandzang/home/private/yamo/mobile :
- npm test : 6 suites, 32 tests passés ; npm run typecheck : 0 erreur ; npm run lint : code de sortie 0.
- curl GET /api/v1/messages (Accept-Language fr puis en) : les 21 clés de ApiMessageKey existent en fr et en, sans manque. Aucune clé en trop dans la réponse.
- Les clés utilisées par t(...) dans src/ (hors tests) sont toutes dans MessageKey : 19 en littéral, plus form.showPassword et form.hidePassword via un ternaire ; offline.* est local (OFFLINE_MESSAGES).
- Revue de src/ (ThemedText, accessibilityLabel, Alert, placeholder, titres) : plus aucun texte visible écrit en dur, hors offline.* (fr/en). Exceptions connues, non bloquantes : glyphes « ← », « * », « ✓ » ; photosPermission dans app.json. error.network et signIn.title ont disparu du code et du README.
- Variables :name : le remplacement se fait par regex dans translate() (locale.ts). Les 3 textes à variables de l'API (home.greeting, home.signedInAs, home.email) utilisent :name, :fullName, :email. Une variable absente reste visible. Couvert par locale.test.ts.
- Langue : locale = compte, sinon pickBestLocale(appareil) ; requête ['messages', locale] avec keepPreviousData et staleTime Infinity ; le changement de langue ne démonte pas la navigation (isLoading ne repasse pas à true, car placeholderData fournit les textes précédents).
- Hors ligne : failedToLoad() couvre réseau et serveur qui répond mal (locales, me, messages) ; ApiError 401/403 (rejectsToken) est exclu et renvoie à la connexion via le nettoyage du jeton. Retry sans nouvelle tentative sur NetworkError. OfflineScreen affiche message et Réessayer locaux ; retry() relance locales, me et messages. DynamicForm, AvatarPicker et FormLoader utilisent offline.message sur NetworkError, et FormLoader offline.retry.
- DynamicForm : title puis subtitle, chacun seulement s'il est non nul, en-tête absent sinon. FieldShell : aide toujours affichée (gris), erreur en plus en dessous (rouge, accessibilityRole alert). L'aide en doublon a été retirée de CheckboxField et SwitchField : elle ne s'affiche qu'une fois.
- README : section « Textes de l'interface » mise à jour (clés, OFFLINE_MESSAGES, démarrage, exception photosPermission).
Ne couvre pas : le comportement en simulateur (constat de l'orchestrateur, non rejoué) ; l'exécution réelle d'AuthProvider (aucun test dédié) ; le rendu visuel rouge/gris ; Android ; la partie web.

Bloquants : 0

Majeurs : 0

Mineurs : 3
- Aucun test automatisé ne couvre la logique offline / 401 / keepPreviousData d'AuthProvider (seuls OfflineScreen, DynamicForm, AvatarPicker et locale sont testés). La preuve repose sur le constat simulateur.
- retry() appelle messagesQuery.refetch() même quand la requête est désactivée (langue pas encore connue) : il peut charger les textes sous une langue provisoire. Sans conséquence visible, car la clé est par langue.
- Repli technique `field.label ?? field.name` dans les accessibilityLabel des champs : un nom de champ peut être lu si l'API envoie un libellé nul. Cas peu probable ; texte non rédactionnel.
