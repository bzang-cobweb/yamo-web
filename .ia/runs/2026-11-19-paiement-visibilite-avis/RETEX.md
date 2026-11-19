# RETEX — un avis par défaut, appliquer le paiement, visibilité d'une offre

- **Règle domiciliée** : « qui voit une offre, à part son auteur » était recopiée à la main (publiée + pays) dans la policy (fiche, avis, favori), l'accueil (liste, épinglées, villes) et les favoris. Elle a maintenant un domicile : `Offer::visibleToOthers()` / `isVisibleToOthers()`. Tout nouvel écran qui montre des offres à d'autres doit passer par là.
- **Une valeur servie plutôt qu'un seuil dans l'app** : le bouton « Voir tous les avis » compare le nombre total au nombre servi, au lieu d'un seuil codé (« dès deux avis ») ; changer la configuration ne demande pas de version de l'app.
- **Tests qui figeaient une constante** : trois tests supposaient 5 avis servis ; ils fixent désormais la configuration qu'ils lisent.
- **Mineur QA non traité** : la conservation des données par la migration de renommage n'est pas testée (`renameColumn`, réversible).
