# `2026-11-26` — Les modifications d'un partenaire : « Dernière modification » et « Modifié par » en tête

Complète : 2026-11-26-partenaire-dernieres-modifications.md

- **Type** : décision
- **Questions posées** : « rajouter 2 lignes : date de la dernière modification et l'auteur » ; « Modifié le puis Modifié par comme les 2 premières lignes, et garder que 2 lignes ouvertes » ; « changer Modifié le par Dernière modification ».
- **Ce qui est retenu** :
  - sur la page d'une notification d'un partenaire, les lignes de ses dernières modifications commencent par « Dernière modification » (la date et l'heure, `DisplayTime::date(…, 'll LT')` : « 26 nov. 2026 17:01 ») puis « Modifié par » (le nom du compte, lu à l'affichage, en lien vers sa page) ; viennent ensuite les champs changés, la description en dernier ;
  - les deux premières lignes restent les seules ouvertes ; les champs changés sont derrière « Voir plus » ;
  - les deux lignes se lisent dans `partners.last_changes` (`at`, `by`) : rien de nouveau n'est enregistré ; un compte qui n'existe plus : pas de ligne « Modifié par » ;
  - en anglais « Last updated » / « Updated by », en allemand « Zuletzt geändert » / « Geändert von ».
- **Portée** : `AddPartnerToNotificationThread`, traductions.
- **Tranché par** : humain (les deux lignes, leur place, deux lignes ouvertes, « Dernière modification ») ; session (le format de la date, le lien vers l'auteur)
