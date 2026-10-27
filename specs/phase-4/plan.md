# Plan — Phase 4

## Modèle de données

| Table | Colonnes ajoutées | Remarques |
|---|---|---|
| `guests` | `entry_token` | 48 caractères aléatoires, unique, distinct du jeton d'invitation |
| `guests` | `checked_in_at` | horodatage du premier pointage |
| `guests` | `checked_in_by` | utilisateur ayant pointé, `nullOnDelete` |

Les foyers existants reçoivent un jeton lors de la migration. Les nouveaux en reçoivent un à la création (modèle) et à l'import CSV (insertion en masse).

## QR code

- Dépendance serveur `endroid/qr-code`, writer SVG.
- Service `EntryQrCode` : `svg(Guest)`, `dataUri(Guest)`.
- URL encodée : `route('check-in.show', $guest->entry_token)`.
- Le SVG est servi en téléchargement par une route protégée pour l'organisateur, et embarqué en data URI dans l'invitation pour le foyer.

## Routes

| Méthode | URL | Action |
|---|---|---|
| GET | `/events/{event}/scan` | page mobile de scan |
| POST | `/events/{event}/scan` | vérification ou pointage JSON d'un code |
| DELETE | `/events/{event}/guests/{guest}/check-in` | annulation JSON |
| GET | `/events/{event}/guests/{guest}/entry-qr.svg` | téléchargement du QR |
| GET | `/check-in/{entryToken}` | page directe ouverte par le QR |
| POST | `/check-in/{entryToken}` | pointage depuis la page directe |
| POST | `/check-in/{entryToken}/undo` | annulation depuis la page directe |

Toutes les routes de pointage sont derrière `auth`. L'accès est contrôlé par `EventPolicy::manageGuests` (propriétaire et co-organisateurs). Le pointage JSON vérifie en plus que le foyer appartient à l'événement de l'URL.

## Services et contrôleurs

- `CheckInService` :
  - résout le foyer par `entry_token` et événement attendu ;
  - `inspect` : renvoie l'état sans écrire (`checked_in`, `already_checked_in`, `requires_confirmation`, `invalid`) ;
  - `checkIn` : transaction, premier pointage seulement, confirmation obligatoire si le foyer n'est pas présent ;
  - `undo` : efface le pointage ;
  - `counters` : foyers pointés, total, couverts pointés.
- `CheckInController` : page de scan, POST JSON, page directe, POST direct, annulation.
- `GuestController` ou contrôleur dédié : téléchargement du QR.

## Vues et JavaScript

- `check-in/scan.blade.php` : compteurs, zone caméra, saisie manuelle, carte de résultat, actions confirmer/annuler/scanner suivant.
- `check-in/show.blade.php` : page directe sans JavaScript, accent de l'événement, détail du foyer, statut, formulaire de pointage ou d'annulation.
- `resources/js/scanner.js` : démarrage de la caméra avec `html5-qrcode`, extraction du jeton depuis l'URL, appels JSON, pause/reprise, messages d'erreur et repli manuel.
- Invitation : bloc QR avec data URI, consigne « Présentez ce code à l'entrée » et téléchargement.
- Page événement : accès au scan, compteur de pointage, téléchargement du QR par foyer.

## Sécurité et cas limites

- Jeton d'entrée non devinable, séparé du jeton d'invitation : un scan ne permet pas de modifier le RSVP.
- Toutes les pages de pointage en `noindex, nofollow` et `no-referrer` ; le jeton ne fuit pas vers les polices externes.
- Redirection vers la connexion pour un visiteur non connecté ; 403 pour un tiers.
- Jeton inconnu ou d'un autre événement : aucun écrit, message invalide.
- Pointage idempotent : le premier horodatage est conservé ; l'annulation est explicite.
- Confirmation obligatoire pour `pending` et `declined`.
- Caméra indisponible ou refusée : message et saisie manuelle toujours disponible.
- Pas de dépendance à un service QR externe : le SVG est produit localement.

## Ordre d'implémentation

1. Spec et migration, jeton d'entrée, modèle, importeur.
2. Service QR et routes de téléchargement.
3. Service de pointage, contrôleur, routes.
4. Page de scan, page directe et JavaScript caméra.
5. Intégration invitation et espace organisateur.
6. Tests Pest, Pint, build, parcours navigateur mobile, documentation.
