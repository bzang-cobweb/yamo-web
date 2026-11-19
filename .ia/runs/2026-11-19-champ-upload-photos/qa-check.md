# RAPPORT QA — 2026-11-19-champ-upload-photos
Go : oui

Base du verdict (rejoué moi-même) :
- Serveur : `ddev exec php artisan test` -> 498 passed (2397 assertions). Fichiers OfferPhotoApiTest + FieldTest relancés : 28 passed.
- App : jest 42 suites / 227 tests verts ; `npx tsc --noEmit` exit 0 ; `npm run lint` exit 0.
- composer.json / composer.lock / package.json / package-lock.json : aucun diff (critère 9).
- `schedule:list` : `model:prune --model=FormUpload` toutes les heures (critère 5) ; test de purge à 24 h présent et vert.
- Lecture du code : route auth:sanctum + throttle:60,1 + can viewForm ; champ inconnu/non-upload -> 404 ; fileRules (file, mimetypes par contenu, max, dimensions) ; fichiers sur disque local privé (storage/app/private), nommés par uuid, aucun nom client dans le chemin (pas de traversée) ; `pending()` filtré par user_id + champ + < 24 h ; `distinct` sur les ids (doublons -> 422) ; max:20 et required (21 photos / 0 photo -> 422) ; photo d'une autre offre refusée (ids connus = ceux de l'offre en cours seulement) ; sync dans une transaction ; app : limites lues dans `field.upload`, galerie seule, bouton d'enregistrement désactivé et `submit` bloqué pendant un envoi.

Ne couvre pas : vérification au simulateur / rendu réel de l'app ; rendu back-office Filament du champ (FileUpload) non rejoué ; liste et fiche (critère 7) vérifiées par lecture (OfferDetailResource lit `getMedia(Offer::PHOTOS)` dans l'ordre), pas par parcours visuel ; envois réellement concurrents ; pas de test de charge du limiteur de débit.

Bloquants : 0

Majeurs : 0

Mineurs : 4
- Course sur la sauvegarde : deux enregistrements simultanés citant le même fichier en attente peuvent faire échouer le second (fichier déjà retiré, `Image::load` -> 500 au lieu de 422). Cas rare, non rejoué.
- La transaction DB ne protège pas les fichiers médias déjà écrits : si l'enregistrement échoue après `addMedia`, les fichiers WebP déjà copiés peuvent rester orphelins (les uploads en attente, eux, restent et seront purgés à 24 h).
- Le contrôle de type côté app est figé sur `image/jpeg` (le sélecteur recompresse en JPEG) : si Laravel retire `image/jpeg` des types acceptés, l'app refuse tout avec « mauvais type » alors que le serveur accepterait un PNG ; cohérent mais limité. De plus, la spec disait « rend {id, url} » ; la réponse ne rend que `{id}` (les tests et l'app s'y conforment).
- Le nom de champ `photos` est codé en dur dans `OfferPhotoService::sync` (`FormUpload::pending(..., 'photos')`) ; acceptable tant qu'il n'y a qu'un champ upload par formulaire.
