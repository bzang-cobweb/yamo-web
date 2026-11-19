# `2026-11-19` — Fiche de sa propre offre : « Modifier » dans la barre

- **Type** : décision
- **Question posée** : « si je visualise mon offre, aligne le prix au milieu de la booking bar », puis « annule ma dernière demande et mets plutôt le bouton éditer dans ce cas ».
- **Réponse** : la seconde demande.
- **Ce qui est retenu** : sur la fiche de sa propre offre, la barre fixe garde le prix à gauche et montre à droite « Modifier » (« Edit », « Bearbeiten »), servi par Laravel (`edit: {label, form}`) seulement à l'auteur qui peut la modifier ; il ouvre le formulaire de l'offre lié à elle ; après l'enregistrement, la fiche se recharge. Le prix centré est annulé (commit de retour, la version était déjà poussée).
- **Motif** : la demande de l'humain.
- **Portée** : `OfferDetailResource` (`edit`), textes `offer::offer.edit` ; app (`BookingBar`).
- **Corollaires** : aucun.
- **Tranché par** : humain
