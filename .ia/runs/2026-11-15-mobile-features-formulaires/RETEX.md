# RETEX — chantier 2026-11-15-mobile-features-formulaires

## Ce qui s'est produit deux fois → règles candidates

1. **Metro sert un code périmé** (2 fois : types de routes absents, `DynamicForm` sans le
   dernier changement) : un test navigateur « échoue » sur du vieux code. Règle candidate
   (projet mobile) : après une modification hors de l'écran affiché, ou un nouveau fichier de
   route, redémarrer Expo avec `--clear` avant de conclure ; vérifier dans le fichier servi
   (`curl …/<route>.bundle | grep`) en cas de doute.
2. **Le garde-fou et le classifieur bloquent des commandes légitimes** : texte `.env` dans une
   commande, `id` isolé, et commandes réécrites par un hook (`rtk`) à relancer telles quelles.
   Déjà noté au chantier précédent — à porter au marketplace (`bash-guard`).
3. **Une valeur de configuration mutée à l'exécution** (`app.locale`, réécrite par
   `App::setLocale()`) lue comme une constante : bug trouvé par un test. Règle candidate (tous
   projets Laravel) : une valeur par défaut qui doit rester stable a sa propre clé de
   configuration, jamais `app.locale`.

## Autres leçons

- **Changements de demande en cours de chantier** (2 : pseudonyme au chantier précédent,
  Login/Logout/Profile ici) : traités par une décision qui remplace, un avenant au contrat, une
  vérification qa-check dédiée — sans réécrire ce qui était commité.
- **L'audit lancé en parallèle d'une création de module** a pris l'échafaudage généré pour un
  travail concurrent : lancer l'audit **avant** de créer des fichiers dans son périmètre.
- **Testing Library 14** rend `render` et `fireEvent` asynchrones ; **TypeScript 6** n'inclut
  plus les types globaux (`types: ["jest"]`).

## Règles : ré-implémentée ou domiciliée ?

- **Domiciliée** : champs et règles d'un formulaire déclarés une fois (module `Form`) — l'écran
  mobile et la FormRequest lisent la même source.
- **Domiciliée** : la langue par défaut (`Locales::default()`), la langue courante côté mobile
  (`useI18n()`), le choix de la meilleure langue (`pickBestLocale`).
- **Domiciliée** : le refus d'un jeton (401/403) en un seul endroit (`ApiError.rejectsToken`).
- **À surveiller** : les règles de validation du compte vivent encore à trois endroits
  (formulaire Filament, `auth:create-user`, formulaires de l'API) — candidates à un formulaire
  du module `Form` réutilisé par la commande.
