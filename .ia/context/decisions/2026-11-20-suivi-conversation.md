# Page d'une notification : une zone fixe et le suivi en conversation

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-suivi-demande.md, 2026-11-20-entete-premiere-etape.md
- **Remplace en partie** : 2026-11-20-coordonnees-notification.md (l'app n'affiche plus l'e-mail ni le téléphone sur cette page)

## Décision

La page d'une notification se divise en deux :

- **Une zone fixe en haut**, qui ne défile pas : le type en gras et la pastille du
  statut à droite, le texte de la demande et son total. Plus de photo, de nom ni de coordonnées au-dessus.
- **Dessous, ce qui défile** : le suivi de la demande comme une conversation, de la
  plus ancienne étape à la plus récente. Les étapes du lecteur à droite, nommées
  « Toi », sans photo ; celles de l'autre partie à gauche, avec la photo de qui l'a
  faite dans un petit cercle et son nom, qui ouvrent sa page. Dessous, le message (à
  défaut le libellé de l'étape, « Demande acceptée »), puis la date, alignée à droite.
  Une notification sans demande montre son message et sa date.

Le côté se décide dans l'app : une étape est au lecteur si qui l'a faite est lui.

## Motif

Demandes de l'humain du 2026-11-20 : « summary et pastille statut à droite (zone fixe
qui ne scroll pas avec le contenu) », « afficher à gauche les notifications de
l'utilisateur … une petit cercle avec sa photo suivi de son nom et en dessous de son nom
le message de la notification suivi de la date aligné à droite », puis, corrigeant « le
propriétaire à droite » : « celui qui lit est toujours à droite et son cercle n'est pas
affiché et son nom est toujours affiché "toi" », et, pour les coordonnées : « il faut les retirer, le
click se fera sur le nom et le cercle dans la conversation ».

## Complément du même jour

- Les dates de la page : l'heure seule le jour même (« 14:05 »), sinon la date et
  l'heure (`formatStamp`).
- Ses propres étapes : « Toi » (en « You », de « Du ») à la place de son nom, sans
  photo. Demande de l'humain : « ne pas afficher son propre nom, mettre juste toi et
  enlever son cercle ».
- Le texte de la zone fixe ne nomme plus qui a fait la demande (`summary`, à défaut
  `body`). Demande de l'humain : « Ne plus mettre l'utilisateur dans le summary de la
  notification ».

## Portée

La page d'une notification dans l'app. Pas les cartes de la liste.

## Corollaires

- Une étape avec un message (le motif d'un refus) ne montre pas son libellé : la
  pastille du haut dit le statut.
- L'API sert encore l'e-mail et le téléphone de qui agit sur cette page ; l'app ne les
  affiche plus.
