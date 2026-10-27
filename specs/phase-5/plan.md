# Plan — Phase 5

## Modèle de données

| Table | Colonnes | Remarques |
|---|---|---|
| `events` | `dress_code` | texte court facultatif, 255 caractères |
| `events` | `latitude`, `longitude` | décimaux 10,7 facultatifs, renseignés ensemble |
| `event_program_steps` | `id`, `event_id`, `start_time`, `end_time`, `title`, `location`, `description`, `position` | `event_id` en cascade ; ordre manuel par `position` |

- `Event` : `programSteps()` ordonnées par `position`, `hasCoordinates()`, `googleMapsUrl()`, `wazeUrl()`.
- `ProgramStep` : `event()`, champs remplissables, position par défaut en fin de liste.
- `EventPolicy::manageProgram` : propriétaire ou co-organisateur.

## Routes

| Méthode | URL | Action |
|---|---|---|
| GET | `/i/{token}/programme` | page programme et carte du foyer |
| POST | `/events/{event}/programme` | ajout d'une étape |
| PUT | `/events/{event}/programme/{step}` | modification d'une étape |
| DELETE | `/events/{event}/programme/{step}` | suppression d'une étape |
| POST | `/events/{event}/programme/{step}/move` | monter ou descendre une étape |

Les routes d'écriture sont derrière `auth` et `EventPolicy::manageProgram`. Chaque étape est vérifiée comme appartenant à l'événement de l'URL. Les réglages (code vestimentaire, coordonnées) passent par `events.update`, réservé au propriétaire.

## Contrôleur et services

- `ProgramStepController` : `store`, `update`, `destroy`, `move`.
  - `store` : valide, place l'étape à `max(position) + 1`.
  - `move` : échange la position avec l'étape voisine dans une transaction.
  - Toutes les actions redirigent vers l'espace événement avec un message.
- `InvitationController::programme` : résout le foyer par jeton, charge l'événement et ses étapes.
- `EventController` : validation et enregistrement de `dress_code`, `latitude`, `longitude`.

## Vues et JavaScript

- `events/_form.blade.php` : code vestimentaire, sélecteur Leaflet avec repère déplaçable, champs numériques de coordonnées synchronisés, bouton « Retirer le repère ».
- `events/show.blade.php` : carte « Programme » avec formulaire d'ajout, liste ordonnée, édition en `<details>`, suppression, montée/descente, lien d'aperçu de la page programme et code vestimentaire.
- `invitations/programme.blade.php` : en-tête de l'événement, frise horaire, code vestimentaire, carte Leaflet, boutons Google Maps et Waze, retour à l'invitation, `noscript` avec adresse et liens.
- `invitations/show.blade.php` : code vestimentaire et bouton « Programme et carte ».
- `resources/js/map-picker.js` : initialisation Leaflet, clic pour placer le repère, synchronisation avec les champs.
- `resources/js/program-map.js` : carte en lecture seule, repère et popup, `scrollWheelZoom: false`.
- `resources/js/app.js` : import conditionnel des deux modules selon la présence des conteneurs.
- Dépendance `leaflet` (CSS importé dans les modules JS).

## Sécurité et cas limites

- Page programme en `noindex, nofollow` et `no-referrer` via le layout d'invitation.
- Aucune donnée d'un autre événement accessible par l'URL d'étape (404).
- Contenu des étapes échappé par Blade ; popup Leaflet construite avec `textContent`.
- Coordonnées validées et affichées avec 5 décimales maximum ; repère hors carte impossible.
- Sans JavaScript : formulaire de coordonnées numérique utilisable, page programme complète en HTML.
- Sans coordonnées : pas de carte ni de Waze, Google Maps déduit de l'adresse.
- Tuiles OSM bloquées ou indisponibles : la page conserve les liens d'itinéraire et l'adresse.

## Ordre d'implémentation

1. Spec, migration et modèles.
2. Contrôleur des étapes, policy et routes.
3. Formulaire d'édition (code vestimentaire, repère Leaflet).
4. Carte programme dans l'espace événement.
5. Page programme publique et lien depuis l'invitation.
6. Tests Pest, Pint, build, parcours navigateur mobile, documentation.
