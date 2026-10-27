# Plan — Phase 7

## Données

### `guestbook_entries`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `event_id` | FK events | cascade |
| `guest_id` | FK guests | cascade, foyer auteur |
| `author_name` | string(80) | nom affiché publiquement |
| `message` | text | 1 000 caractères max à la validation |
| `status` | string(20) | `pending` par défaut |
| `moderated_by` | FK users nullable | `nullOnDelete` |
| `moderated_at` | timestamp nullable | |
| `created_at` / `updated_at` | timestamps | |

Index `(event_id, status, created_at)` pour la file de modération.

## Modèles

- Enum `GuestbookStatus` : `Pending`, `Approved`, `Hidden`, `Rejected` avec `label()` et `tone()`.
- `GuestbookEntry` : relations `event()`, `guest()`, `moderator()` ; cast du statut ; scope `approved()`.
- `Event` : `guestbookEntries()` hasMany.

## Routes

| Méthode | URI | Nom | Accès |
|---|---|---|---|
| POST | `i/{token}/livre-dor` | `guestbook.store` | publique, `throttle:5,1` |
| GET | `events/{event}/livre-dor` | `guestbook.index` | auth + `moderateGuestbook` |
| POST | `events/{event}/livre-dor/{entry}/approuver` | `guestbook.approve` | auth + `moderateGuestbook` |
| POST | `events/{event}/livre-dor/{entry}/masquer` | `guestbook.hide` | auth + `moderateGuestbook` |
| POST | `events/{event}/livre-dor/{entry}/rejeter` | `guestbook.reject` | auth + `moderateGuestbook` |

`moderateGuestbook` = propriétaire ou co-organisateur (`hasMember`). Un message d'un autre événement renvoie 404.

## Dépôt

1. Résolution du foyer par `invitation_token`, 404 si inconnu.
2. Si le champ leurre `website_url` est rempli : redirection avec la confirmation habituelle, sans écriture.
3. Validation : `author_name` requis (80), `message` requis (1 000).
4. Création à l'état `pending`, rattachée à l'événement et au foyer.
5. Redirection vers `#livre-dor` avec « Merci ! Votre message sera publié après validation par les organisateurs. »

## Modération

1. `index` charge les messages de l'événement avec leur foyer et le modérateur, groupés par état, et les compteurs.
2. `approve`, `hide`, `reject` passent par une méthode commune : autorisation, vérification de l'événement, mise à jour de `status`, `moderated_by` et `moderated_at`.
3. Retour sur la file avec « Message publié. », « Message masqué. » ou « Message rejeté. ».

## Pages

### Section sur `invitations/show.blade.php`

- Section `#livre-dor` après le RSVP : messages publiés (message, nom, date), du plus récent au plus ancien, paginés par 10.
- État vide : « Aucun message publié pour l'instant. Soyez le premier à signer. »
- Formulaire : nom (prérempli avec le nom du foyer), message, champ leurre caché, bouton « Signer le livre d'or ».
- Note : le message paraît après validation.

### `guestbook/index.blade.php`

- Fil d'Ariane, titre, retour à l'événement.
- Quatre compteurs : en attente, publiés, masqués, rejetés.
- Sections par état, chacune listant les messages avec auteur, foyer, date et métadonnées de modération.
- Actions : Publier / Rejeter (en attente), Masquer / Rejeter (publié), Publier / Rejeter (masqué), Publier (rejeté).
- État vide global et `noindex`.

### Intégrations organisateur

- `events/show.blade.php` : bouton « Livre d'or » avec le nombre de messages en attente.
- `dashboard.blade.php` : pastille « X messages à modérer » par événement, lien direct vers la file.

## Cas limites

- Deux modérations simultanées : la dernière action gagne, sans verrou (pas d'enjeu de compteur).
- Un foyer peut signer plusieurs messages ; la limitation de débit borne le volume.
- Un message soumis avec du HTML est stocké tel quel puis échappé à l'affichage.
- La pagination du livre d'or utilise `livre_dor_page` pour ne pas entrer en conflit avec d'autres paramètres.

## Tests

- Dépôt : enregistrement en attente, espaces retirés, redirection avec ancre.
- Validation : nom et message requis, longueurs maximales.
- Anti-spam : honeypot ignoré silencieusement, 6e dépôt en une minute refusé (429).
- Affichage : messages approuvés visibles et ordonnés, les autres états masqués, formulaire présent, échappement HTML.
- Modération : approuver, masquer, rejeter, ré-approuver ; auteur et date enregistrés ; message d'un autre événement 404.
- Autorisations : visiteur redirigé, tiers 403, propriétaire et co-organisateur autorisés.
- Compteurs : événement et tableau de bord.
- Page de modération : sections, actions, état vide, `noindex`.

## Ordre d'implémentation

1. Migration, enum, modèle, factory, relation.
2. Contrôleur, policy, routes.
3. Vues (invitation, modération, intégrations).
4. Tests Pest, Pint, build.
5. Parcours navigateur et documentation.
