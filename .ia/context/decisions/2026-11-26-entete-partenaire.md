Remplacée en partie par : 2026-11-26-entete-partenaire-sans-slogan.md (plus de slogan en sous-titre)

# `2026-11-26` — L'en-tête de la page d'une notification d'un partenaire

- **Type** : décision (complète 2026-11-26-booking-summary)
- **Question posée** : « pour notification thread partner, pour le heading afficher le titre du partenaire avec lien et son slogan comme sous-titre ».
- **Ce qui est retenu** :
  - la page d'une notification du fil d'un partenaire (`partner:group:{id}`) sert `heading` : le nom du partenaire (titre, en lien vers `/partners/{id}`) et son slogan (sous-titre, `null` sans slogan), dans la langue de la requête ;
  - le listener `AddPartnerToNotificationThread` du module Partner le remplit, sur `NotificationThreadSerializing`, comme les fils des demandes et des messages ; un partenaire introuvable ou sans nom : pas d'en-tête, le texte de la notification à la place.
- **Motif** : la même page pour tous les fils, l'objet du fil en tête.
- **Portée** : notifications des partenaires.
- **Tranché par** : humain
