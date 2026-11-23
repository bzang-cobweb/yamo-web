# `2026-11-24` — Taille minimale à l'envoi : la règle tirée du cadre de l'image

Remplace en partie : 2026-11-24-image-taille-minimale-enregistree.md, 2026-11-24-logo-taille-minimale.md

- **Type** : décision
- **Question posée** : « pourquoi a-t-on besoin du MinImageSide ? la validation laravel dimensions ne gère-t-elle pas ce cas ? » → « Oui, corrige ».
- **Réponse** : `dimensions` ne sait pas dire « le plus grand côté », mais c'est la bonne règle pour la photo de profil et les photos d'une offre ; le plus grand côté laissait passer un avatar de 720 × 500 (carré de 500) et une photo en hauteur de 1080 × 1920 (largeur 1080).
- **Ce qui est retenu** :
  - `ConfiguredImageService::uploadRules()` tire la règle du cadre de l'image enregistrée (`bounds(minUploadSize())`) et de sa façon d'y tenir (`fit()`) : recadrée → `dimensions:min_width,min_height` (photo de profil, 720 × 720) ; entière dans un cadre plus haut que large → `dimensions:min_width` (photos d'une offre, 1920 de large) ; plus large que haut → `dimensions:min_height` ; carré → `MinImageSide`, le plus grand côté (logo, 720) ;
  - `Field::uploadRules(array)` remplace `Field::minImageSide()` : l'app reçoit les mêmes règles que le back-office et l'envoi de la photo de profil ;
  - l'aide des photos d'une offre dit « au moins 1920 px de large » ; le message d'un refus `dimensions` est celui de Laravel (« La taille de l'image … n'est pas conforme. »), commun à toutes les règles `dimensions` ; le logo garde le sien.
- **Motif** : la règle juste pour chaque image, sans en choisir une à la main ; Laravel là où il suffit.
- **Portée** : `ConfiguredImageService`, `Field`, `PartnerForm` et `OfferForm` de l'app ; traductions de l'aide des photos ; tests.
- **Corollaires** : une photo d'offre en hauteur doit faire 1920 px de large (2560 de haut en 3:4) ; une photo de profil doit faire au moins 720 × 720.
- **Tranché par** : humain
