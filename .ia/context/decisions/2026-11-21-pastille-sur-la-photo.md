# Mes logements : la pastille des demandes en attente sur la photo, à la place du statut

Remplace : 2026-11-21-pastille-demandes-en-attente.md (pour la place de la pastille)

- **Date** : 2026-11-21
- **Type** : décision
- **Décidé par** : l'humain (« mettre la pastille sur la photo à la place du statut »)

## Décision

Sur une carte avec photo, la pastille orange « N demandes en attente » se met sur la photo, à la
place de la pastille du statut (« Publiée »). Elle reste un lien : la toucher ouvre les
notifications des demandes en attente de ce logement. Sans demande en attente, le statut revient à
sa place. Sans photo, la pastille reste sous le titre.

## Mécanique

Dans l'app (`ProfileListScreen`, `ItemPicture`) : le premier compteur qui a un ton va sur la photo,
dans une pastille touchable. Le serveur ne change pas (le ton vient de 2026-11-21-pastille-demandes-en-attente).

## Motif

Une seule pastille sur la photo, celle qui demande une action.

## Portée

Toutes les listes de cartes avec photo dont un compteur a un ton ; aujourd'hui, « Mes logements ».
