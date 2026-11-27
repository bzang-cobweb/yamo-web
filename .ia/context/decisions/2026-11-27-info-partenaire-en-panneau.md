# `2026-11-27` — Notification de partenaire : « Info » ouvre la fiche du partenaire

Remplace en partie : 2026-11-27-detail-sans-zone-fixe.md (le contenu de « Info » pour un partenaire)

- **Type** : décision
- **Question posée** : « le menu info de partner fait appel au détail d'un partenaire et affiche dans un panneau avec sa hauteur maximale (utiliser le même contenu que celui de la page détail d'un partenaire, en faire donc un composant commun) ».
- **Ce qui est retenu** : sur une notification de partenaire, `AddPartnerToNotificationThread` remplace l'entrée `info` du menu par `{key: 'partner', label: 'Info', link: null, record: '<id>'}` ; toute entrée du menu porte désormais `record` (nul s'il ne désigne rien). L'app ouvre l'entrée `partner` dans un panneau à taille maximale, titré de son libellé, qui lit le partenaire (`GET /partners/{id}`) et montre `PartnerDetails` : le même composant que la page d'un partenaire (logo, nom, slogan, vues, contact, description, pastille ; roue au chargement, « plus disponible » s'il a disparu).
- **Motif** : une seule fiche de partenaire, écrite une fois ; « Info » montre ce qu'est le partenaire plutôt que de répéter l'en-tête de la notification.
- **Portée** : web : `NotificationResource` (`record`), écouteur du module Partner. Mobile : `PartnerDetails` (nouveau, `features/partner/components`), `PartnerDetailScreen` réduit à la barre, la fiche et le bouton « Visiter le site » ; `NotificationDetailScreen`. Le panneau ne compte pas de clic et n'a pas le bouton du site : ils restent propres à la page.
- **Tranché par** : humain
