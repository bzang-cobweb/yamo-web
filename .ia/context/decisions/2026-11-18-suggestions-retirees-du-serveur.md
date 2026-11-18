# `2026-11-18` — `Field::suggestions()` est retirée du serveur

- **Type** : décision
- **Remplace en partie** : `2026-11-18-suggestions-retirees-de-l-app.md`, dont le corollaire gardait `Field::suggestions()` côté serveur.
- **Question posée** : retirer aussi du serveur la capacité, désormais servie à personne.
- **Réponse** : « Yes, remove it ».
- **Portée** : module Form (`Field`) : méthode, propriété et clé `suggestions` des champs servis à l'app.
- **Corollaires** :
  - les suggestions de rue du back-office (Filament, `AddressService::suggest()`) ne sont pas touchées ;
  - réintroduire des suggestions dans l'app demandera de reprendre le serveur et une version de l'app.
- **Tranché par** : humain
