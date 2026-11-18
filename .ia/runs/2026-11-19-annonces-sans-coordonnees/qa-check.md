RAPPORT QA — 2026-11-19-annonces-sans-coordonnees (VERDICT FINAL, après correction)
Go : oui
Base du verdict (rejeu) :
- `ddev exec php artisan test Modules/Form Modules/Offer tests/Feature` : 188 passed, 735 assertions, 0 échec (storage/app/qa-tests2.txt).
- Sondes tinker : 22 textes à refuser (dont « 699/12/34/56 », « 699_12_34_56 », « O6 99 12 34 56 », « 6O 9O 12 34 56 », « O6O6O6O6O6 », « whatsap », « contactez nous au », « me contacter », « joindre au », « appel au 699 ») : tous refusés, 0 manqué. 48 textes ordinaires (dates à barres, 2026/2027, 24h/24, 2/5, « 2 chambres/1 salon », « 100 000/150 000/200 000 FCFA », montants, adresses, horaires, « Un snap sur la cuisine ») : 0 faux positif (storage/app/qa-probe2.txt).
Bloquants : 0 (l'ancien bloquant « / » est levé). Majeurs : 0.
Mineurs restants, non promis par le contrat : « +237 » seul, chiffres pleine chasse, e-mail/site dégradé (« awa gmail », « monsite.xyz »), « Réf. 12345678 » refusé (conforme au contrat).
Ne couvre pas : étape App au simulateur, rendu navigateur Filament, avis/profil/adresses, offres existantes, photos, tests hors des trois dossiers.

--- Premier passage (NO-GO, historique) ---
RAPPORT QA — 2026-11-19-annonces-sans-coordonnees
Go : non
Base du verdict : rejeu personnel.
- `ddev exec php artisan test Modules/Form Modules/Offer tests/Feature` : 179 passed, 726 assertions, 0 échec (sortie : storage/app/qa-tests.txt).
- Sondes directes de `ContactDetails::find` (tinker, lecture seule) : 61 textes à refuser, 50 textes ordinaires (sortie : storage/app/qa-probe.txt).
- Lecture du diff : Field::withoutContactDetails, Domain::inputAttributes, OfferForm (app), OfferForm (Filament : title.xx, description.xx, details.<attr>.xx via FieldComponent qui reprend les règles du Field). Les deux chemins (API et Filament) utilisent la même règle NoContactDetails.
- Critères 1, 2, 3 (les 5 exemples du contrat passent) et 5 : OK. Critère 4 : les listes sont lues via trans() dans Modules/Form/lang/*/contact.php, donc l'ajout d'un mot ne demande aucun code (un test dédié existe et passe).
- Les champs texte du domaine sont couverts sans déclaration : inputAttributes() active la règle sur tout attribut TEXT/TEXTAREA. RentalDomain n'en a aucun aujourd'hui ; la couverture est testée sur un attribut « note ».

Ne couvre pas : l'étape App (simulateur) ; le rendu réel de l'erreur dans le navigateur Filament (seulement Livewire assertHasFormErrors) ; les avis, le profil et les adresses (hors contrat) ; les offres déjà enregistrées ; les photos ; les tests hors des trois dossiers demandés.

Bloquants : 1
- Le contrat dit « on ignore les séparateurs habituels (espaces, points, tirets, parenthèses, barres obliques) », mais la barre oblique n'est pas ignorée. `ContactDetails::find("699/12/34/56")` renvoie NULL : un téléphone séparé par des « / » est accepté. Cause : la regex de hasPhone() (`/\+?\d[\d\s.\-()]*\d/`) ne contient pas « / ». Il faut ajouter la barre oblique aux séparateurs. Les dates « 01/12/2026 » sont déjà exemptées par la règle des dates ; « 24h/24 » et « 2/5 » ne sont pas touchés (moins de 8 chiffres).

Majeurs : 0

Mineurs : 8 (contournements non promis par le contrat, et faux positifs)
Contournements acceptés (non promis) :
- Séparateurs « _ » ou « , » : « 699_12_34_56 », « 699,12,34,56 ».
- « +237 » seul ou « +237 6 » : accepté (moins de 8 chiffres).
- Lettre O à la place de 0 : « O6 99 12 34 56 » est refusé, mais seulement parce qu'il reste 8 chiffres. « 6O 9O 12 34 56 » et « O6O6O6O6O6 » sont acceptés. À l'inverse « 6O 99 12 34 56 » est refusé uniquement pour la même raison.
- Chiffres pleine chasse : « ６９９１２３４５６ » accepté (Str::ascii ne les normalise pas). Les chiffres arabo-indiens sont, eux, refusés.
- E-mail ou site dégradé : « awa @ gmail . com », « awa gmail », « awa chez gmail », « a@b.c », « monsite.xyz », « monsite.ci », « monsite.cd », « exemple .com », « exemple . com », « bit.ly/abc », « site: yamo dot com ». « wa me 237699 » accepté (« wa.me » est refusé).
- Messageries ou appels non listés : « whatsap », « Wa », « what's app », « zap », « tg », « DM », « inbox me », « contactez nous au », « me contacter », « appelez le proprio », « appel au 699 », « joindre au », « mon nº », « ma ligne est le », « passez me voir au 12 rue ». « six cent quatre vingt » et « contact: six neuf » acceptés (moins de 7 chiffres en lettres).
- Aucun de ces textes n'est promis par le contrat, qui annonce explicitement des listes complétables à la main.

Faux positifs (refusés alors qu'ils sont plausibles) :
- « Look at the signal tower nearby » et « Un snap sur la cuisine » : mots du réseau « signal » (prévu au contrat) et « snap » (ajouté hors contrat). Les mots courts « fb » et « imo » sont aussi dans les listes.
- « Prix 15 000 000 000 à débattre » (11 chiffres sans devise) ; « Réf. 12345678 » ; « Réf 2026-0001234 » ; « ID 123 456 78 » : refusés, conforme au contrat (8 chiffres ou plus sans devise).
- « Fully furnished, near the U.S. embassy.com » : traité comme un site (sans conséquence réelle).
- Aucun faux positif sur : 3 chambres/85 m²/2ᵉ étage, loyers FCFA/XAF/EUR/« F CFA »/« francs », « 250.000.000 F CFA », dates (01.12.2026, 01/12/2026, 2026-2027), adresses (« 12 rue de la Paix, 75008 Paris »), horaires 8h-18h, 24h/24, « un deux trois pièces », « one bedroom, two baths… six », GPS, dimensions 5x6.

Constat de preuve : tout est rejouable par la commande donnée ; les sondes ont été exécutées par moi, hors du dépôt suivi (aucun fichier suivi modifié).
