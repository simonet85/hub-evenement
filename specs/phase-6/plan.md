# Plan — Phase 6

## Données

### `reminders`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `event_id` | FK events | cascade |
| `guest_id` | FK guests | cascade |
| `user_id` | FK users nullable | auteur, `nullOnDelete` |
| `sent_at` | timestamp nullable | renseigné par le job après envoi |
| `created_at` / `updated_at` | timestamps | mise en file = `created_at` |

Index `(guest_id, created_at)` pour les limites.

### `guests`

- `reminders_opted_out_at` nullable : date du refus des relances.

## Modèles

- `Reminder` : `event()`, `guest()`, `user()`.
- `Guest` :
  - `reminders()` hasMany.
  - `remindersOptedOut(): bool`.
  - `reminderCount(): int` et `lastReminderAt(): ?Carbon`.
  - `canReceiveReminder(): bool` : sans réponse, non désinscrit, e-mail présent, moins de 2 relances, dernière relance de plus de 24 h.
  - `whatsappUrl(): ?string` : normalisation du téléphone, message encodé.
- `Event` : `reminders()` hasMany ; `pendingGuests()` (sans réponse).

## Routes

| Méthode | URI | Nom | Accès |
|---|---|---|---|
| GET | `events/{event}/relances` | `reminders.index` | auth + `manageGuests` |
| POST | `events/{event}/relances` | `reminders.store` | auth + `manageGuests`, `throttle:10,1` |
| GET | `relances/desinscription/{guest}/{signature}` | `reminders.unsubscribe.show` | `signed` |
| POST | `relances/desinscription/{guest}/{signature}` | `reminders.unsubscribe` | `signed` |

Le lien signé est généré avec `URL::signedRoute('reminders.unsubscribe', ['guest' => $guest])` ; la désinscription ne dépend donc pas de la connexion.

## Envoi

1. Le contrôleur valide `guest_ids` (tableau, `exists`), limite la sélection aux foyers de l'événement (404/ignorés sinon).
2. Pour chaque foyer sélectionné, `canReceiveReminder()` filtre ; les autres sont comptés comme ignorés.
3. Une ligne `Reminder` est créée par envoi (mise en file), puis `SendReminderEmail` est poussé sans délai : le worker unique sérialise les envois, alors qu'un délai par job serait purgé en vrac par un cron `--stop-when-empty`.
4. Le job envoie `ReminderEmail` via `Mail::to($guest->email)->send(...)`, puis renseigne `sent_at`. En cas d'échec, la relance reste sans `sent_at` et le job est relancé par la file.
5. Retour : « X relances en file, Y foyers ignorés. »

## Pages

### `reminders/index.blade.php`

- En-tête : nom de l'événement, compteurs (sans réponse, relances envoyées, désinscrits).
- Liste des foyers sans réponse : case à cocher, nom, places, e-mail, téléphone, relances restantes, dernière relance, badge « Désinscrit », lien WhatsApp.
- Formulaire unique : « Relancer la sélection » (bouton principal) + lien « Tout sélectionner ».
- État vide : aucun foyer sans réponse.
- Note sur les limites (2 relances, 24 h) et sur l'absence d'envoi WhatsApp automatique.
- `noindex`.

### `reminders/unsubscribe.blade.php`

- Carte centrée, nom du foyer et de l'événement.
- `GET` : formulaire POST « Ne plus recevoir de relances » + lien « Revenir à l'invitation ».
- Après POST : confirmation et lien vers l'invitation. Déjà désinscrit : même écran, sans erreur.

### E-mail `mail/reminder.blade.php`

- Même gabarit que le reçu RSVP, accent par type d'événement.
- Rappel de la date limite si présente, bouton « Répondre à l'invitation ».
- Pied : lien de désinscription signé et mention « Vous recevez ce message car vous n'avez pas encore répondu ».

## Autorisations et cas limites

- `manageGuests` (propriétaire ou co-organisateur).
- Un foyer d'un autre événement est ignoré, jamais relancé.
- Une sélection vide ou sans foyer éligible renvoie une erreur de validation.
- Deux envois simultanés : la limite est vérifiée dans une transaction sur les relances du foyer.
- Le job recharge le foyer et vérifie encore l'éligibilité au moment de l'envoi (désinscription entre-temps) ; il s'arrête silencieusement si le foyer est devenu inéligible.
- Les e-mails en échec restent visibles avec une relance sans `sent_at` (état « en file »).

## Tests

- Accès : propriétaire, co-organisateur, tiers, invité non connecté.
- Éligibilité : sans réponse, absent/decliné, désinscrit, sans e-mail.
- Limites : 2 relances, 24 h, relance en file comptée.
- Envoi : lignes créées, job poussé sans délai, e-mail envoyé et `sent_at`.
- Désinscription : signature invalide, GET, POST, idempotence, accès à l'invitation conservé.
- WhatsApp : normalisation, message encodé, absent sans téléphone.
- Page : liste, compteurs, badges, état vide, `noindex`.

## Ordre d'implémentation

1. Migrations, modèles, méthodes de domaine.
2. Job, mailable, gabarit d'e-mail.
3. Contrôleur, routes, pages.
4. Tests Pest, Pint, build.
5. Parcours navigateur et documentation.
