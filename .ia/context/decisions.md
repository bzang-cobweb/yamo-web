# Registre des décisions

Source de vérité des arbitrages du projet. **Une réponse humaine qui ne vit que
dans une messagerie se perd, et la question est reposée.**

Le registre est le répertoire **`.ia/context/decisions/`** : **une entrée, un
fichier**, nommé `AAAA-MM-JJ-<sujet-court>.md`. Ce fichier-ci n'en contient
aucune : il explique le registre et porte le modèle d'une entrée. Voir
`bza:lois/escalade.md`.

Alimenté dans le **même cycle** que la réception de la réponse. L'accusé de
réception d'un arbitrage est le commit de son fichier, pas un message.

Tout agent lit **tous les fichiers du répertoire** au démarrage. Aucun point qui
y figure ne se re-signale, ni comme défaut, ni comme question, ni « pour
confirmation ».

## Pourquoi un fichier par entrée

Plusieurs agents consignent en parallèle. Dans un fichier unique, ils écrivent
tous au même endroit : chaque fusion bloque sur un conflit, et une copie
périmée commitée par-dessus efface sans bruit les entrées des autres. Un
fichier par entrée supprime les deux.

## Règles d'écriture

- **On crée, on ne réécrit pas.** On ne modifie jamais l'entrée d'un autre.
- **Remplacer** : une entrée qui en change une autre est un nouveau fichier qui
  porte `Remplace : AAAA-MM-JJ-<sujet>.md` en tête. L'ancien fichier reste
  intact, à une ligne près : `Remplacée par : AAAA-MM-JJ-<sujet>.md`, ajoutée en
  tête. Une entrée marquée `Remplacée par` ne s'applique plus.
- **Pas d'index tenu à la main** : il recréerait le conflit que le répertoire
  évite. Un index éventuel est généré.
- **Transition** : les entrées écrites ici avant le passage au répertoire restent
  valables tant qu'elles n'ont pas été migrées en fichiers.

---

## Modèle d'une entrée

Copier le modèle du type voulu dans `.ia/context/decisions/AAAA-MM-JJ-<sujet>.md`.

### Décision

```markdown
# `AAAA-MM-JJ` — <titre court de la décision>

- **Type** : décision
- **Question posée** : <ce qui a été demandé, en une phrase>
- **Réponse** : <ce qui a été tranché, sans interprétation>
- **Motif** : <pourquoi — c'est ce qui permettra de savoir quand la décision expire>
- **Portée** : <ce que la décision couvre, et ce qu'elle ne couvre pas>
- **Corollaires** : <ce qui en découle mécaniquement et que personne n'a énoncé>
- **Tranché par** : <humain | orchestrateur>
```

### Risque assumé — ne pas re-signaler

> Point identifié, évalué, et **accepté en connaissance de cause**. Sans cette
> entrée, chaque nouvel audit le remonte comme un défaut et le même arbitrage
> est repayé.

```markdown
# `AAAA-MM-JJ` — <le point>

- **Type** : risque assumé
- **Ce que c'est** : <description factuelle>
- **Pourquoi c'est assumé** : <le raisonnement>
- **Ce qui le rouvrirait** : <la condition qui rendrait le sujet à nouveau ouvert>
```

### Conséquence de conception assumée

> Effet d'une décision que personne n'avait énoncé, et qui se relirait dans six
> mois comme un bug à corriger. L'écrire au moment où il est accepté est la
> seule façon de ne pas le « corriger » par erreur plus tard.

```markdown
# `AAAA-MM-JJ` — <la conséquence>

- **Type** : conséquence de conception
- **Découle de** : <la décision d'origine>
- **Ce que ça implique concrètement** : <le comportement observable>
- **Ce n'est pas un défaut** : <pourquoi c'est voulu>
```

### Règle de blocage

> Toute règle qui bloque ou sérialise du travail porte son motif. Quand le motif
> tombe, la levée est une **nouvelle entrée** qui remplace celle-ci — sinon les
> agents réappliquent la règle depuis leurs mémoires.

```markdown
# `AAAA-MM-JJ` — <ce qui est bloqué>

- **Type** : règle de blocage
- **Règle** : <ce qui est bloqué ou sérialisé>
- **Motif** : <pourquoi>
- **Statut** : en vigueur | levée (voir l'entrée qui remplace celle-ci)
```
