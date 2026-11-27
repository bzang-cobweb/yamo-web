# `2026-11-27` — Écrire aux membres d'un partenaire depuis son fil

Complète : 2026-11-24-notifications-fil-de-groupe.md, 2026-11-27-repondre-en-panneau.md

- **Type** : décision
- **Question posée** : « détail notification partenaire remplacer bouton mes partenaires par un formulaire comme une notification message » ; précisé : « les membres du partenaire uniquement mais une seule notification est envoyé à un membre qui n'est pas l'auteur comme pour les autres notifications sauf qu'ici ce n'est pas l'utilisateur configuré dans le backoffice ».
- **Ce qui est retenu** :
  - sur la page d'une notification du fil d'un partenaire, « Écrire » remplace « Mes partenaires » pour un membre qui peut écrire : le formulaire `partner.message` (titré du nom du partenaire, le message sans libellé affiché), dans un panneau à sa hauteur ;
  - le message (`PartnerMessageReceived`) entre dans le fil du partenaire, de l'auteur (`user_id`) : une seule notification dans l'onglet, à un membre qui n'est pas l'auteur (l'auteur de la dernière action depuis l'app s'il est membre et n'écrit pas, sinon le premier autre membre, administrateurs d'abord) ; les autres administrateurs par e-mail et SMS ; jamais le compte du back-office ;
  - tous les membres le lisent dans le fil : l'étape « Message » et le texte en bulle ;
  - peut écrire un compte rattaché au partenaire (404 sinon) s'il y a un autre membre (403 sinon) : `PartnerPolicy::message()`, route `POST /api/v1/partners/{partner}/messages`.
- **Tranché par** : humain
