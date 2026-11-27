# `2026-11-27` — Le champ note reçoit ses bornes, son incrément et ses étoiles du serveur

Complète : 2026-11-27-note-au-dixieme.md

- **Type** : décision
- **Question posée** : « le champ laravel doit passer le min et le max et un boolean qui indique si on affiche les étoiles », puis « passer aussi l'incrément en paramètre ».
- **Ce qui est retenu** : `Field::rating()` se règle par `->ratingRange($min, $max, $step)` (1, 5 et 0,1 par défaut) et `->stars(bool)` (vrai par défaut). La validation suit ces réglages (« numérique, entre min et max, multiple de l'incrément ») ; l'app les lit : la note sur son maximum (« 7,5 / 10 »), les décimales de l'incrément, le curseur et l'accessibilité au pas de l'incrément, les étoiles seulement si demandées. Le formulaire d'avis : `ratingRange(1, 5, 0.1)->stars()`.
- **Tranché par** : humain
