# RETEX — 2026-11-17-formulaires-profil-generiques

- **Deuxième itération sur le même besoin** (menu en back-office → menu en code avec `path` → formulaires génériques) : le but réel, « ajouter un lien sans nouvelle version de l'app », n'était pas posé au départ. Règle candidate (projet) : pour toute donnée servie à l'app, demander d'emblée « faut-il pouvoir la changer sans nouvelle version de l'app ? ».
- **Règle domiciliée** : « le serveur décrit l'envoi d'un formulaire » vit dans `Modules/Form/Support/Submission` ; méthode et chemin sont lus sur la route, pas recopiés.
- **Règle ré-implémentée puis supprimée** : chaque écran du profil portait sa propre suite (retour, code, déconnexion) ; elle n'existe plus qu'une fois, dans `ProfileFormScreen`.
- **Critère mal écrit** : « un test ajoute un cas d'énumération » nommait un mécanisme impossible ; reformulé en résultat observable (formulaire + route nouveaux servis et envoyables).
- La QA a signalé à tort l'absence d'un test d'écran pour le formulaire sans envoi (il existe) : vérifier chaque mineur avant de corriger.
