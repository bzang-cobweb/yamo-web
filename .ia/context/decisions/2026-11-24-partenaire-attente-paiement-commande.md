# `2026-11-24` — Commande : les partenaires publiés sans période payée passent en attente de paiement

- **Type** : décision
- **Question posée** : « faire une commande qui, si le paiement est activé, va passer tous les statuts des partenaires qui ne sont pas en attente de paiement ou de validation au statut en attente de paiement si expires_at est passé ou null » ; précisé : les publiés seulement.
- **Ce qui est retenu** :
  - `partner:await-payment` (`AwaitPartnerPaymentsCommand`), planifiée chaque nuit à 00:10 (`routes/console.php`).
  - Paiement des partenaires non appliqué : rien.
  - Appliqué : chaque partenaire **publié** dont `expires_at` est passé ou vide passe « En attente de paiement ». Son dernier jour, aujourd'hui, est encore payé (2026-11-24-periode-payee-en-jours).
  - En validation, en attente de paiement ou refusé : aucun changement.
  - Chaque changement passe par le modèle : il est annoncé comme au back-office (l'auteur de la dernière demande ; ses administrateurs par e-mail et SMS).
- **Tranché par** : humain (la règle ; publiés seulement)
