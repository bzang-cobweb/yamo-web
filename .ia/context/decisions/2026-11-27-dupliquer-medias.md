# `2026-11-27` — Gestionnaire de médias : dupliquer fichiers et dossiers ; contenus sans renommer ni déplacer

- **Type** : décision
- **Question posée** : « ajouter les options renommer, dupliquer et déplacer » — renommer et déplacer existaient pour les fichiers du gestionnaire ; que faire pour les médias des contenus, et que peut-on dupliquer ?
- **Réponse** :
  - les médias des contenus (photos d'offres, logos…) ne se renomment ni ne se déplacent : ils se **dupliquent** dans un dossier du gestionnaire, où la copie se renomme et se déplace librement ; l'original reste attaché à son contenu ;
  - on duplique les **fichiers et les dossiers** (un dossier avec ses sous-dossiers et leurs fichiers).
- **Motif** : renommer une photo d'offre change son adresse, la déplacer la retire de son offre ; la copie donne la liberté sans casser les contenus.
- **Portée** : paquet `bza/laravel-media-manager`.
- **Corollaires** (orchestrateur) :
  - action « Dupliquer » : nom (celui de l'original par défaut) et destination (le même dossier par défaut quand on peut y ajouter) ; un nom pris reçoit un numéro (`plan-1.pdf`, `Photos (1)`) ;
  - il faut « Voir » et « Télécharger » sur la source, « Ajouter » sur la destination ; un fichier va toujours dans un dossier, un dossier peut aller à la racine ;
  - la copie garde l'extension et les métadonnées, pas les autres propriétés propres au modèle d'origine ;
  - un dossier copié n'emporte pas ses droits : il hérite de son nouveau parent ; seuls les sous-dossiers que l'utilisateur peut voir et télécharger sont copiés ;
  - la limite de taille des téléversements ne s'applique pas à une copie.
- **Tranché par** : humain (contenus, périmètre) ; orchestrateur (corollaires)
