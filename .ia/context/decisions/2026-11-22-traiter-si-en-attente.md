# Page d'une demande : « Traiter la demande » seulement si elle est en attente

- **Date** : 2026-11-22
- **Type** : décision
- **Demandé par** : l'humain (« détail d'une demande, mettre le bouton traiter la demande uniquement si la demande est dans le statut en attente »)

## Décision

Sur la page d'une demande, le propriétaire ne voit le bouton « Traiter la demande » (vers le calendrier du logement) que tant qu'elle attend une réponse : en attente et pas expirée. Une fois la demande acceptée, refusée, annulée, terminée ou expirée, la page n'a plus de barre de bouton en bas pour lui. Les boutons du membre ne changent pas.

## Portée

`AddBookingToNotificationThread`, qui retire `link` et `link_label` pour le propriétaire. L'app n'affiche pas la barre sans lien.
