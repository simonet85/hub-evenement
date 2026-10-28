# Validation — Phase 12

## Environnement

- Application de développement Docker (PHP 8.3, MySQL 8.4) servie sur `http://localhost:8000`.
- Tests Pest sur SQLite `:memory:` via `phpunit.xml`.
- Chromium headless (Playwright 1243) en 1280 × 900 pour l'organisateur et 390 × 844 mobile tactile pour l'invité.
- Le montage Windows (9p) ralentit les requêtes : le parcours navigateur utilise des attentes de 180 s et bloque les polices Google et les tuiles OpenStreetMap.

## Tests automatisés (Pest)

24 nouveaux tests couvrent le durcissement, la suppression, la rétention et le contrôle de production.

`tests/Feature/HardeningTest.php` — 8 tests :

- En-têtes `nosniff`, `DENY`, `no-referrer` et `Permissions-Policy` sur l'accueil et une invitation ; HSTS uniquement en HTTPS.
- Sixième connexion en une minute → 429 avec la page française « Trop de tentatives ».
- Échec de connexion journalisé (`auth.failed`) avec e-mail normalisé et IP, sans mot de passe.
- 404 personnalisée pour une adresse inconnue et pour un jeton mal formé (47 caractères ou moins).
- 403 personnalisée pour un événement étranger.
- 500 générique en `APP_DEBUG=false`, sans message d'exception ni nom de classe.

`tests/Feature/EventDeletionTest.php` — 5 tests :

- Le propriétaire confirme avec le nom exact : événement, foyers et dossier `events/{id}` supprimés, redirection et statut, journal `event.deleted`.
- Confirmation absente ou partielle : erreur de validation, événement conservé, zone de suppression visible.
- Co-organisateur et organisateur étranger : 403, événement conservé.
- Visiteur non connecté : redirection vers la connexion.

`tests/Feature/RetentionTest.php` — 6 tests :

- `--dry-run` affiche la simulation sans modifier foyers ni contributions.
- Événement ancien : foyers, livre d'or supprimés ; contribution anonymisée (foyer détaché, nom « Invité anonymisé », téléphone vide, message et charge nuls) ; montant et statut conservés ; cadeau, photo et événement intacts ; événement récent intact.
- Seconde exécution sans effet (0 foyer, 0 message, 0 contribution).
- Seuil par défaut configurable (`GUEST_DATA_RETENTION_DAYS`) et option `--days`.
- Nombre de jours invalide : échec explicite.

`tests/Feature/ProductionCheckTest.php` — 5 tests :

- Configuration conforme : code 0 et message « Configuration de production conforme. ».
- `APP_DEBUG=true` : code 1 avec le contrôle en échec nommé.
- Cookies non sécurisés et journal verbeux : code 1 avec les deux explications.
- `--mail` avec `Mail::fake` : e-mail de test envoyé, code 0.
- Adresse de test invalide : échec propre.

## Parcours navigateur

1. **En-têtes et 404** : en-têtes de sécurité présents sur l'accueil, HSTS absent en HTTP, jeton mal formé en 404 avec la page personnalisée.
2. **Inscription et événement** : compte organisateur créé, événement mariage créé et statut « Événement créé » annoncé.
3. **Foyer et import CSV** : foyer « Famille Koné » ajouté ; import de deux foyers (point-virgule, en-tête) avec statut « 2 foyer(s) importé(s). » ; les deux foyers sont visibles.
4. **Co-organisateur** : second compte créé, ajout par e-mail avec statut « Co-organisateur ajouté. » ; l'événement apparaît dans son tableau de bord, il ouvre l'événement et la zone de suppression reste réservée au propriétaire.
5. **Parcours invité mobile** : lien personnel du foyer, invitation en 200 et non indexable ; RSVP présent pour 2 personnes avec régime, statut de confirmation annoncé ; aucun débordement horizontal (0 px) ; lien invalide en 404 lisible sur mobile.
6. **Suppression** : confirmation par le nom, statut « Événement supprimé. », événement retiré du tableau de bord et invitation devenue 404.

32 assertions, aucune erreur JavaScript. Capture : `50-phase12-invitation-mobile.png`.

## Vérifications HTTP

```bash
curl -sI http://localhost:8000/
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8000/route-inconnue
curl -s -o /dev/null -w "%{http_code}\n" http://localhost:8000/i/trop-court
```

- `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer` et `Permissions-Policy: camera=(self), microphone=(), geolocation=()` présents.
- Pas de `Strict-Transport-Security` sur HTTP ; il est ajouté en HTTPS (couvert par Pest).
- 404 pour une route inconnue et pour un jeton de moins de 48 caractères ; page « Page introuvable ».

## Contrôle de production et purge

```bash
docker compose exec app php artisan app:check-production
docker compose exec app php artisan guests:purge --dry-run
```

- En local, le contrôle sort en code 1 avec 7 écarts attendus : environnement, débogage, URL HTTPS, cookies `secure`/`encrypt`, journal quotidien et niveau de journal. Les 10 autres contrôles passent.
- La simulation de purge répond « Aucun événement terminé depuis au moins 90 jour(s). » sur la base de développement.

## Résultats du 28 octobre 2026

### Automatisé

- Pest : **245 tests, 1 147 assertions**, tous verts (SQLite `:memory:`), dont **24 tests dédiés à la Phase 12**.
- Pint : **127 fichiers** conformes, dont les 21 modifiés ou ajoutés.
- Vite : build réussi en 2,10 s (`app.css` 62,04 kB — gzip 11,72 ; `app.js` 54,16 kB — gzip 20,69 ; `reveal.js` 0,75 kB ; `program-map.js` 0,76 kB ; `leaflet.js` 149,51 kB ; `scanner.js` 339,19 kB).
- Playwright : parcours organisateur + invité mobile complet, code de sortie 0, 32 assertions, aucune erreur JavaScript.
- `app:check-production` : échec attendu en local (7 contrôles) et succès sur configuration conforme dans les tests.

### Corrections pendant la validation

- **Apostrophes échappées** : `assertSee` échappe par défaut le HTML ; les textes contenant une apostrophe (« Retour à l'accueil », « L'incident a été enregistré ») sont comparés sur le HTML brut.
- **Documentation de rétention** : le guide indiquait à tort que le livre d'or restait après la purge ; il précise désormais que le livre d'or et les relances disparaissent avec les foyers, conformément à la cascade.
- **Couverture** : ajout de la vérification des en-têtes sur une invitation et de la conservation des cadeaux et photos lors de la purge.

### Reste manuel

- Déploiement réel sur le mutualisé : HTTPS, `TRUSTED_PROXIES`, cron planificateur, worker de file et permissions.
- Envoi SMTP réel avec `php artisan app:check-production --mail=` sur le domaine définitif.
- Sauvegarde `mysqldump` + `storage/app/private`, puis restauration testée avant ouverture.
- Pages 419 (session expirée) et 503 (maintenance) en conditions réelles ; la 429 est couverte par Pest.
- Paiement MoneyFusion live avec un petit montant, puis remboursement.
- Parcours complet sur un smartphone réel et vérification des journaux après quelques jours d'exploitation.
