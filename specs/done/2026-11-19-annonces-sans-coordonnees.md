# Annonces sans coordonnées

Demande du 2026-11-19 :

> contrôler que le propriétaire ne mette pas des informations ou indices de contact dans tous les champs de saisies

Précisions de l'humain :
- **réaction** : « Refuse the save » — l'enregistrement est refusé tant que la coordonnée reste ;
- **périmètre** : « All offer texts » — titre, description et tout attribut texte libre du domaine, dans toutes les langues, depuis l'app comme depuis le back-office.

## Ce que j'ai compris

Une annonce ne doit pas permettre de joindre le propriétaire en dehors de l'application. Quand un texte de l'offre contient une coordonnée ou un indice de contact, l'enregistrement est refusé. Une erreur s'affiche sous le champ fautif :

> Retirez les coordonnées (téléphone, e-mail, site, réseau social) : elles ne sont pas autorisées dans une annonce.

Rien n'est enregistré tant que la coordonnée n'est pas retirée.

### Ce qui est détecté

On compare les textes en minuscules et sans accents, et on ignore les séparateurs habituels (espaces, points, tirets, parenthèses, barres obliques).

1. **Téléphone** : au moins 8 chiffres qui se suivent, avec ou sans « + » ou « 00 » devant.
   - Exemples : « 699 12 34 56 », « +237 6 99-12-34-56 », « 00237699123456 ».
   - **Exception** : un montant suivi d'une devise n'est pas un téléphone (« 250 000 000 FCFA », « 1 200 000 XAF »). Les surfaces, années et nombres courts ne sont pas visés.
2. **Chiffres écrits en lettres** : une suite d'au moins 7 chiffres écrits en mots, en français ou en anglais.
   - Exemple : « six neuf neuf un deux trois quatre ».
3. **E-mail** : une adresse complète (« awa@gmail.com »), ou masquée avec « arobase », « (at) », « [at] » ou « at » entre deux mots, suivie d'un domaine.
4. **Site ou lien** :
   - « http:// », « https:// » ou « www. » ;
   - un nom de domaine avec une extension courante (« .com », « .cm », « .fr », « .net », « .org », « .ch »…) ;
   - les liens courts de messagerie (« wa.me », « t.me »).
5. **Réseaux et messageries** : un mot de la liste, mot entier, insensible à la casse.
   - Liste : WhatsApp (et « wtsp », « whats app »), Telegram, Signal, Viber, Messenger, Facebook, Instagram, « insta », TikTok, Snapchat, Twitter, X.com, LinkedIn.
6. **Formules d'appel au contact**, en français et en anglais.
   - Exemples : « appelez-moi », « contactez-moi au », « mon numéro », « mon tél », « joignable au », « écrivez-moi », « call me », « my number », « text me ».
7. **Pseudonyme** : « @ » suivi d'un nom (« @awa_ngono ») qui n'est pas un e-mail.

Les listes de mots (réseaux, formules, chiffres en lettres) sont rangées dans un fichier de traduction par langue. On les complète sans toucher au code.

### Où

- **Une règle de validation générique** `NoContactDetails`, dans le module Form :
  - réutilisable par tout formulaire et par tout domaine ;
  - un champ texte l'active avec `->withoutContactDetails()` ;
  - elle est servie à l'API comme au back-office, comme les autres règles d'un `Field`.
- **Champs contrôlés** :
  - dans l'app (formulaire `offer.offer`) : le titre, la description, et chaque attribut du domaine de type texte ou zone de texte ;
  - dans le back-office (ressource Offres) : les mêmes champs, **dans chaque langue** (onglets de traduction).
- **Hors contrôle** :
  - les avis des visiteurs ;
  - le profil ;
  - les adresses (une adresse est privée, jamais publiée) ;
  - les offres déjà enregistrées : elles ne sont pas modifiées, et le contrôle s'applique à leur prochain enregistrement.

### App

Rien de nouveau à construire : l'erreur 422 de l'API s'affiche déjà sous le champ concerné.

### Hors périmètre

- **Photos** : un numéro écrit sur une photo (reconnaissance de texte).
- **Analyse par IA** ou score de suspicion.
- **Modération a posteriori** des offres existantes : liste ou alerte dans le back-office.
- **Messagerie interne** pour contacter le propriétaire.

## Découpage

1. **Serveur** :
   - la règle `NoContactDetails` et ses listes fr/en ;
   - `Field::withoutContactDetails()` ;
   - l'application aux champs de l'offre (app et back-office) ;
   - les tests, avec de nombreux exemples : ce qui doit être refusé, et ce qui doit passer.
