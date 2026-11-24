# `2026-11-24` — Le statut « En attente de paiement » et la note du formulaire à la modification

Complète : 2026-11-24-partenaire-note-creation.md

- **Type** : décision
- **Question posée** : « ajouter un nouveau statut attente paiement. La note lors de la modification s'affiche uniquement si : en validation, refusé, attente de paiement, affiché » (avec les textes de chaque cas).
- **Ce qui est retenu** :
  - **Nouveau statut** `awaiting_payment` (`PartnerStatusEnum::AWAITING_PAYMENT`), « En attente de paiement » :
    - pastille orange dans l'app, comme « En validation » (l'app n'a que quatre tons) ;
    - bleue au back-office (`info`), pour la distinguer ;
    - sa classe de notification : `PartnerAwaitingPayment` ;
    - son titre et son texte de notification (fr, en, de).
    - Il n'est pas affiché : seul un partenaire publié peut l'être.
    - Il apparaît de lui-même dans le choix du statut au back-office et dans le filtre « Statut » des notifications.
    - La colonne (texte de 20 caractères) l'accepte : pas de migration.
  - **Note du formulaire de l'app, à la modification**, selon le statut (`PartnerForm::notice()`) :
    - en validation : « Ce partenaire est encore en cours de validation et n'est pas affiché sur l'application. » ;
    - refusé : « Ce partenaire a été refusé et n'est pas affiché sur l'application. » ;
    - en attente de paiement : « Ce partenaire est en attente de paiement et n'est pas affiché sur l'application. » ;
    - publié et affiché : « Ce partenaire est affiché sur l'application. » ; le paiement des partenaires activé : « Ce partenaire est affiché sur l'application jusqu'au [date de fin]. » ;
    - publié mais pas affiché (campagne pas commencée, paiement expiré) : pas de note.
  - À l'ajout, la note reste celle de 2026-11-24-partenaire-note-creation.
- **Tranché par** : humain (le statut, les cas, les textes français) ; session (les tons, les textes anglais et allemands, pas de note pour un partenaire pas affiché)
