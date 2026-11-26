# `2026-11-26` — Une modification de partenaire : « Slogan (avant) », « Slogan (après) »

Complète : 2026-11-26-partenaire-dernieres-modifications.md

- **Type** : décision
- **Questions posées** : « laisser une ligne entre contenus avant et après » ; puis « faire plutôt des libellés genre slogan (avant) et slogan (après) ».
- **Ce qui est retenu** : sur la page d'une notification d'un partenaire, chaque champ modifié donne deux lignes : « Slogan (avant) » et « Slogan (après) », chacune avec sa valeur, sans guillemets ; un champ vide se dit « (vide) » (`PartnerNotification::changeLines()`). En anglais « (before) / (after) », en allemand « (vorher) / (nachher) ». Les deux premières lignes restent visibles, la suite derrière « Voir plus ». L'e-mail garde une ligne par champ, « « avant » → « après » ».
- **Portée** : `AddPartnerToNotificationThread`, `PartnerNotification`, traductions.
- **Tranché par** : humain
