# Plan — Phase 8

## Données

### `photos`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `event_id` | FK events | cascade |
| `uploaded_by` | FK users nullable | `nullOnDelete` |
| `original_name` | string(255) | nom du fichier fourni |
| `path` | string(255) | original, disque `local` |
| `thumb_path` | string(255) nullable | vignette, renseignée par la tâche |
| `web_path` | string(255) nullable | version web, renseignée par la tâche |
| `mime` | string(50) | `image/jpeg` ou `image/png` |
| `size` | unsigned int | octets |
| `width` / `height` | unsigned int nullable | dimensions après orientation |
| `caption` | string(120) nullable | légende facultative |
| `status` | string(20) | `pending` par défaut |
| `moderated_by` | FK users nullable | `nullOnDelete` |
| `moderated_at` | timestamp nullable | |
| `created_at` / `updated_at` | timestamps | |

Index `(event_id, status, created_at)` pour la file et la galerie.

## Modèles

- Enum `PhotoStatus` : `Pending`, `Approved`, `Hidden` avec `label()` et `tone()`.
- `Photo` : relations `event()`, `uploader()` (`uploaded_by`), `moderator()` (`moderated_by`) ; cast du statut ; scope `approved()` ; accesseurs `originalPath()`, `derivativePath('thumb'|'web')`, `hasThumb()`, `hasWeb()`.
- `Event` : `photos()` hasMany. `User` : `uploadedPhotos()` hasMany (facultatif, pour la traçabilité).

## Tâche de fond

`GeneratePhotoDerivatives` (`ShouldQueue`, 3 tentatives) :

1. Lit l'original sur le disque `local`.
2. Applique l'orientation EXIF.
3. Produit la version web (1 600 px max) puis la vignette (480 px max) par `scaleDown`.
4. Ré-encode en JPEG (qualité 82) ou PNG selon le MIME d'origine, sans métadonnées.
5. Écrit `{uuid}-web.{ext}` et `{uuid}-thumb.{ext}`, renseigne `thumb_path`, `web_path`, `width`, `height`.
6. Si l'original a disparu ou est illisible, la tâche échoue sans laisser de chemins partiels.

## Routes

| Méthode | URI | Nom | Accès |
|---|---|---|---|
| GET | `i/{token}/galerie` | `invitations.gallery` | publique par jeton |
| GET | `galerie/photos/{photo}/{variant}` | `gallery.photo` | `signed`, photo publiée |
| GET | `events/{event}/galerie` | `gallery.index` | auth + `manageGallery` |
| POST | `events/{event}/galerie` | `gallery.store` | auth + `manageGallery` |
| GET | `events/{event}/galerie/{photo}/apercu/{variant?}` | `gallery.preview` | auth + `manageGallery` |
| PUT | `events/{event}/galerie/{photo}` | `gallery.caption` | auth + `manageGallery` |
| POST | `events/{event}/galerie/{photo}/approuver` | `gallery.approve` | auth + `manageGallery` |
| POST | `events/{event}/galerie/{photo}/masquer` | `gallery.hide` | auth + `manageGallery` |
| POST | `events/{event}/galerie/{photo}/rejeter` | `gallery.reject` | auth + `manageGallery` |

`manageGallery` = propriétaire ou co-organisateur (`hasMember`). Toute action vérifie `$photo->event_id === $event->id`, sinon 404.

## Envoi

1. Autorisation puis validation : `photos` tableau de 1 à 10 éléments ; chaque élément `file`, `image`, `mimes:jpg,jpeg,png`, `max:10240`.
2. Pour chaque fichier : UUID, extension déduite du MIME, écriture de l'original, dimensions via `getimagesize`.
3. Création de la photo à l'état `pending` (nom d'origine via `getClientOriginalName()`).
4. Dispatch de `GeneratePhotoDerivatives` pour chaque photo.
5. Retour sur la galerie : « N photo(s) ajoutée(s). Elles seront visibles après validation. »

Un envoi partiellement invalide est refusé en entier (validation Laravel), pour éviter les lots à moitié traités.

## Diffusion

