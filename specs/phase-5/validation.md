# Validation — Phase 5

## Tests automatisés (Pest)

### Étapes du programme
- Ajout d'une étape horodatée (début, fin, titre, lieu, description) avec position initiale.
- Étapes successives numérotées dans l'ordre d'ajout.
- Modification d'une étape existante.
- Réordonnancement haut/bas : l'ordre et les positions sont renumérotés ; déplacer la première étape vers le haut ne change rien.
- Suppression d'une étape.
- Validation : heure de début invalide, fin antérieure au début et titre vide refusés, sans écriture.

### Autorisations
- Le propriétaire et un co-organisateur gèrent le programme.
- Un tiers reçoit 403 sur l'ajout, la modification et la suppression.
- Une étape appartenant à un autre événement renvoie 404 sur la modification, la suppression et le déplacement.

### Page programme
- La page par jeton affiche la frise dans l'ordre, les horaires, le code vestimentaire, la carte et les liens Google Maps/Waze construits depuis les coordonnées.
- La page est `noindex, nofollow` et `no-referrer`.
- Sans coordonnées : adresse affichée, aucun conteneur de carte, lien Google Maps par recherche, pas de Waze.
- Un jeton inconnu renvoie 404.

### Événement et invitation
- Le code vestimentaire et les coordonnées sont enregistrés par le propriétaire.
- Les coordonnées hors bornes ou incomplètes sont refusées.
- L'invitation affiche le code vestimentaire et le bouton « Programme et carte » vers le bon jeton.
- L'espace organisateur affiche le programme, l'état vide, les actions et le lien d'aperçu vers la page programme.

## Parcours navigateur

- Édition de l'événement : code vestimentaire saisi, coordonnées saisies au clavier, statut « Repère placé à … » et repère visible sur la carte.
- Ajout de deux étapes, réordonnancement haut puis bas, modification du titre, ajout puis suppression d'une troisième étape ; l'ordre affiché suit chaque action.
- Page programme ouverte depuis l'aperçu organisateur : frise, horaires, code vestimentaire, lieu, carte Leaflet avec tuiles OpenStreetMap, liens Google Maps et Waze, `noindex` et `no-referrer`.
- Affichage mobile 390 px : aucun débordement horizontal.
- Invitation publique : code vestimentaire et bouton « Programme et carte ».
- Page programme sans JavaScript : frise, tenue, adresse, liens d'itinéraire et note de repli rendus côté serveur.
- Aucune erreur JavaScript.

## Vérifications manuelles

```bash
docker compose exec app php artisan migrate
docker compose exec app ./vendor/bin/pest
docker compose exec app ./vendor/bin/pint --test
npm run build
```

- Cliquer sur la carte d'édition pour déplacer le repère, vérifier la latitude/longitude mises à jour, puis « Retirer le repère ».
- Saisir des coordonnées hors bornes et vérifier les messages d'erreur du formulaire.
- Ouvrir la page programme sur un mobile réel : pincer pour zoomer, liens Google Maps et Waze.
- Vérifier le programme avec un événement sans coordonnées.

## Résultats du 27 octobre 2026

### Automatisé

- Pest : **90 tests, 365 assertions**, tous verts (SQLite `:memory:`), dont 16 tests dédiés au programme.
- Pint : **69 fichiers** conformes.
- Vite : build réussi avec Leaflet (`app.css` 47,13 kB, `app.js` 53,88 kB, `leaflet.js` 149,51 kB, `map-picker.js` 1,39 kB, `program-map.js` 0,76 kB).
- Playwright (Chromium headless, 1280 × 900 puis 390 × 844), événement de démonstration « Mariage QR 2026 » (3 foyers) :
  - Carte d'édition : repère placé à 5.3599, -4.0083, statut mis à jour, tuiles OSM affichées.
  - Code vestimentaire enregistré puis affiché sur l'événement, l'invitation et la page programme.
  - Deux étapes ajoutées, Réception remontée puis redescendue, « Cérémonie » renommée « Cérémonie religieuse », étape « Bal » ajoutée puis supprimée ; ordre final Cérémonie religieuse / Réception.
  - Page programme : frise ordonnée, horaires, tenue, lieu, carte Leaflet avec tuiles et repère, liens `destination=5.3599,-4.0083` et `ll=5.3599%2C-4.0083`, en-têtes `noindex` et `no-referrer`.
  - Mobile 390 px : débordement horizontal 0 px.
  - Sans JavaScript : contenu complet et note de repli, lien Google Maps fonctionnel, aucun débordement.
  - Aucune erreur JavaScript.
  - Captures : `15-carte-repere.png`, `16-evenement-programme.png`, `17-programme-desktop.png`, `18-programme-mobile.png`, `19-invitation-programme.png`, `20-programme-sans-js.png`.

### Bugs corrigés pendant la validation

- **Table du modèle** : la migration crée `event_program_steps`, le modèle visait `program_steps` ; `ProgramStep` déclare désormais `protected $table = 'event_program_steps'`. Les tests Pest ont révélé l'erreur.
- **Carte d'édition** : le statut du repère et le bouton « Retirer le repère » étaient recherchés dans le conteneur de la carte alors qu'ils en sont frères ; ils sont maintenant recherchés dans le document. Le parcours navigateur a révélé l'erreur.

### Reste manuel

- Tester la carte sur un mobile réel (gestes tactiles, chargement des tuiles sur réseau lent).
- Vérifier l'ouverture effective des liens Google Maps et Waze sur un appareil avec les applications installées.
