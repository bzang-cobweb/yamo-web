# RETEX — règles et informations pratiques d'un logement

- **Demande d'outil, réponse de modèle** : la demande était un éditeur de texte riche ; la question « is there a better way » a permis de proposer des champs structurés, sans dépendance. À refaire : quand une demande nomme un mécanisme, proposer le résultat et le comparer avant de bâtir.
- **Règle domiciliée** : « quels attributs sont des règles » vit dans le domaine (`ruleAttributes()`, `informationAttribute()`) ; la fiche, le back-office et le formulaire la lisent.
- **tinker** : `artisan tinker <fichier>` reste interactif après le script (commande bloquée, arrêtée) ; `ddev exec php artisan tinker --execute "…"` casse les `$` (guillemets doubles). Règle candidate : un script PHP qui démarre l'application lui-même, lancé par `ddev exec php <fichier>`.
- **Données de démonstration modifiées pour une capture** : sauvegardées avant, remises à l'identique et vérifiées après.
- **openapi.json dépend de la base locale** (énumération des pays ouverts) : un gros diff sans changement d'API ; à garder en tête avant de le commiter.
