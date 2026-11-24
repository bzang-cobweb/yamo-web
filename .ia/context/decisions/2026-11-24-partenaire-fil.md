Remplacée par : 2026-11-24-notifications-fil-de-groupe.md (le fil partner:group, partagé par tous les comptes)

# `2026-11-24` — Un fil par partenaire, sans table de demandes

Remplace : 2026-11-24-partenaire-demandes.md (la table `partner_requests` et le fil par demande ; la règle « le back-office ne prévient que les administrateurs » reste)

- **Type** : décision de conception
- **Question posée** : « je ne comprends donc pas pourquoi on a encore besoin de PartnerRequest », puis « supprime PartnerRequest ».
- **Ce qui est retenu** :
  - La table `partner_requests` est retirée, avec le modèle `PartnerRequest`, `PartnerRequestService` et `data.partner_request_id`.
    - Sa migration n'avait jamais été poussée : le fichier est supprimé plutôt que défait par une autre migration.
  - Toutes les notifications d'un partenaire forment un seul fil chez chaque destinataire : `Partner::thread($compte)`, de la forme `partner:<id>:<compte>`.
    - Une carte par partenaire réunit ajouts, modifications et réponses de l'équipe.
    - Sa page montre le suivi : « Partenaire ajouté », « Partenaire modifié », « Partenaire accepté »…
  - Les actions s'appellent `PartnerNotification::CREATED` et `UPDATED`.
  - Les règles d'envoi ne changent pas :
    - depuis l'app, tous les comptes rattachés sont prévenus ;
    - au back-office, seuls les administrateurs, jamais l'utilisateur du back-office.
- **Motif** :
  - Avec un fil par partenaire, la table ne servait plus qu'à garder un historique de validations.
  - Personne ne l'a demandé : « en validation » se lit déjà sur le statut du partenaire.
- **Corollaires** :
  - Le filtre « Statut » porte sur la dernière étape de la carte : un partenaire accepté depuis ne sort plus sous « En validation ».
  - Le suivi d'un partenaire disparaît avec ses notifications, au bout de 90 jours (`notification:prune`).
  - En local, les notifications de partenaire déjà enregistrées ont été rattachées au fil de leur partenaire.
- **Tranché par** : humain
