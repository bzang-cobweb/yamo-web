RAPPORT QA — 2026-11-16-genre-date-naissance, étape 2 (mobile), tâche 04
Go : oui
Base du verdict : rejoué moi-même. npm test : 13 suites, 51 tests verts. npm run typecheck : vert. npm run lint : "No issues found". package.json : une seule ligne ajoutée, @react-native-community/datetimepicker 9.2.1, identique à bundledNativeModules.json et au package-lock. curl /api/v1/messages : form.clearDate = "Effacer" (fr), "Clear" (en). Relecture du code : DateField et utils/dates.ts (AAAA-MM-JJ construit et sérialisé en heure local, sans toISOString ni new Date(string) ; max aujourd'hui, min 1900-01-01 ; "Effacer" seulement si !required et date présente, vide = '' ; affichage Intl avec la locale de l'app) ; case 'date' dans FormField ; User avec full_name, initials, gender, birth_date ; Avatar sur user.initials ; ProfileScreen sur user.full_name ; grep initialsOf et .first_name/.last_name hors tests dans src/ : aucun résultat ; useFormDefinition gcTime: 0 avec justification en commentaire ; README à jour (type date, full_name/initials, définition non gardée en mémoire) ; plugin datetimepicker ajouté dans app.json ; aucun rien à ajouter pour le genre (RadioField existant).
Ne couvre pas : le comportement sur simulateur (calendrier natif en français, jours grisés, repli, flux d'enregistrement, valeurs en base, rouverture du formulaire, avatar « EA ») : repris du constat de l'orchestrateur, non rejoué. Android (DateTimePickerAndroid) non exécuté, seulement relu. Le test de gcTime n'a pas été rejoué en conditions réelles. Web non revérifié.

Bloquants : 0

Majeurs : 0

Mineurs : 1
- DateField.tsx : ligne vide superflue avant </FieldShell> (cosmétique, lint passe).
