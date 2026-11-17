# `2026-11-19` — Back-office : traductions saisies champ par champ

Remplace : 2026-11-15-plugin-traduction-filament.md

- **Type** : décision
- **Question posée** : (demande humaine) « en backend enlever le switch de langue et
  le mettre au niveau de chaque champ ou groupe de champs traduisible ».
- **Réponse** :
  - plus de sélecteur de langue de page ;
  - chaque champ ou groupe de champs traduisible est présenté avec un onglet par
    langue de `config('app.supported_locales')`, par
    `App\Filament\Schemas\TranslatableTabs` ;
  - un champ obligatoire l'est dans chaque langue.
- **Motif** : voir et saisir toutes les traductions d'un champ au même endroit, sans
  changer de langue pour toute la page.
- **Portée** : back-office (pays, régions, réglages de l'application et tout futur
  champ traduisible).
- **Corollaires** :
  - le plugin `lara-zeus/spatie-translatable` n'est plus enregistré dans le panneau
    ni utilisé ;
  - le paquet reste installé : le retirer de `composer.json` demande l'accord de
    l'humain.
- **Tranché par** : humain (présentation en onglets : orchestrateur)
