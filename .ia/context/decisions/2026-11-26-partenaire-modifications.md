Remplacée en partie par : 2026-11-26-partenaire-dernieres-modifications.md (les changements gardés sur le partenaire et montrés en lignes, plus dans la notification)

# `2026-11-26` — La modification d'un partenaire garde seulement ce qui a changé

- **Type** : décision
- **Question posée** : « est-ce qu'on peut conserver juste les modifications du partenaire lors d'une modification ? » → « oui, les enregistrer ».
- **Ce qui est retenu** :
  - `PartnerService::change()` compare les champs que modifie un membre avant et après (le nom, le slogan, la description, le lien, le libellé du bouton dans la langue saisie ; l'e-mail et le téléphone de contact) ; la liste des champs changés passe par `PartnerSubmitted` jusqu'à `PartnerNotification` ;
  - la notification enregistre `changes` (`field`, `old`, `new`) et son `message` : un champ par ligne, « Slogan : « avant » → « après » », un champ vide dit « (vide) » (fr, en, de) ; ce message est celui de l'étape dans le suivi, la ligne en italique de la carte, et une ligne de l'e-mail ;
  - pour un lecteur qui ne l'a pas reçue (son auteur, un autre compte du partenaire), `forReader()` réécrit la notification avec ses changements ;
  - une modification sans aucun changement n'envoie plus de notification ;
  - l'ajout d'un partenaire et les actions de l'équipe au back-office ne changent pas (pas de liste de changements).
- **Motif** : voir ce qui a été modifié sans comparer soi-même deux versions.
- **Portée** : module Partner (service, événement, listener, notifier, notification, filtre, traductions) ; tests.
- **Corollaires** : dans le suivi, la bulle d'une modification montre ses changements à la place du libellé « Partenaire modifié », qui reste le titre de la carte ; les modifications enregistrées avant n'ont pas de changements.
- **Tranché par** : humain (enregistrer les changements) ; session (les champs comparés, le format, aucune notification sans changement)
