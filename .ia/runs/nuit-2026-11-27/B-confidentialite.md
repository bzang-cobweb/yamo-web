# Nuit du 2026-11-27 — Lot B, masquage, vérification, confidentialité (B2, B3, B4, B5, B7)

Contrat : `specs/2026-11-27-visiteur-profil-favoris.md`. Mode nuit, sans question à l'humain.

## Fait

- **B4 Masquage** : un domicile, `Modules\Auth\Support\Masks` (`email()`, `phone()`, `destination()`), qui remplace
  `VerificationCodeService::mask()`/`maskDestination()`. `GET /auth/me` sert `email_masked`, `phone_masked`,
  `email_verified`, `phone_verified` ; l'app (profil) affiche les masqués. Membres d'un partenaire : `email_masked`,
  `phone_masked` affichés, le lien `tel:`/`mailto:` garde la vraie valeur. Contact d'une demande (panneau « Info ») :
  valeur masquée + lien `tel:`/`mailto:`, ouvert par le système dans l'app (`SummaryLineRow`). Back-office inchangé.
- **B2/B3 Changement d'e-mail / de numéro** : la valeur actuelle est masquée en lecture seule ; le code part toujours à
  la nouvelle valeur. Valeur actuelle non vérifiée : aide « n'est pas encore vérifiée » et un lien servi
  (« Vérifier mon adresse » → `auth.profile.email.verify`, « Renvoyer le code » → `auth.profile.phone.verify`). Ces
  formulaires (valeur masquée, « Envoyer le code ») envoient un code à la valeur actuelle
  (`POST /auth/me/email/verify`, `/phone/verify`, purposes `verify_email`/`verify_phone`) ; sa validation pose
  `email_verified_at`/`phone_verified_at`.
- **Concept générique** : `Form::links()` / `FormLink` dans `packages/form`, servi par `FormResource` (`links`) ; l'app
  (`DynamicForm`) affiche les liens sous les champs et ouvre `/profile/[form]`.
- **B5** : `phone_verified_at` déjà posé par la connexion SMS ; la connexion par code e-mail pose de même
  `email_verified_at` ; le lien « Renvoyer le code » couvre le reste.
- **B7 « Confidentialité »** : visibilité du profil, avis, mes données, supprimer mon compte, avec icônes.
  - Visibilité (`auth.profile.privacy.visibility`, radio, défaut « Tout le monde ») appliquée par la garde `viewMember`
    de `GET /members/{id}` (`Modules\Offer\Support\MemberVisibility`), 404 sinon.
  - Avis (`auth.profile.privacy.reviews`, interrupteur, défaut non) : `author_location` sur la fiche et la liste des
    avis ; l'app l'affiche sous le prénom.
  - Mes données (`auth.profile.privacy.data`) : JSON des données du compte (module Auth) en pièce jointe à l'e-mail
    vérifié ; sinon 422 clair. 3 demandes par heure.
  - Supprimer mon compte : plus de mot de passe ; raison obligatoire (textarea) → code à l'e-mail vérifié, sinon au
    numéro vérifié → validation : raison gardée (`account_deletion_reasons`), compte supprimé comme avant (douce puis
    purge) ; l'app affiche le message et se déconnecte (vérifié par un test Jest ; elle ne remet plus le compte supprimé
    dans l'app avant).
  - Stockage : `users.privacy_settings` (JSON nullable, défauts fusionnés par `PrivacySettings`).
- Textes fr, en **et de** (le projet a une langue allemande, testée).

## Preuves

- `ddev exec php artisan test --compact` (suite complète) : **993 passed** (6378 assertions), aucun échec.
- `ddev exec php artisan migrate` : `2026_12_08_120000_add_privacy_settings_to_users` et
  `2026_12_08_121000_create_account_deletion_reasons_table` jouées (lot 89) ; `migrate:rollback --step=2 --pretend` :
  `drop table account_deletion_reasons`, `drop privacy_settings`.
- `ddev exec vendor/bin/pint --dirty` : PASS.
- Mobile : `npx tsc --noEmit` sans erreur ; `npx jest --watchAll=false --forceExit` : « Test Suites: 74 passed, 74
  total », « Tests: 470 passed ».
