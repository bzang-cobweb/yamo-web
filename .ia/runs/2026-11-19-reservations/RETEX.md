# RETEX — 2026-11-19-reservations

- **Le calendrier de l'app bloquait toute date future** depuis l'origine (bornes 1900 → aujourd'hui codées en dur) : vu seulement en essayant de réserver au simulateur. Règle candidate (projet) : une capacité générique (champ date) ne porte pas de borne propre à un cas (la naissance) ; les bornes viennent du serveur. Domicile trouvé : `Field::dateRange()`.
- **Une suite Jest qui ne démarre pas passe inaperçue** quand on filtre la sortie sur « Tests: » et « ✕ » : l'erreur « Test suite failed to run » ne s'affiche pas. S'est produit une fois, attrapé par la QA indépendante. Règle candidate (marketplace) : lire aussi « Test Suites: » et vérifier « N passed, N total ».
- **Tests à plusieurs comptes** : sous Sanctum, le garde garde le premier compte d'un test ; `forgetGuards()` à chaque changement de compte. Déjà rencontré ailleurs ? À domicilier dans une aide de test commune si cela se reproduit.
- **Garde-fou « id »** : une commande contenant `value('id')` est refusée par le hook ; écrire `getKey()`.
- Règle ré-implémentée : aucune ; « lien du menu selon le compte » a trouvé son domicile dans `ProfileMenuLinks` (condition recevant le compte).
