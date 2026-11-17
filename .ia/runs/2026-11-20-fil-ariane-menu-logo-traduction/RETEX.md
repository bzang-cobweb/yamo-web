# RETEX — 2026-11-20-fil-ariane-menu-logo-traduction

## Ce qui s'est produit deux fois

- Une modification de `_layout.tsx` n'apparaît qu'après un redémarrage de Metro avec `--clear` et une relance d'Expo Go. C'est la deuxième fois, après le chantier du 2026-11-19.
  - **Règle candidate** (projet) : après toute modification d'un `_layout.tsx`, redémarrer Metro avec `--clear` avant de vérifier visuellement.
- Une adresse de fichier construite depuis `APP_URL` (HTTPS) est illisible par l'app en local, qui joint l'API en HTTP. Le cas s'était déjà présenté pour l'avatar.
  - **Domicile trouvé** : le trait `App\Http\Resources\Concerns\ServesMediaOnRequestHost`, à utiliser pour toute adresse de média servie par l'API.

## Règles ré-implémentées / domiciliées

- Domiciliée : fil d'Ariane d'une fiche ou d'une liste dans deux traits (`app/Filament/Pages/Concerns`). Les surcharges de `getBreadcrumb()` répétées dans chaque page sont supprimées.
- Domiciliée : groupe de menu partagé entre l'application et les modules (`App\Filament\NavigationGroup`).

## Obstacle

- Le navigateur intégré ne fait pas confiance au certificat DDEV, ce qui a bloqué la vérification visuelle du back-office.
