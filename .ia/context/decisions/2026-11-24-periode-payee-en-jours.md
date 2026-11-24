# `2026-11-24` — La période payée en jours, sans heure

- **Type** : décision
- **Question posée** : « transformer starts_at et expires_at en date et pas datetime » ; pour les partenaires et les offres.
- **Ce qui est retenu** :
  - **Colonnes** `starts_at` et `expires_at` des tables `offers` et `partners` : des dates (migrations `2026_12_05_150000` et `2026_12_05_150100`). L'heure enregistrée est perdue, la date est gardée. Les migrations se défont.
  - **`HasPaymentRange`** lit des dates et compte en jours, bornes comprises :
    - commencée le jour de son début ;
    - payée jusqu'à la fin du jour de sa fin : `expires_at >= aujourd'hui` ;
    - `isPaymentRunning()` est ajouté.
  - **La carte de « Mes logements »** (« à partir du », « expire le », « expirée ») suit la même règle.
  - **Back-office** : un sélecteur de date, sans heure, pour les offres et les partenaires ; la liste des partenaires affiche des dates.
- **Corollaire** : une offre ou un partenaire payé jusqu'au 30 reste visible tout le 30. Avant, il disparaissait à l'heure enregistrée.
- **Tranché par** : humain (dates, offres et partenaires) ; session (bornes comprises)
