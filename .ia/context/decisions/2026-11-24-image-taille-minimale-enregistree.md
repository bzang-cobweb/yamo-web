Remplacée en partie par : 2026-11-24-image-regle-minimum-par-cadre.md

# `2026-11-24` — Taille minimale à l'envoi : la taille enregistrée

Remplace : 2026-11-24-image-taille-minimale-max.md

- **Type** : décision
- **Question posée** : « lorsqu'on ouvre en grand l'avatar on sert l'image de base ; normalement le minimum doit être 720px non ? » → « ok donc utilisez storedSize pour obtenir le minimum ».
- **Réponse** : la demande elle-même.
- **Ce qui est retenu** :
  - `ConfiguredImageService::minUploadSize()` = `storedSize()` : l'original plafonné quand il est gardé (`original_size`), sinon la plus grande conversion ; toujours sur le plus grand côté, par `MinImageSide` (`uploadRules()`, `Field::minImageSide()`) ;
  - photo de profil : 720 px (au lieu de 288) ; logo : 720 px et photos d'une offre : 1920 px, inchangés ;
  - recadrer ne doit toujours jamais agrandir (`store()`), ce qui protège un enregistrement direct (back-office, démo) qui n'est pas validé.
- **Motif** : l'image enregistrée peut s'ouvrir en grand (la photo de profil) ; ni elle ni une conversion ne doit être un agrandissement.
- **Portée** : `ConfiguredImageService` ; tests de la photo de profil, de la suppression de compte et de la classe.
- **Corollaires** : une photo de profil de moins de 720 px est refusée ; aucun écran ne sert encore l'image de base de la photo de profil (seulement en repli).
- **Tranché par** : humain
