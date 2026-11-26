# `2026-11-26` — Carte d'une notification : l'expéditeur avant le texte, le texte à 80 caractères

Complète : 2026-11-26-cartes-notifications-tronquees.md (le maximum passe de 120 à 80)

- **Type** : décision
- **Question posée** : « pourquoi le contenu texte des cards n'est plus tronqué ? Mettre le nom de l'utilisateur avant le texte ».
- **Constat** : le texte était tronqué à 120 caractères, soit environ trois lignes sur un téléphone ; la plupart des textes d'une demande (100 à 120 caractères) passaient entiers, et la coupe ne se voyait pas.
- **Ce qui est retenu** :
  - `NOTIFICATION_CARD_MAX` passe à 80 caractères (environ deux lignes) pour le texte et le message de la carte ; le reste de la troncature ne change pas (balises retirées, passages liés entiers, « … ») ;
  - le nom de qui envoie (en lien vers sa page, sauf si c'est le lecteur) passe avant le texte : le type et la pastille, le nom, le texte, le message, la date ; la description lue par le lecteur d'écran suit le même ordre.
- **Portée** : la liste des notifications (app).
- **Tranché par** : humain (le nom avant le texte) ; session (80 caractères)
