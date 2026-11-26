# `2026-11-26` — Les cartes de notifications tronquent toujours leur texte

- **Type** : décision
- **Question posée** : « notifications cards : tronquer toujours le message peu importe le type (définir un nombre max de caractères à afficher ; attention aux messages avec balises) ».
- **Ce qui est retenu** :
  - sur la carte d'une notification, quel que soit son type, le texte (`summary`, à défaut `body`) et le message sont tronqués à `NOTIFICATION_CARD_MAX` (120) caractères visibles, suivis de « … » ; la coupe tombe à la fin d'un mot quand c'est possible ;
  - avant de compter : les balises (`<b>`, `<a href="…">`) sont retirées, jamais coupées en deux ; les retours à la ligne et les espaces répétés deviennent une espace (un message sur plusieurs lignes tient sur la carte) ; un « < » qui n'ouvre pas de balise reste ;
  - un passage lié du texte (le nom de l'offre, d'un membre) n'est jamais coupé : il reste entier, ou disparaît avec la suite ;
  - la description lue par le lecteur d'écran garde le texte entier, sans balises ; la page de la notification montre tout ;
  - le tout dans `mobile/src/features/notification/utils/preview.ts` (`plainText`, `truncate`, `truncateSegments`) et `NotificationText` (`maxLength`).
- **Motif** : une carte garde une hauteur lisible, quelle que soit la longueur du message.
- **Portée** : la liste des notifications (app).
- **Tranché par** : humain (tronquer toujours, un maximum de caractères, attention aux balises) ; session (120, la coupe en fin de mot, les passages liés entiers, les retours à la ligne)
