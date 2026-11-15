# Skills propres au projet

Un répertoire par skill : `.ia/skills/<nom>/SKILL.md`, avec en tête

```
---
name: <nom>
description: <ce que fait le skill, et quand l'utiliser>
---
```

Le plugin `bza` les expose à Claude Code par un lien `.claude/skills/<nom>`
(local au poste, jamais commité). Un nouveau skill est disponible à partir de la
session suivante.