- `gallery.photo` vérifie la signature, le statut `approved`, le variant demandé (`thumb` ou `web`), l'existence du dérivé, puis répond depuis le disque `local` avec `Cache-Control: private, max-age=1800`.
- Les URLs sont générées dans les vues avec `URL::temporarySignedRoute(..., now()->addMinutes(30))`. Elles sont recalculées à chaque chargement de page, donc toujours valides pour l'affichage courant.
- `gallery.preview` (authentifiée) sert `thumb`, `web` ou `original` à un membre de l'événement, avec `Cache-Control: no-store` : elle permet de modérer avant la fin du traitement.
- Les réponses photo ne sont jamais mises en cache publiquement ; le `Referer` est neutralisé par le layout invitation (`no-referrer`).

## Modération

- `index` charge les photos de l'événement avec l'uploadeur et le modérateur, et les compteurs par état.
- `approve` / `hide` : mise à jour de `status`, `moderated_by`, `moderated_at`, puis retour sur la galerie.
- `reject` : suppression des fichiers (`path`, `thumb_path`, `web_path`) puis `delete()` de la ligne. Confirmation côté interface ; l'action est irréversible.
- `caption` : validation `caption` nullable, 120 caractères, espaces retirés ; une légende vide devient `null`.

## Pages

### `invitations/show.blade.php`

- Bouton « Galerie photo (N) » à côté de « Programme et carte », N = nombre de photos publiées ; masqué si aucune photo publiée.
- L'invitation ne contient pas de grille : la galerie a sa propre page, pour ne pas alourdir l'invitation.

### `gallery/show.blade.php` (invité)

- Layout invitation, fil d'Ariane vers l'invitation.
- Titre « Galerie photo » et compte des photos publiées.
- Grille responsive (2 colonnes mobile, 3 tablette) de vignettes signées, lien vers la version web dans un nouvel onglet.
- Légende sous chaque photo si présente.
- État vide : « Aucune photo publiée pour l'instant. Revenez après l'événement. »
- Pagination 12 par page (`galerie_page`).

### `gallery/index.blade.php` (organisation)

- Fil d'Ariane, titre, retour à l'événement.
- Formulaire d'envoi : `multiple`, `accept="image/jpeg,image/png"`, aide « 10 photos maximum, 10 Mo par photo, JPEG ou PNG ».
- Trois compteurs : en attente, publiées, masquées.
- Cartes photo : aperçu via la route authentifiée (repli sur l'original tant que les dérivés ne sont pas prêts), nom d'origine, dépôt, dimensions, statut, formulaire de légende, actions Publier / Masquer / Supprimer.
- Section « En attente » en premier, puis « Publiées », puis « Masquées ».
- État vide global et `noindex`.

### Intégrations organisateur

- `events/show.blade.php` : bouton « Galerie photo » avec le nombre de photos en attente.
- `dashboard.blade.php` : pastille « X photo(s) à modérer » par événement.

## Cas limites

- Dérivés pas encore générés : la carte affiche « Traitement en cours » et l'aperçu utilise l'original via la route authentifiée.
- Original absent (suppression manuelle) : la carte affiche « Fichier introuvable » ; la modération reste possible.
- URL signée expirée : 403 ; photo masquée entre-temps : 404.
- La légende est échappée à l'affichage ; aucune balise HTML n'est interprétée.
- Deux modérations simultanées : la dernière action gagne, comme pour le livre d'or.

## Tests

- Envoi : une et plusieurs photos, original privé, nom d'origine, état initial, dispatch des tâches, redirection et confirmation.
- Validation : fichier non image, GIF/WebP, plus de 10 Mo, tableau vide, plus de 10 fichiers.
- Tâche : génération de la vignette et de la version web, dimensions bornées, chemins enregistrés, image PNG transparente conservée en PNG.
- Diffusion : URL signée valide, signature absente/altérée, photo en attente ou masquée renvoie 404, variant inconnu 404, prévisualisation réservée aux membres.
- Galerie invitée : uniquement les photos publiées, ordre, légende, pagination, état vide, jeton inconnu 404.
- Modération : publier, masquer, republier, légende modifiable, rejet supprime fichiers et ligne.
- Autorisations : visiteur redirigé, tiers 403, photo d'un autre événement 404.
- Compteurs : événement et tableau de bord.
- Échappement : légende `<script>` rendue en texte.

## Ordre d'implémentation

1. Migration, enum, modèle, factory, relations.
2. Tâche de dérivés.
3. Contrôleur, policy, routes.
4. Vues (galerie invitée, gestion, intégrations).
5. Tests Pest, Pint, build.
6. Parcours navigateur avec worker de file, documentation.
