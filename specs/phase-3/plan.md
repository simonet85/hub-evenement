# Plan — Phase 3

## Modèle de données

| Table | Colonnes ajoutées | Remarques |
|---|---|---|
| `guests` | `invitation_token` | 48 caractères aléatoires, unique, régénéré pour révoquer |
| `guests` | `rsvp_status` | `pending`, `attending`, `declined` |
| `guests` | `rsvp_seats`, `attendee_names` | places confirmées et prénoms (texte multilingue) |
| `guests` | `dietary_notes`, `rsvp_message` | régime et message libre |
| `guests` | `rsvp_responded_at`, `rsvp_is_late` | horodatage et marqueur hors délai |

Les foyers existants reçoivent un jeton lors de la migration. Les nouveaux en reçoivent un à la création (modèle) et à l'import CSV (insertion en masse).

Enum `RsvpStatus` : libellé et ton visuel (`warning`, `success`, `danger`).

## Routes

| Méthode | URL | Action |
|---|---|---|
| GET | `/i/{token}` | invitation publique du foyer |
| POST | `/i/{token}/rsvp` | enregistrement de la réponse (throttle 20/min) |
| POST | `/events/{event}/guests/{guest}/invitation` | régénération du lien (membre de l'événement) |

## Composants

- `InvitationController` : résolution par jeton, affichage, validation et enregistrement du RSVP.
- `Guest` : génération du jeton, `hasResponded`, `isPastDeadline`, `canRespond`, URL d'invitation.
- `GuestController::regenerateInvitation` : nouveau jeton, contrôle d'appartenance du foyer.
- `RsvpConfirmation` (mailable en file) : reçu au foyer si adresse connue.
- Layout `layouts.invitation` : page publique centrée, accent du type d'événement, non indexable.
- Vue `invitations.show` : détails, formulaire ou récapitulatif verrouillé, bandeau hors délai.
- Composant `rsvp-status` : pastille avec icône et mot, plus badge « hors délai ».
- Page événement : statistiques réponses/couverts, statut par foyer, détail de la réponse, lien à copier et régénération.

## Sécurité et cas limites

- Jeton non devinable, jamais exposé dans les listes publiques ; lien révocable.
- 404 sur jeton inconnu ou révoqué ; 403 si la réponse est verrouillée.
- 404 si le foyer n'appartient pas à l'événement lors de la régénération.
- Régénération réservée aux propriétaires et co-organisateurs.
- Places confirmées plafonnées par `allowed_seats` ; prénoms plafonnés par les places.
- Page publique en `noindex, nofollow`, limitation de débit sur le POST.
- Aucun e-mail envoyé si le foyer n'a pas d'adresse.

## Ordre d'implémentation

1. Migration, enum, modèle, usine et importeur.
2. Contrôleur public, routes, régénération.
3. Layout et vue d'invitation, formulaire et états.
4. Reçu e-mail en file.
5. Intégration à la page événement (statistiques, statuts, lien).
6. Tests Pest, Pint, build, parcours navigateur mobile.