2. **App** : vérifier au simulateur que l'erreur s'affiche sous le champ.
3. **QA indépendante** (`bza-dev:qa-check`).

## Critères de succès

- Enregistrer une offre dont le titre, la description ou un attribut texte contient un téléphone, un e-mail, un lien, un réseau social, une formule d'appel ou un pseudonyme est refusé (422). L'erreur est sous ce champ ; rien n'est enregistré.
- Les mêmes textes sont refusés dans le back-office, dans chaque langue.
- Les textes ordinaires d'une annonce passent, par exemple :
  - « 3 chambres, 85 m², 2ᵉ étage » ;
  - « Loyer 250 000 FCFA, caution 2 mois » ;
  - « Disponible en 2026 » ;
  - « Proche de l'université de Douala » ;
  - « Climatisé, eau chaude 24h/24 ».
- On peut ajouter un mot interdit en éditant le fichier de traduction, sans toucher au code.
- La suite de tests serveur passe.

## Ce que j'ai décidé seul

- **Séparateurs d'un téléphone** : « _ » s'ajoute aux espaces, points, tirets, parenthèses et barres obliques. Une lettre O collée à un chiffre compte pour un zéro (« 6O 9O 12 34 56 »).
- **Pas un téléphone** : une date (« 01.12.2026 », « 01/12/2026 », « 01 12 2026 ») et deux années (« 2026-2027 », « 2026/2027 »).
- **Listes de mots** : plus larges que les exemples du contrat (« wtsp », « watsap », « fb », « imo »… ; « bipez-moi », « texto au », « contactez nous », « me contacter »…). « snap » est retiré à la suite de la QA, à cause d'un faux positif.
- **Domaine** : `Domain::inputAttributes()` contrôle d'office tout attribut texte. Un domaine n'a rien à déclarer.
- **Ordre des contrôles** : quand un texte contient plusieurs coordonnées, le premier genre trouvé l'emporte (e-mail, lien, pseudonyme, téléphone, chiffres en lettres, réseau, formule). Le message est le même pour tous.

## Ce qui a été fait

- **Module Form** :
  - `ContactDetails::find()` et la règle `NoContactDetails` ;
  - `Field::withoutContactDetails()`, réservé aux textes ;
  - les listes `form::contact` en fr et en en.
- **Module Offer** :
  - le titre et la description du formulaire `offer.offer` refusent les coordonnées, ainsi que tout attribut texte du domaine ;
  - le back-office les refuse aussi, dans chaque onglet de langue (titre, description, attributs).
- **Tests** :
  - `ContactDetailsTest` : 36 textes à refuser, 19 textes ordinaires, le message, l'opt-in et l'ajout d'un mot sans code ;
  - `OfferApiTest` : création et modification ;
  - `TranslationTest` : attribut du domaine et back-office par langue ;
  - Form, Offer et tests/Feature : 188 tests passent.
- **Simulateur** : un titre avec « 699 12 34 56 » et une description avec « WhatsApp » affichent l'erreur sous chaque champ ; rien n'est enregistré.
- **QA indépendante** : NO-GO (barres obliques), corrigé, puis GO. Rapport : `.ia/runs/2026-11-19-annonces-sans-coordonnees/qa-check.md`.

## Ce que je n'ai pas vérifié

- **Back-office** : l'erreur n'a pas été vue dans le navigateur ; seuls les tests Livewire la couvrent.
- **Contournements non promis par le contrat** :
  - « +237 » seul ;
  - des chiffres pleine chasse ;
  - un e-mail sans arobase (« awa gmail ») ;
  - une extension de domaine hors liste (« monsite.xyz », « .ci ») ;
  - un raccourcisseur de liens (« bit.ly ») ;
  - un nombre écrit en toutes lettres (« six cent quatre-vingt-dix-neuf »).
- **Annonces réelles** : le taux de faux positifs sur de vraies annonces est inconnu. Seuls environ 50 textes ordinaires ont été essayés.

## Ce qui reste en suspens

- **Faux positif connu, conforme au contrat** : une référence ou un montant sans devise de 8 chiffres ou plus (« Réf. 12345678 ») est refusé.
- **Offres déjà enregistrées** : aucune liste ni alerte dans le back-office pour les offres qui contiennent déjà des coordonnées.
- **Photos, avis et profil** : non contrôlés.
