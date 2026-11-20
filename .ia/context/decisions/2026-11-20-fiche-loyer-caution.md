Remplacée en partie par : 2026-11-20-caution-meuble-toujours.md

# Fiche d'un logement : loyer mensuel, caution et avance dans les caractéristiques

- **Date** : 2026-11-20
- **Type** : décision
- **Statut** : active
- **Complète** : 2026-11-20-caution-affichee.md, 2026-11-20-loyer-sans-charges-avec-charges.md

## Décision

Section « Caractéristiques » de la fiche d'un logement :

- location non meublée : « Loyer mensuel » après les charges, « Loyer mensuel (sans charges) »
  quand les charges sont supérieures à 0 ; puis « Caution » (« 2 mois ») et « Avance de loyer »
  (« 3 mois ») ;
- charges, caution (mois ou montant) et avance : seulement si renseignées et non nulles ;
- meublé : pas de loyer mensuel ; sa caution en montant, si renseignée.

Mécanisme : `Domain::adjustFacts()` (inchangé par défaut), que `RentalDomain` implémente ; les
libellés du loyer sont ceux des lignes d'une demande (`offer::booking.lines.rent`, `rent_net`).

## Motif

Demande de l'humain du 2026-11-20 : « dans détail de l'offre afficher dans caractéristiques si
pas vides : loyer mensuel et appliquer la règle du sans charges ; caution à gauche xx mois à
droite ; avance loyer à gauche xx mois à droite ». Place du loyer après les charges (comme dans
une demande) et 0 traité comme vide : décidé seul.
