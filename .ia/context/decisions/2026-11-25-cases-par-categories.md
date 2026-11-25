Remplacée par : 2026-11-25-categories-slug-traduit.md (enregistrement par id, racines par slug traduit)

# `2026-11-25` — Règles et caractéristiques en cases à cocher, tirées des catégories d'offre

Remplace : 2026-11-25-oui-non-trois-positions.md

- **Type** : décision
- **Question posée** : « remplacer les choix non précisé / oui / non par des catégories. mettre les existants dans un seeder. une catégorie Caractéristiques qui contient toutes ces sous-catégories ».
- **Réponses de l'humain** : utiliser `OfferCategory` et garder l'enregistrement actuel ; des cases à cocher ; une catégorie parente « Règles » au même niveau que « Caractéristiques ».
- **Ce qui est retenu** :
  - **Deux catégories racines** dans `offer_categories`, au même niveau, créées par le seeder du domaine (`RentalDomain::categories()`), puis gérées au back-office :
    - « Règles » (`rules`) : Animaux acceptés (`pets`), Fumeurs acceptés (`smoking`), Fêtes et événements autorisés (`parties`) ;
    - « Caractéristiques » (`features`) : Caméras de surveillance (`cameras`), Forage (`borehole`), Groupe électrogène (`generator`).
  - **La saisie** : deux champs de cases à cocher, « Règles » dans l'étape Règles et « Caractéristiques » dans l'étape Caractéristiques. Leurs cases sont les enfants de la racine, dans l'ordre du back-office ; une case ajoutée au back-office apparaît sans nouvelle version de l'app. Le back-office de l'offre a les mêmes cases.
  - **L'enregistrement ne change pas** : chaque case cochée s'écrit sous son slug dans `offers.details` (`"pets": "yes"`), une case décochée est effacée. Le slug d'une case est donc sa clé, et ne doit pas changer.
  - **La fiche** : une ligne « Oui » par case cochée, sous son nom, dans « Règles » ou « Caractéristiques » ; un « no » enregistré avant les cases n'est plus montré.
  - **Les filtres** Animaux, Fumeurs, Fêtes restent : « Oui », la case cochée ; « Non », pas cochée (rien d'enregistré compris).
  - **La catégorie d'une offre** (« Catégorie ») ne propose ni « Règles » ni « Caractéristiques » ni leurs enfants.
  - **Technique** : `Field::optionsUsing()` / `labelUsing()` calculent options et libellé à la première lecture, pour ne pas interroger la base au chargement du domaine. Le choix à trois positions et ses aides (`Field::NOT_SPECIFIED`…) sont retirés.
- **Tranché par** : humain (catégories d'offre, cases, deux racines, enregistrement inchangé) ; session (« Non » = pas cochée dans les filtres, les ancien « no » plus montrés)
