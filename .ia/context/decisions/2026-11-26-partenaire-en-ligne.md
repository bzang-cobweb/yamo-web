# `2026-11-26` — Partenaire : « En ligne » à la place de « Publié » et « Affiché »

- **Type** : décision
- **Question posée** : « pourquoi dans mes partenaires cards j'affiche statut "Affiché" mais dans détail notification j'ai "publié" » ; puis « partenaire status : garder "En ligne" à la place de affiché et publié ».
- **Ce qui est retenu** :
  - le statut `published` d'un partenaire s'appelle « En ligne » (`partner::partner.statuses.published`) : pastille d'une notification, filtre « Statut », back-office ; en anglais et en allemand « Online » ;
  - dans « Mes partenaires », un partenaire publié et visible s'appelle aussi « En ligne » (`partner::partner.member.status.shown`, au lieu de « Affiché ») ; publié mais pas visible (campagne pas commencée, finie ou non payée) reste « Pas affiché » ;
  - ne changent pas : le titre et le texte d'une notification (« Partenaire accepté », « … a été accepté par l'équipe ») et le champ « Publié » du back-office (`fields.published`).
- **Motif** : le même mot sur la carte de « Mes partenaires » et sur la page d'une notification.
- **Portée** : module Partner (traductions fr, en, de) ; tests.
- **Tranché par** : humain
