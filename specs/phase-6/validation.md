# Validation — Phase 6

## Tests automatisés (Pest)

### Envoi groupé
- Une relance sélectionnée crée une ligne `reminders` et pousse un job sans délai.
- Un lot de trois foyers crée trois relances et trois jobs ; « Tout relancer » ne retient que les foyers sans réponse, les autres sont comptés comme ignorés.
- Le job envoie l'e-mail au bon destinataire, avec le lien d'invitation, le bouton de réponse et le lien de désinscription signé, puis renseigne `sent_at`.
- Un foyer ayant répondu, sans e-mail, désinscrit ou appartenant à un autre événement est ignoré ; une sélection sans foyer éligible renvoie une erreur de validation.

### Limites
- Une relance en file (`sent_at` nul) compte dans la limite.
- Une seconde relance moins de 24 heures après la première est refusée ; après 24 heures elle est acceptée.
- La troisième relance est refusée même espacée de 24 heures.

### Désinscription
- Le lien non signé renvoie 403 ; le lien signé affiche la confirmation nominative, puis le POST enregistre le refus.
- La désinscription est idempotente (la date initiale est conservée).
- Après désinscription, l'invitation personnelle reste accessible et le foyer disparaît des relances.

### WhatsApp
- Le numéro local `07 07 07 07 07` devient `2250707070707`, un numéro déjà international est conservé.
- Le lien `wa.me` contient le numéro normalisé et le message encodé avec le lien d'invitation.
- Un foyer sans téléphone n'a pas de lien WhatsApp.

### Pages et accès
- Propriétaire et co-organisateur accèdent aux relances ; un tiers reçoit 403 ; un visiteur non connecté est redirigé vers la connexion.
- La page liste les foyers sans réponse avec le compteur « 1 / 2 relances utilisées », le lien WhatsApp, les deux boutons d'envoi et l'en-tête `noindex, nofollow`.
- Un foyer désinscrit affiche le badge et le motif, sans date de prochaine relance.
- L'état vide s'affiche quand tous les foyers ont répondu.
- Le détail de l'événement propose le lien « Relancer les foyers ».

## Parcours navigateur

- Connexion et ouverture des relances depuis le détail de l'événement de démonstration.
- Foyer « Famille Bamba » listé, compteur « 1 foyer éligible », page `noindex`, lien WhatsApp normalisé vers `2250707070707` avec le jeton d'invitation dans le message.
- Sélection du foyer puis « Relancer la sélection » : confirmation « 1 relance mise en file. », compteur « 1 / 2 relances utilisées », case verrouillée pendant 24 h.
- Worker `queue:work --stop-when-empty` exécuté : e-mail « Petit rappel » reçu dans Mailpit, destinataire `bamba.p6@example.com`, lien d'invitation, lien de désinscription signé et bouton de réponse présents.
- Affichage mobile 390 px : aucun débordement horizontal.
- Désinscription depuis le lien de l'e-mail : page nominative, confirmation, badge « Désinscrit » et motif côté organisateur, envoi désactivé.
- L'invitation personnelle reste accessible après désinscription.
- Aucune erreur JavaScript.

## Vérifications manuelles

```bash
docker compose exec app php artisan migrate
docker compose exec app ./vendor/bin/pest
docker compose exec app ./vendor/bin/pint --test
npm run build
curl -s -X DELETE http://localhost:8025/api/v1/messages   # vider Mailpit avant le parcours
```

- Relancer un lot de plusieurs dizaines de foyers et vérifier la répartition des envois dans Mailpit.
- Ouvrir le lien WhatsApp pré-rempli sur un mobile avec WhatsApp installé.
- Désinscrire un foyer, attendre 24 h, vérifier qu'il reste exclu et que son invitation fonctionne encore.
- Vérifier qu'un foyer non désinscrit reçoit bien la seconde relance après 24 h, puis plus aucune.

## Résultats du 27 octobre 2026

### Automatisé

- Pest : **108 tests, 432 assertions**, tous verts (SQLite `:memory:`), dont 18 tests dédiés aux relances.
- Pint : **76 fichiers** conformes.
- Vite : build réussi (`app.css` 47,31 kB, `app.js` 53,88 kB, `scanner.js` 339,19 kB, `leaflet.js` 149,51 kB).
- Migrations : `reminders` et `guests.reminders_opted_out_at` appliquées sur MySQL 8.4.
- Playwright (Chromium headless, 1280 × 900 puis 390 × 844), événement de démonstration « Mariage QR 2026 », foyer « Famille Bamba » :
  - Page relances avec compteurs, sélection et confirmation « 1 relance mise en file. ».
  - E-mail réellement envoyé par le worker vers Mailpit, avec invitation et désinscription signée.
  - Désinscription : badge et motif affichés, envoi désactivé, invitation toujours accessible.
  - Mobile 390 px : débordement horizontal 0 px.
  - Captures : `21-relances.png`, `22-relances-mobile.png`, `23-relance-email.png`, `24-desinscription.png`, `25-relances-desinscrit.png`.

### Décision d'envoi

Le plan initial poussait chaque job avec un délai de 2 secondes. La cible de déploiement étant un mutualisé avec cron `queue:work --stop-when-empty`, un délai par job produit l'effet inverse : le worker s'arrête faute de job disponible, puis le passage suivant purge en vrac tous les jobs devenus disponibles. Les jobs sont donc poussés sans délai ; le worker unique sérialise les envois, et la latence SMTP suffit à limiter le débit. Le comportement est identique en développement (`queue:work` continu) et en production (cron).

### Correction d'affichage

- **Foyer désinscrit** : la page affichait « Prochaine relance possible le … » sur un foyer désinscrit, car la condition testait `nextReminderAt()` sans vérifier le refus. La date est désormais masquée pour un foyer désinscrit ; un test le couvre.

### Reste manuel

- Relancer un lot réel de plusieurs dizaines de foyers et surveiller la boîte d'envoi du fournisseur (quotas, réputation).
- Ouvrir le lien WhatsApp sur un téléphone avec l'application installée, y compris pour un numéro hors Côte d'Ivoire.
- Vérifier le rendu de l'e-mail de relance dans Gmail et Outlook (mode sombre, images bloquées).
