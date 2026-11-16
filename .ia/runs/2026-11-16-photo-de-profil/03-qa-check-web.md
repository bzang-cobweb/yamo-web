RAPPORT QA — étape 1 (web), tâche 03 — photo de profil
Go : oui
Base du verdict : rejeu personnel.
- `php artisan test` : 80/80, 273 assertions. `./vendor/bin/pint --test` : passed. `tests/Feature/RouteGuardTest.php` : 2/2 ; aucun fichier de `tests/` modifié par le diff hors AuthApiTest (+1 ligne `avatar_url`), donc aucune nouvelle exception.
- Rejeu HTTP réel sur https://yamo.ddev.site avec le compte jetable diag-avatar (id 6), curl multipart :
  * GET /me : `avatar_url` renvoyé (https://yamo.ddev.site/storage/<uuid>/conversions/avatar-thumb.webp). Sans jeton, POST et DELETE : 401.
  * Fichier texte : 422, messages en français ; fichier 6 Mo : 422 dont « ne peut pas dépasser 5120 kilo-octets » ; après ces deux refus, disque inchangé (même uuid, 2 fichiers).
  * Envoi d'un PNG 2000x1200 : 200, nouvel uuid, un seul dossier sur le disque (l'ancien a disparu) ; avatar.webp = WebP 512x307, vignette = WebP 256x256, 5,3 Ko contre PNG 12 Ko ; aucun original stocké (find sur storage/app/public).
  * DELETE : 200, `avatar_url` null, plus aucun fichier sur le disque.
  * Vignette servie en 200 image/webp en https et en http (public/storage -> ../storage/app/public, lien relatif, ignoré par git).
  * Adresse : uuid aléatoire, pas de numéro séquentiel (UuidPathGenerator).
  * Routes sous auth:sanctum, uniquement sur `$request->user()` : aucune route ne vise un autre compte.
- openapi : `scramble:export --fail-on-unknown` régénéré dans ddev vers un chemin temporaire, comparé par cmp : identique à openapi.json (routes avatar et `avatar_url` présentes).
- .ddev/config.yaml : upload_dirs = `../storage/app/public`.
- Tests présents pour suppression de compte (critère 8) et Filament (6) : passés dans les 80.

Ne couvre pas : rendu visuel du back-office en navigateur (critère 6 vérifié seulement via les tests Filament/Livewire), suppression de compte rejouée seulement par le test automatisé (aucune suppression en base), vrai HEIC, image réelle > 5 Mo valide (fichier aléatoire testé), partie mobile, `ddev restart` pris en compte de upload_dirs, comportement du disque en production.

Bloquants : 0

Majeurs : 0

Mineurs : 2
- Les refus empilent plusieurs messages pour un même fichier (ex. « doit être une image » + « type : jpg… » + « taille de l'image non conforme ») ; le dernier, sur les dimensions, est trompeur pour un fichier non image.
- Après le rejeu, le compte diag-avatar a une photo (uuid 10f6e810-a6c5-45c9-868c-fa3e681805af, PNG aléatoire de test) à la place de la photo initiale.
