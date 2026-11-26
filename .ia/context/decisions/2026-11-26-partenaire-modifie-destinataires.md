# `2026-11-26` — « Le partenaire … a été modifié. » pour les destinataires

- **Type** : décision
- **Question posée** : « pour les destinataires, changer le message par : Le partenaire xxxxx a été modifié. »
- **Ce qui est retenu** : le texte d'une modification depuis l'app, pour ceux qui ne l'ont pas faite (le compte du back-office, les autres comptes du partenaire), devient « Le partenaire « :name » a été modifié. » (`partner::partner.notify.submitted.others.updated`), au lieu de « « :name » a été modifié par :actor. » ; en anglais « The partner “:name” was updated. », en allemand « Der Partner „:name“ wurde geändert. ». Qui l'a modifié est nommé au-dessus, sur la carte et dans le suivi.
- **Ne changent pas** : le texte de l'auteur (« Votre partenaire « … » a été modifié. »), celui d'un ajout, ceux de l'équipe, et les changements enregistrés (2026-11-26-partenaire-modifications).
- **Corollaire** : une notification déjà reçue garde son texte d'origine chez son destinataire ; les autres lecteurs lisent le nouveau.
- **Tranché par** : humain