- Nouveaux tests web : `ContactVerificationApiTest` (masqués sur /me et formulaires, liens si non vérifié, code qui
  vérifie e-mail et numéro, autre compte refusé, connexion e-mail qui vérifie), `PrivacyApiTest` (visibilité, avis,
  mes données), `MemberPrivacyTest` (Offer : les quatre visibilités sur la page d'un membre, messages compris ; lieu
  sous un avis) ; complétés : `AccountDeletionApiTest` (raison + code, téléphone vérifié, code faux, autre compte, super
  administrateur), `ProfileMenuApiTest` (ordre du sous-menu), `PartnerApiTest`, `BookingThreadTest`,
  `VerificationCodesApiTest`, `AuthApiTest`, `OfferReviewApiTest`, `FormApiTest` (paquet).
- Nouveaux tests Jest : `FormLinks.test.tsx`, `SummaryLines.test.tsx` ; complétés : `ProfileScreen.test.tsx`
  (masqués), `ProfileFormScreen.test.tsx` (suppression : code puis déconnexion).

## Commits

Web (`nuit/2026-11-27`) :
- `1097118` E-mail et téléphone masqués à l'affichage… ; décision
- `fc97d96` Formulaires servis : Form::links()… ; décision
- `4782e7f` Profil : vérifier l'e-mail ou le numéro actuel… ; « Confidentialité »… ; décisions
- (ce rapport, commit suivant)

Mobile (`nuit/2026-11-27`) :
- `d0474c8` Formulaires : les liens servis sous un formulaire…
- `778d90a` E-mail et téléphone masqués : profil, membres d'un partenaire, contact d'une demande…
- `c817da5` Confidentialité : le lieu de l'auteur sous un avis, la suppression du compte par le code…

Décisions : `.ia/context/decisions/2026-11-27-email-telephone-masques.md`, `2026-11-27-formulaire-liens.md`,
`2026-11-27-verifier-email-telephone.md`, `2026-11-27-confidentialite.md`.

## Décidé seul

- Visibilité : **radio** plutôt que quatre interrupteurs (choix exclusif) ; « propriétaires » = au moins une offre, tout
  état ; « contacts » = une demande en commun (l'un a demandé une offre de l'autre, tout état) ou un fil de messages
  sur une offre de l'un d'eux ; profil non visible → **404** ; le membre voit toujours sa page.
- Avis : lieu **non** affiché par défaut ; ville = première adresse (comme la page publique), puis région et pays du
  compte ; dans la liste des avis, le lieu est placé avant la date (« Littoral, Cameroun · 18 nov. 2026 »).
- Mes données : envoi immédiat par e-mail (plutôt qu'une demande traitée par l'équipe) ; **seulement les données du
  module Auth** (compte, adresses, contact d'urgence, réglages) — pas les offres, demandes, avis, messages.
- Suppression : à défaut d'e-mail ou de numéro vérifié, le code part à l'e-mail puis au numéro tels quels ; ni l'un ni
  l'autre → 422. Raison dans une table à part (survit à la purge), non montrée au back-office. Avertissement réécrit :
  « reconnectez-vous pour le récupérer ».
- Vérification : la connexion par code e-mail pose `email_verified_at` (symétrie avec le SMS, non demandée) ; un seul
  service pour les deux formulaires de vérification.
- Masquage : valeur réelle gardée dans les liens d'action ; contact d'offre masqué comme celui de l'hôte ; contact d'un
  partenaire (`contact_email`/`contact_phone`) non masqué (pas un compte) ; recherche des membres d'un partenaire sur
  les vraies valeurs ; « Changer de numéro » montre un champ texte masqué au lieu du champ téléphone (pays + numéro).
- Commit web `4782e7f` réunit vérification et confidentialité : fichiers partagés (routes, fournisseur, purposes,
  contrôleur des codes, textes) impossibles à scinder sans `git add -p`.

## Non vérifié

- Rendu dans l'app (simulateur non touché) : liens sous le formulaire, formulaires de vérification, radio de visibilité,
  formulaire « Mes données » sans champ, lieu sous un avis, lignes de contact cliquables.
- Envoi réel de l'e-mail « Mes données » (pièce jointe) : vérifié par `Mail::fake` seulement.
- Contrat OpenAPI (`ddev composer openapi`) non régénéré.
- La visibilité ne s'applique qu'à la page publique d'un membre ; les autres endroits qui montrent un nom (avis,
  notifications, membres d'un partenaire) ne la lisent pas.
