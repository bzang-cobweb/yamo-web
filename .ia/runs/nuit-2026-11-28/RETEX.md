# Retour d'expérience — nuit du 2026-11-28

- **API écrite d'abord dans le contrat** : serveur et app écrits en parallèle par deux sous-agents d'après la même
  section « API » ; deux écarts seulement à l'intégration (erreurs du défi sur `code`, date de naissance selon l'âge
  minimum). Règle candidate : pour un chantier serveur + app, écrire l'API exacte dans le contrat avant de découper.
- **Bout en bout contre le serveur local** (Mailpit pour lire le code) : peu coûteux, et c'est lui qui a montré
  l'effet de l'âge minimum réglé en local. À refaire à chaque changement d'API d'authentification.
- **Deux fois** : un « cookie » ou une valeur de sécurité implicite dans la demande (numéro non vérifié, cinquième
  essai) a dû être tranché après coup ; les cas de sécurité méritent une ligne dans les critères dès le contrat.
