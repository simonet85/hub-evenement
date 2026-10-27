# Validation — Phase 4

## Tests automatisés (Pest)

### Jeton et QR
- Chaque foyer reçoit un `entry_token` unique de 48 caractères, distinct du jeton d'invitation.
- Les foyers importés par CSV reçoivent un jeton.
- Le QR SVG est téléchargeable par le propriétaire et le co-organisateur ; un tiers reçoit 403 ; un foyer d'un autre événement renvoie 404.
- Le QR contient l'URL de pointage du foyer.
- L'invitation affiche le QR et le téléchargement.

### Accès au pointage
- Un visiteur non connecté est redirigé vers la connexion puis revient au pointage.
- Un tiers reçoit 403 sur le scan et sur la page directe.
- Un jeton inconnu renvoie une erreur sans écriture.
- Un jeton d'un autre événement est refusé sur le scan de l'événement.

### Pointage
- Un foyer présent confirmé est pointé directement, avec horodatage et auteur.
- Un foyer sans réponse ou décliné exige une confirmation ; sans elle, aucun écrit.
- Un scan répété signale « déjà pointé » et conserve le premier horodatage.
- L'annulation efface l'horodatage et l'auteur.
- Les compteurs de foyers et de couverts pointés sont corrects.
- La page directe fonctionne sans JavaScript et reste non indexable / sans `Referer`.

### Parcours navigateur
- Saisie manuelle du code ou de l'URL sur la page de scan.
- Page directe ouverte depuis le QR, pointage puis annulation.
- Foyer non confirmé : confirmation obligatoire.
- Compteurs mis à jour sur la page de scan et la page événement.
- Affichage mobile 390 px, aucun débordement.
- Caméra : vérifier l'invitation à la permission et le repli manuel ; le décodage caméra n'est pas automatisable en headless.

## Vérifications manuelles

```bash
docker compose exec app php artisan migrate
docker compose exec app ./vendor/bin/pest
docker compose exec app ./vendor/bin/pint --test
npm run build
```

- Ouvrir l'invitation sur mobile, télécharger le QR et vérifier son contenu.
- Ouvrir `/check-in/{jeton}` sans être connecté, puis après connexion.
- Sur la page de scan, autoriser la caméra sur un mobile HTTPS/localhost, scanner le QR et vérifier le résultat.
- Scanner une deuxième fois, puis annuler le pointage.
- Vérifier les compteurs après chaque action.

## Résultats du 27 octobre 2026

### Automatisé

- Pest : **74 tests, 291 assertions**, tous verts (SQLite `:memory:`).
- Pint : **64 fichiers** conformes.
- Vite : build réussi (`app.css` 26,01 kB, `app.js` 53,36 kB, `scanner.js` 339,25 kB).
- Playwright (Chromium headless, 390 × 844 et 1280 × 900), événement de démonstration à 3 foyers / 9 places (2 réponses, 5 couverts) :
  - QR téléchargé par l'organisateur, puis décodé avec `html5-qrcode` : le SVG encode exactement `http://localhost:8000/check-in/{jeton d'entrée}`.
  - Invitation mobile : QR visible, embarqué en data URI, aucun débordement horizontal.
  - Page de scan : code inconnu refusé ; Koné pointé (1 / 3 foyers, 2 / 9 couverts) ; second scan « Déjà pointé » sans doublon ; annulation (0 / 3, 0 / 9) ; re-pointage ; Bamba (en attente) exige la confirmation, puis 2 / 3 et 4 / 9.
  - Caméra : flux factice → « Caméra active. Présentez le QR dans le cadre. », arrêt propre, repli manuel disponible.
  - Page directe sans JavaScript (Yao) : pointage → 3 / 3 foyers et 7 / 9 couverts, annulation puis re-pointage, en-têtes `noindex` et `no-referrer`.
  - Espace organisateur : compteurs 3 / 3 et 7 / 9, trois badges « Pointé · », auteur affiché, aucun débordement à 390 px, aucune erreur JavaScript.
  - Captures : `09-invitation-qr.png`, `10-evenement-pointage.png`, `11-scan-vide.png`, `12-scan-resultat.png`, `13-scan-camera.png`, `14-pointage-direct-sans-js.png`.

### Reste manuel

- Décoder un QR avec une vraie caméra sur mobile (HTTPS ou localhost). Le flux factice valide l'interface et le repli, pas le décodage caméra réel.

### Incident corrigé

- Un premier run Pest lancé avant la correction de `phpunit.xml` a utilisé MySQL (Docker injecte les variables dans `$_SERVER`, prioritaire sur `$_ENV`) et vidé la base de développement. Les variables de test sont désormais forcées dans `<env>` **et** `<server>` ; le run final a laissé les données MySQL intactes.
