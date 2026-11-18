---
name: projet-domaine
description: Initialiser ce projet pour un domaine (location de logements, boutique, rencontre…) avec la commande project:init — choix du domaine, du nom, de la langue, des pays et des canaux, lancement, vérification, consignation. À utiliser quand on démarre un nouveau projet à partir de ce socle, ou qu'on change le domaine d'un projet.
---

# Initialiser le projet pour un domaine

Le socle est générique ; le **domaine** (une classe `Domain` du module Offer)
décide de ce qu'est une offre : libellés, attributs, catégories, capacités
(décisions `2026-11-18-offres-et-domaines` et
`2026-11-18-initialisation-par-domaine`). La commande `project:init` fait tout le
travail ; ce skill la prépare, la lance et vérifie le résultat. Il n'écrit
jamais dans `.env` lui-même.

## 1. Lire l'état

- Variables présentes, **sans leurs valeurs** :
  `sh "<plugin id>/scripts/env.sh" keys .env`, avec le chemin complet de l'outil
  `env` (skill `id:env` ou `bza:env`). Ne jamais ouvrir `.env`.
- Domaines connus : `ddev artisan project:init --list`.
- Offres existantes : si le projet en a déjà et que le domaine change, la commande
  exige `--force`. Le signaler à l'humain avant.

## 2. Demander, au format d'escalade

Une seule série de questions, chacune avec une recommandation et le défaut
appliqué sans réponse :

| Réglage | Option | Défaut |
|---|---|---|
| Domaine | `--domain=` | aucun : à choisir |
| Nom de l'application | `--name=` | le nom actuel |
| Langue par défaut | `--locale=` | `fr` |
| Pays actifs | `--country=CH` (répétable) | `CH` |
| Canaux de compte | `--channels=email,phone` | les deux |

**Domaine absent de la liste** (boutique, rencontre…) : ne pas l'inventer ici.
Proposer d'ouvrir son chantier avec `bza:lead` : classe `Domain` (libellés,
attributs en `Field`, catégories en arbre, capacités, modules requis), textes
fr/en, inscription dans `OfferServiceProvider`, tests (formulaire, validation,
liste). Le domaine `test.shop` de `Modules/Offer/tests/Feature/DomainTest.php`
sert de modèle.

## 3. Lancer

```
ddev artisan project:init --domain=<clé> --name="<nom>" --locale=<langue> --country=<CODE> --channels=<canaux> --no-interaction
```

- Ne jamais passer `--admin-email` : la création du super administrateur demande
  un mot de passe, que seul l'humain saisit. Lui donner la commande à lancer :
  `ddev artisan auth:create-user --super-admin`.
- Production : la commande refuse sans `--force` ; ne l'ajouter que sur demande
  explicite de l'humain.
- Lire le résumé : ce qui a changé dans `.env` (« rien » si tout était déjà en
  place) et ce qui reste à faire (secrets, e-mail, SMS).

## 4. Vérifier

- `ddev exec php artisan test` : tout passe.
- Avec un compte de test `@example.test` au rôle `member` (créé puis supprimé) :
  `GET /api/v1/auth/me/menu` contient le lien du domaine (« Mes logements ») et
  `GET /api/v1/forms/offer.offer` contient les attributs du domaine.
- Back-office : les ressources Offres et Catégories sont visibles pour un compte
  qui a `offers.*`.

## 5. Consigner

Une entrée au registre (`bza:decision`) : domaine retenu, nom, langue, pays,
canaux, et qui l'a décidé. Commiter par chemin ; `.env` n'est jamais commité.

## Règles absolues

- Jamais de lecture ni d'écriture directe de `.env` ; seule la commande y écrit,
  et seulement `APP_NAME`, `APP_LOCALE`, `OFFER_DOMAIN`, `AUTH_CHANNEL_EMAIL`,
  `AUTH_CHANNEL_PHONE`.
- Jamais de mot de passe saisi par Claude.
- Jamais de domaine inventé dans ce skill : un nouveau domaine est un chantier.
