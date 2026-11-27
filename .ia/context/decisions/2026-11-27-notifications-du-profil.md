# `2026-11-27` — Notifications : un lien du profil, des choix par compte

- **Type** : décision
- **Question posée** : « Menu profile ajouter un lien notification : Account activity (email, sms and push choice
  default select all), reminders (email, push, and sms choice default select all), offers (yes or no default yes),
  news (yes or no default yes) ».
- **Ce qui est retenu** :
  - lien « Notifications » dans le menu principal, après « Confidentialité », icône cloche (`bell` / `notifications`) ;
  - formulaire `auth.profile.notifications` (`PATCH /api/v1/auth/me/notifications`) : « Activité du compte » et
    « Rappels » en cases à cocher (e-mail, SMS, push ; toutes cochées par défaut), « Offres » et « Nouveautés » en
    interrupteurs (oui par défaut) ;
  - stockage : `users.notification_settings` (JSON, vide = les défauts) ; clés et défauts dans un seul domicile,
    `Modules\Auth\Support\NotificationSettings` ; `User::notificationSettings()` les complète par les défauts ;
  - câblage : `User::notificationChannels()`, le point central de toutes les notifications envoyées (réservations,
    messages, partenaires, invitation à laisser un avis), ne garde plus que l'e-mail et le SMS cochés dans « Activité
    du compte ». Les codes de vérification ne passent pas par là : ils partent toujours.
- **Décidé seul (mode nuit)** :
  - l'option d'un canal de compte désactivé (`AuthChannels`) n'est pas proposée ; le push l'est toujours ;
  - « Rappels », « Offres », « Nouveautés » et le push sont **enregistrés seulement** : aucune notification ne les
    lit encore (pas de push, pas d'envoi d'offres ni de nouveautés ; l'invitation à laisser un avis, seul rappel
    existant, vit dans le module Offer et suit pour l'instant « Activité du compte ») ;
  - une clé absente de l'envoi ne change pas le choix enregistré.
- **Portée** : module Auth (`ProfileMenuKey`, `ProfileMenuService`, `NotificationSettingsForm`, contrôleur, service,
  `User`), aucune version de l'app.
- **Tranché par** : humain (demande), session de nuit pour le détail
