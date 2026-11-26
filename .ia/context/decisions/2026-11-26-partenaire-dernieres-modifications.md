# `2026-11-26` — Les dernières modifications d'un partenaire, gardées sur lui et montrées en lignes

Remplace en partie : 2026-11-26-partenaire-modifications.md (les changements dans les données et le message de la notification)

- **Type** : décision
- **Question posée** : « sauvegarder les modifications dans un nouveau champ sur partners et l'afficher dans notification detail comme BookingSummary. La notification est envoyée si et seulement s'il y a eu des modifications ».
- **Ce qui est retenu** :
  - une colonne `partners.last_changes` (JSON, nullable ; migration `2026_12_09_120000_add_last_changes_to_partners`) garde la dernière modification depuis l'app : `changes` (champ, avant, après), `by` (le compte), `at` ;
  - `PartnerService::change()` la remplit quand un champ a changé, et n'envoie la notification que dans ce cas ;
  - la notification ne porte plus les changements dans ses données ni dans son message ; l'e-mail les liste toujours ;
  - la page d'une notification sert un champ générique `lines` (libellé, valeur ; `null` par défaut, comme `heading`) ; pour le fil d'un partenaire, `AddPartnerToNotificationThread` y met ses dernières modifications : « Slogan » — « « avant » → « après » », un champ vide « (vide) » ;
  - l'app les affiche sous l'en-tête par `SummaryLines`, le composant des lignes et de « Voir plus » sorti de `BookingSummary`, qui l'utilise aussi (bza-dev:composition) ;
  - les étapes du suivi gardent leur libellé (« Partenaire modifié ») ; la ligne en italique de la carte ne montre plus les changements.
- **Motif** : la dernière version de ce qui a changé, au même endroit que les lignes d'une demande.
- **Corollaires** : seule la dernière modification est gardée ; une modification de l'équipe au back-office ne la remplit pas.
- **Tranché par** : humain (le champ sur partners, l'affichage comme BookingSummary, la notification seulement s'il y a des modifications) ; session (`by` et `at`, le champ générique `lines`, `SummaryLines`)
