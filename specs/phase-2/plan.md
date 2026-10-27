# Plan — Phase 2

## Modèle de données

| Table | Colonnes | Remarques |
|---|---|---|
| `events` | `user_id`, `name`, `type`, `starts_at`, `venue_name`, `venue_address`, `rsvp_deadline` | `user_id` = propriétaire ; type en enum |
| `event_roles` | `event_id`, `user_id`, `role` | co-organisateurs ; unique `(event_id, user_id)` |
| `guests` | `event_id`, `name`, `email`, `phone`, `allowed_seats` | un enregistrement = un foyer, 1 à 20 places |

## Routes

| Méthode | URL | Action |
|---|---|---|
| GET | `/dashboard` | liste des événements visibles |
| GET | `/events/create` | formulaire de création |
| POST | `/events` | création |
| GET | `/events/{event}` | détail, foyers, co-organisateurs |
| GET | `/events/{event}/edit` | formulaire d'édition (propriétaire) |
| PUT | `/events/{event}` | mise à jour (propriétaire) |
| POST | `/events/{event}/guests` | ajout d'un foyer |
| DELETE | `/events/{event}/guests/{guest}` | suppression d'un foyer |
| POST | `/events/{event}/guests/import` | import CSV |
| POST | `/events/{event}/co-organizers` | ajout (propriétaire) |
| DELETE | `/events/{event}/co-organizers/{user}` | retrait (propriétaire) |

## Composants

- Enum `EventType` (libellé + classe d'accent).
- Modèles `Event`, `EventRole`, `Guest` ; `EventPolicy` pour les autorisations.
- `GuestImporter` : lecture CSV native, détection du séparateur, validation ligne à ligne, insertion transactionnelle, rapport `imported` / `errors`.
- Contrôleurs `EventController`, `GuestController`, `GuestImportController`, `CoOrganizerController` ; autorisations via `Gate::authorize`.
- Vues Blade : dashboard, création/édition (formulaire partagé), détail avec formulaires d'ajout, import et co-organisateurs.

## Sécurité et cas limites

- Vérifier l'appartenance du foyer à l'événement avant suppression (404 sinon).
- Valider le type via `Rule::enum`, les places entre 1 et 20, le fichier (CSV/TXT, 2 Mo max).
- CSV : BOM UTF-8, lignes vides, colonnes manquantes, en-tête facultatif, séparateur virgule ou point-virgule.
- Un co-organisateur ne peut pas modifier l'événement ni gérer les co-organisateurs.
- Le propriétaire ne peut pas s'ajouter lui-même comme co-organisateur.

## Ordre d'implémentation

1. Migrations, enum, modèles, factories.
2. Policy et importeur CSV.
3. Contrôleurs, routes et vues.
4. Tests Pest, Pint, build des assets, vérification HTTP.
