# `2026-11-26` — Le destinataire lit le titre enregistré ; les autres, le titre calculé pour eux

Remplace en partie : 2026-11-26-titre-calcule.md (le titre calculé pour tout lecteur)

- **Type** : décision
- **Question posée** : « title de data = title() à l'envoi, et si c'est le destinataire on ne fait plus appel à title(), on lit title de data directement » ; puis « reset booking ».
- **Ce qui est retenu** :
  - à l'envoi, `data.title` est déjà écrit par la même fonction que `title()` du module, pour le destinataire, dans sa langue (une demande : `BookingNotification::title()` ; un partenaire : `PartnerNotification::title()` ; un message : « Message ») ;
  - à l'affichage (`NotificationResource::title()`), le lecteur qui a **reçu** la notification lit `data.title` tel quel ; le lecteur qui ne l'a pas reçue (sa propre action, le fil d'un groupe) lit le titre que `title()` calcule pour lui, dans sa langue ; à défaut (un type sans module), `data.title` ;
  - le titre suit ainsi le reste des données : figé à l'envoi pour le destinataire, rédigé à l'affichage (`forReader()`) pour les autres.
- **Motif** : une seule règle pour toutes les données d'une notification ; un calcul de moins par carte reçue.
- **Corollaires** :
  - un destinataire qui change de langue garde ses anciens titres dans l'ancienne ; un texte de titre modifié ne s'applique qu'aux notifications envoyées ensuite ;
  - les demandes de démo d'avant le type dans les titres ont été recréées (`booking:reset`) : leurs titres enregistrés sont les titres actuels.
- **Tranché par** : humain
