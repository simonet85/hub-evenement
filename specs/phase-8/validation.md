# Validation — Phase 8

## Tests automatisés (Pest)

### Dépôt et stockage privé
- Deux photos (JPEG et PNG) sont stockées sur le disque `local` sous `events/{id}/photos`, avec un nom UUID et l'extension correspondante ; elles sont créées en attente, avec leurs dimensions, et une tâche de dérivés est dispatchée par photo.
- Le lot vide, plus de dix fichiers, un PDF, un GIF, un WebP et un fichier au-delà de 10 Mo sont refusés avec les messages français ; aucune photo n'est écrite et aucune tâche n'est dispatchée.
- Un fichier nommé `.jpg` dont le contenu n'est pas une image est refusé (« Chaque fichier doit être une image lisible. »).
- Une validation refusée redirige explicitement vers la galerie, même lorsqu'un aperçu image a été consulté juste avant (voir « Corrections pendant la validation »).

### Autorisations
- Propriétaire et co-organisateur consultent la page et déposent des photos ; un tiers reçoit 403 ; un visiteur non connecté est redirigé vers la connexion.
- L'aperçu privé répond 403 à un tiers et 404 pour une photo d'un autre événement.

### Dérivés
- La tâche produit une version web de 1600 px de large et une vignette de 480 px, conserve le PNG en PNG, applique l'orientation EXIF et renseigne dimensions et chemins.
- Une photo dont l'original est absent fait échouer la tâche (`RuntimeException`).

### Diffusion aux invités
- Seules les photos publiées apparaissent, de la plus récente à la plus ancienne, avec la légende échappée ; les photos en attente, masquées ou d'un autre événement restent invisibles.
- Pagination par douze, état vide, jeton inconnu en 404, page `noindex`.
- URLs signées : valides en 200 ; altérées ou non signées en 403 ; photo en attente ou masquée en 404 ; variante « original » refusée ; fichier manquant en 404.
- Le lien « Galerie photo (n) » n'apparaît sur l'invitation que lorsqu'au moins une photo est publiée.

### Modération
- Publier renseigne statut, modérateur et date ; masquer retire la photo de l'invitation et invalide son URL signée ; republier la rend de nouveau accessible.
- Rejeter supprime la ligne et les fichiers original, vignette et version web.
- Légende : espaces de début et de fin retirés, valeur vide ramenée à `null`, plus de 120 caractères refusés avec retour vers l'ancre de la photo.
- Une photo d'un autre événement renvoie 404 sur chaque action.
- Compteurs : « n photos à modérer » sur le détail de l'événement et le tableau de bord ; seules les photos en attente sont comptées.

## Parcours navigateur

- Galerie vide : limites et formats affichés, état vide, page non indexable.
- Envoi de deux photos : section « En attente de validation », noms de fichiers, deux cartes avec aperçu privé.
- Tâche de dérivés exécutée : « Vignette et version web prêtes. » sur les deux cartes.
- Compteurs « 2 photos à modérer » sur le détail de l'événement et le tableau de bord ; clic vers la galerie.
- Légende « Les mariés » enregistrée puis publication : section « Publiées ».
- Invitation du foyer « Famille Bamba » : bouton « Galerie photo (1) » ; la galerie invitée affiche la photo et sa légende, sans autre photo.
- URLs signées : vignette et version web accessibles, URL altérée refusée (403), URL non signée refusée (403), mobile 390 px sans débordement.
- Masquage : confirmation, URL signée en 404, galerie invitée vide, lien retiré de l'invitation ; republication : URL signée de nouveau valide.
- Rejet définitif de la photo en attente : confirmation, une seule photo restante.
- Fichier `.jpg` non image refusé avec le message « Chaque fichier doit être une image lisible. ».
- Aucune erreur JavaScript.

## Vérifications manuelles

```bash
docker compose exec app php artisan migrate
docker compose exec app ./vendor/bin/pest
docker compose exec app ./vendor/bin/pint --test
npm run build
```

- Envoyer une photo depuis un vrai téléphone et vérifier le refus des formats non pris en charge (HEIC, WebP).
- Vérifier une photo en portrait (orientation EXIF) et une légende de 120 caractères sur mobile réel.
- Créer plus de douze photos publiées et vérifier la pagination de la galerie invitée.
- Envoyer une photo proche de 10 Mo et mesurer la durée de génération des dérivés.
- Modérer depuis un compte co-organisateur et vérifier les métadonnées « Publiée par … le … ».

## Résultats du 27 octobre 2026

### Automatisé

- Pest : **140 tests, 649 assertions**, tous verts (SQLite `:memory:`), dont 19 tests dédiés à la galerie (143 assertions).
- Pint : **89 fichiers** conformes.
- Vite : build réussi (`app.css` 47,83 kB, `app.js` 53,88 kB, `scanner.js` 339,19 kB).
- Migration : `photos` appliquée sur MySQL 8.4.
- Playwright (Chromium headless, 1280 × 900 puis 390 × 844), événement de démonstration « Mariage QR 2026 », foyer « Famille Bamba » :
  - Envoi de deux photos, dérivés, compteurs, légende, publication.
  - Galerie invitée, URL signées valides/invalides, complétion mobile 390 px (débordement 0 px).
  - Masquage puis republication, rejet de la photo en attente, refus d'un faux JPEG.
  - Parcours de 9 étapes rejoué intégralement sur le code final, sans erreur JavaScript.
  - Captures : `30-galerie-moderation.png`, `31-galerie-invitee.png`, `32-galerie-publiee.png`, `33-galerie-mobile.png`.

### Environnement de validation

Le serveur `php artisan serve` du conteneur répondait en 5 à 16 s par requête sur le montage Windows (9p), ce qui faisait échouer les attentes du navigateur. Le parcours a été rejoué sur un conteneur one-off servant la même application avec l'opcache activé pour `cli-server` (`opcache.enable_cli=1`, `opcache.validate_timestamps=0`), sans modification du projet. Le service `app` standard a été redémarré ensuite.

### Corrections pendant la validation

- **Redirection de validation** : après un échec d'envoi, `back()` renvoyait vers l'URL du dernier aperçu image (`/events/{id}/galerie/{photo}/apercu`), car cette requête GET remplaçait l'URL précédente en session. L'envoi et la légende redirigent désormais explicitement vers la galerie (avec l'ancre `#photo-…` pour la légende), et un test de non-régression couvre ce cas.
- **Bornage des dérivés** : seule la largeur était limitée, si bien qu'un portrait 1200 × 2400 conservait 2000 px de haut. La tâche borne désormais les deux dimensions (plus grand côté à 1 600 px et à 480 px), avec un test dédié au portrait.
- **Script de parcours** (hors application) : les captures pleine page chargeaient mal les images `loading="lazy"` ; le script les défile et attend leur décodage avant capture. Les sections sont ciblées par `aria-labelledby` et les actions par carte pour éviter les boutons homonymes.

### Reste manuel

- Envoi depuis un téléphone réel et vérification du sélecteur de fichiers.
- Contrôle visuel d'un portrait EXIF et d'une légende longue sur mobile.
- Pagination au-delà de douze photos publiées.
- Durée de traitement d'un original proche de 10 Mo.
