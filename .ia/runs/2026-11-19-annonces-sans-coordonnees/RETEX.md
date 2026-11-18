# RETEX — annonces sans coordonnées (2026-11-19)

## Ce qui s'est produit deux fois

- **Le contrat promettait un séparateur que le code ignorait** (« / »), et seule la QA l'a vu. Les exemples de test reprenaient mes cas, pas la lettre du contrat.
  - Règle candidate : pour une règle de détection, écrire **un test par élément énuméré dans le contrat** (chaque séparateur, chaque forme), avant d'ajouter ses propres exemples.
- **Un test qui lit le sous-titre a été oublié** lors du déplacement sous-titre → `highlight` (`tests/Feature/CountrySpaceTest.php`). Il n'a été vu qu'à la suite complète.
  - C'est la même leçon que dans le RETEX des avis : après un changement de forme d'un item de liste, lancer la **suite complète** avant de commiter, pas seulement les modules touchés.

## Règle ré-implémentée / domiciliée

- **Contrôle « pas de coordonnées »** : il vit dans le module Form (règle et `Field`), et un domaine en hérite par `Domain::inputAttributes()`.
- **Back-office** : Filament reprend la même règle par `FieldComponent` pour les attributs ; seuls le titre et la description, déclarés à la main, la répètent explicitement.

## Divers

- Pour essayer un formulaire au simulateur, l'app s'arrête sur les champs requis vides avant d'envoyer. Il faut toucher « Enregistrer » une fois le clavier fermé.
