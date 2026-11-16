RAPPORT QA — chantier 2026-11-16-genre-date-naissance, étape 1 (web), tâche 03
Go : oui (borné)
Base du verdict : rejoué moi-même
- `ddev artisan test` : 107 passés (401 assertions), 0 échec.
- `./vendor/bin/pint --test` : passed.
- `ddev artisan migrate:status` : 2026_11_16_120000_add_gender_and_birth_date_to_users [4] Ran. Migration : gender string(16) nullable, birth_date date nullable.
- openapi : `scramble:export --fail-on-unknown` vers un fichier temporaire du conteneur, `cmp` avec openapi.json : IDENTIQUE (le fichier du dépôt n'a pas été touché).
- Lecture du diff : GenderEnum (male, female, libellés via auth::user.genders.*), cast du modèle, FieldType::DATE (date_format:Y-m-d), Field::date, formulaire (gender radio required, birth_date date non requis), PersonalInfoService (4 champs), UserResource, initiales (Str::ascii), Filament (Radio required ; DatePicker minDate 1900-01-01, maxDate now), liste Filament sans diff.
- Essai réel sur http://yamo.ddev.site avec le compte jetable qa-gd@example.test (« Élodie Ängel ») :
  - GET /api/v1/forms/auth.profile.personal : first_name/last_name required true ; gender type radio, required true, options male=Homme / female=Femme ; birth_date type date, required false. La propriété `required` est bien envoyée.
  - PATCH personal-info sans genre : 422 « Le champ Genre est obligatoire. » ; gender=other : 422 « Le champ Genre est invalide. ».
  - birth_date 2999-01-01 : 422 ; 1899-12-31 : 422 « …postérieure ou égale au 1er janvier 1900. » ; 01/02/2000 : 422 (format).
  - birth_date = aujourd'hui (2026-11-16) : 200 ; 1900-01-01 : 200 (bornes incluses).
  - birth_date null puis "" : 200, birth_date null (effacée).
  - Envoi de name="hack" et email="x@example.test" avec le reste : ignorés (name reste qa-gd-tmp, email inchangé).
  - UserResource : full_name « Élodie Ängel », initials « EA », gender, birth_date « AAAA-MM-JJ » ou null.
  - Nettoyage : comptes @example.test supprimés via tinker, comptage final = 0.

Ne couvre pas :
- Filament : exigence du genre et bornes de la date vérifiées par lecture du code et par le test de création (qui passe), non rejouées dans le navigateur ni par un test dédié (les tests existants ne couvrent pas le refus sans genre ni les bornes côté Filament).
- Message anglais des bornes (Accept-Language en) non rejoué.
- Tout le côté mobile (tâche 02), le commit.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- Français incorrect : la date future est refusée avec « …antérieure ou égale au aujourd'hui. » (lang/fr/validation.php, values.birth_date.today = « aujourd'hui », inséré après « au »). Le message est traduit (pas « today »), mais la grammaire est fautive ; formulation à revoir (par ex. « à aujourd'hui » n'est pas possible avec le gabarit « au :date », il faut une valeur qui s'accorde, ou une règle de message dédiée).
- Pas de test Filament dédié au genre requis ni aux bornes de la date de naissance (seule la création nominale est testée).
