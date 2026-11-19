# RETEX — langue de saisie d'une offre, et l'allemand

- **OpenAPI régénéré trop tôt** : régénéré avant la dernière modification d'une route (validation d'étape avec `record`), il est parti faux et la QA a rendu NO-GO. Règle candidate : régénérer l'OpenAPI juste avant le commit, après le dernier changement de code serveur.
- **Scramble et `validate()`** : valider un paramètre d'adresse avec `$request->validate()` (ou `Validator::make($request->query())`) le fait documenter comme corps de requête ; un paramètre d'adresse se déclare avec `#[QueryParameter]` et se lit sans validateur.
- **Le simulateur a trouvé un bug que les tests ne voyaient pas** : la validation d'étape ignorait l'enregistrement (les étapes d'une édition diffèrent désormais de celles d'une création). Les tests d'API appelaient la bonne route sans le cas « étapes différentes ». Deuxième fois qu'un parcours réel révèle ce que les tests unitaires manquent : garder le passage au simulateur sur chaque changement de formulaire.
- **Ajouter une langue casse les tests qui figent la liste** : fixer les langues des tests dans `TestCase` (fr/en) et faire déclarer l'allemand aux tests qui en parlent ; un test de parité (`GermanTranslationsTest`, `MessagesApiTest`) garde l'allemand complet.
- **Règle domiciliée** : le repli « demandée → d'origine → défaut → autre » vit dans `OriginalLocale`, utilisé par les modèles traduits de l'offre.
- **Mineur QA non traité** : une valeur `record` invalide est ignorée sans que l'OpenAPI le dise.
