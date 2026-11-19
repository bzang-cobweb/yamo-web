# RETEX — liste à choix multiples, intervalle, équipements (2026-11-19)

## Ce qui s'est produit deux fois

- **Une partie du contrat laissée de côté sans le dire** : le back-office de l'intervalle. La QA l'a relevée, comme la borne des vedettes au chantier précédent.
  - Règle candidate : avant la QA, relire chaque puce du contrat et cocher ce qui est fait. Un écart volontaire se note dans « décidé seul », pas dans le silence.
- **Une règle d'ordre (max ≥ min) vérifiée par l'API ne sert à rien quand l'API ignore les filtres invalides** : sur un écran sans envoi (les filtres), la validation doit être faite par l'app.
- **Les textes de l'app sont chargés au démarrage** : un texte nouveau apparaît sous forme de clé (« form.rangeMin ») jusqu'au redémarrage. C'est déjà arrivé pendant les vérifications au simulateur. Il faut redémarrer l'app avant d'en conclure à un oubli.

## Règle ré-implémentée / domiciliée

- **Les deux nouveaux types** sont domiciliés dans le module Form : enum, `Field`, `FieldComponent` et composants de l'app. Le domaine ne fait que les utiliser.
- **Le compte du badge** n'a plus de règle spéciale (`_min`/`_max`) : un filtre égale un nom.
