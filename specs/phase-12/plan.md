# Plan — Phase 12

## Étapes

1. **Entrées** : throttles sur authentification, import, galerie, pointage, simulateur et webhook ; bornes de validation ; contrainte de jeton ; `payload` retiré du `$fillable`.
2. **Erreurs** : mise en page autonome `errors/layout.blade.php` et six vues ; test des statuts 403, 404, 429 et 500.
3. **Transport** : middleware `SecurityHeaders` dans le groupe `web`, `TRUSTED_PROXIES`, cookies sécurisés documentés.
4. **Journalisation** : échec de connexion, webhook non vérifié, suppression d'événement, purge des données.
5. **Suppression** : politique `delete`, route, contrôleur avec confirmation, nettoyage du dossier photo, zone sensible sur la page événement.
6. **Rétention** : `config/retention.php`, commande `guests:purge`, planification quotidienne.
7. **Exploitation** : commande `app:check-production`, `.env.production.example`, `docs/deployment.md`, README.
8. **Validation** : Pest, Pint, build, vérifications HTTP, régression mobile, `validation.md`.

## Fichiers

```
bootstrap/app.php
routes/web.php
routes/console.php
app/Http/Middleware/SecurityHeaders.php
app/Http/Controllers/Auth/AuthenticatedSessionController.php
app/Http/Controllers/Auth/PasswordResetLinkController.php
app/Http/Controllers/Auth/NewPasswordController.php
app/Http/Controllers/CoOrganizerController.php
app/Http/Controllers/ReminderController.php
app/Http/Controllers/PaymentController.php
app/Http/Controllers/EventController.php
app/Policies/EventPolicy.php
app/Models/Event.php
app/Models/Contribution.php
app/Console/Commands/PurgeGuestData.php
app/Console/Commands/CheckProduction.php
config/retention.php
resources/views/errors/layout.blade.php
resources/views/errors/403.blade.php
resources/views/errors/404.blade.php
resources/views/errors/419.blade.php
resources/views/errors/429.blade.php
resources/views/errors/500.blade.php
resources/views/errors/503.blade.php
resources/views/events/show.blade.php
.env.example
.env.production.example
docs/deployment.md
README.md
tests/Feature/HardeningTest.php
tests/Feature/EventDeletionTest.php
tests/Feature/RetentionTest.php
tests/Feature/ProductionCheckTest.php
specs/phase-12/requirements.md
specs/phase-12/plan.md
specs/phase-12/validation.md
```

## Détails techniques

### Débit et validation

- `throttle:N,1` inline, cohérent avec les routes existantes.
- Connexion : `email` `max:255`, `password` `max:255` ; réinitialisation : `email` et `token` `max:255`.
- `guest_ids` : `array|max:200`, `guest_ids.*` : `integer|distinct`.
- `co_email` : `max:255`.
- Routes `i/{token}` : `->where('token', '[A-Za-z0-9]{48}')` sur les neuf routes publiques.
- `Contribution::$fillable` sans `payload` (écrit uniquement par `forceFill` dans `ContributionService`).

### Pages d'erreur

- `errors/layout.blade.php` : HTML autonome, `@vite('resources/css/app.css')`, en-tête marque, carte centrée, lien accueil, pied de page, `noindex`, `no-referrer`, `theme-color`.
- Chaque vue définit `code`, `title`, `eyebrow`, `message`. Pas de session, Livewire ni JS.
- 500 : texte générique uniquement.

### En-têtes

- Middleware `SecurityHeaders` ajouté au groupe `web` via `bootstrap/app.php`.
- `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer`, `Permissions-Policy: camera=(self), microphone=(), geolocation=()`.
- `Strict-Transport-Security: max-age=31536000` si `$request->isSecure()`.
- `TRUSTED_PROXIES` (liste séparée par des virgules) passé à `trustProxies` ; vide par défaut.
- `.env.example` : `TRUSTED_PROXIES=` et commentaires de production.

### Journalisation

- `Log::warning('auth.failed', ['email' => ..., 'ip' => ...])` avant l'échec de connexion.
- `Log::warning('payment.unverified', [...])` dans le webhook quand la vérification échoue.
- `Log::info('event.deleted', ['event_id', 'user_id'])`.
- `Log::info('guest-data.purged', ['event_id', 'guests', 'guestbook', 'contributions'])` sans identité.
- Aucun message, téléphone, jeton ni charge fournisseur dans les journaux.

### Suppression d'événement

- `EventPolicy::delete` : `$event->user_id === $user->id`.
- Route `DELETE events/{event}` dans le groupe `auth`.
- `EventController::destroy` : autorisation, validation `confirmation` égale au nom, suppression, journal, redirection tableau de bord.
- `Event::booted` : sur `deleting`, `Storage::disk('local')->deleteDirectory('events/'.$event->id)`.
- Zone sensible sur `events/show` réservée au propriétaire : explication, saisie du nom, bouton `btn-danger`, `autocomplete="off"`.

### Rétention

- `config/retention.php` : `guest_data_days` depuis `GUEST_DATA_RETENTION_DAYS`, 90 par défaut.
- `guests:purge {--days=} {--dry-run}` : sélection des événements `starts_at <= now()-N jours`; par événement, comptage, puis transaction : contributions anonymisées, foyers supprimés (cascade livre d'or/relances), journal.
- `--dry-run` affiche les comptes sans écriture.
- `Schedule::command('guests:purge')->dailyAt('03:30')`.

### Contrôle production

- `app:check-production {--mail=}` : liste de contrôles booléens, sortie ✓/✗, code 1 si échec.
- Contrôles : env, debug, URL HTTPS, cookies (secure/encrypt/httpOnly/sameSite), niveau de log, clé, mailer, file, cache, base (`getPdo`), manifeste `public/build/manifest.json`, dossiers `storage/framework`, `storage/app/private`, `bootstrap/cache`.
- `--mail` : `Mail::raw` vers l'adresse, erreur interceptée.

### Exploitation

- `.env.production.example` : valeurs non secrètes, `APP_ENV=production`, `APP_DEBUG=false`, HTTPS, cookies sécurisés, `LOG_STACK=daily`, `LOG_DAILY_DAYS=14`, `LOG_LEVEL=warning`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, SMTP à compléter, `GUEST_DATA_RETENTION_DAYS=90`.
- `docs/deployment.md` : prérequis PHP/extensions, build local, `.env`, permissions, `migrate --force`, caches, cron planificateur et file, HTTPS/proxy, SMTP, sauvegarde `mysqldump` + archive `storage/app/private` + `.env`, rétention 30 jours, restauration, contrôle post-déploiement, retour arrière, surveillance.
- README : état Phase 12, fonctionnalités, structure, lien vers le runbook, prochaines étapes.

## Tests

### HardeningTest

- En-têtes sur l'accueil et une invitation ; HSTS seulement en HTTPS.
- 6e connexion → 429 et page française.
- 404 inconnue et 403 d'un tiers → pages personnalisées.
- 500 simulée en `APP_DEBUG=false` → message générique, pas de secret.
- Jeton mal formé → 404.
- Échec de connexion journalisé sans mot de passe.

### EventDeletionTest

- Propriétaire : confirmation exacte, suppression, redirection, lignes et fichiers supprimés, journal.
- Confirmation erronée : erreur, rien supprimé.
- Co-organisateur et tiers : 403.
- Autre événement intact.

### RetentionTest

- `--dry-run` ne modifie rien.
- Événement ancien : foyers, livre d'or et relances supprimés ; contributions anonymisées ; événement, photos et cadeaux conservés.
- Événement récent intact ; deuxième exécution idempotente ; option `--days`.

### ProductionCheckTest

- Configuration conforme → code 0.
- `APP_DEBUG=true` → code 1 et mentionne le contrôle.
- `--mail` avec `Mail::fake` → message envoyé.

## Risques

- **Throttle trop strict** : limites larges sur le pointage (120/min) pour ne pas bloquer une entrée chargée ; limites auth standard.
- **Suppression irréversible** : confirmation par nom, réservée au propriétaire, journalisée, nettoyage du stockage.
- **Purge trop agressive** : désactivée par défaut hors planification, `--dry-run`, seuil en jours, seuls les événements terminés, montants conservés.
- **HSTS derrière un proxy** : dépend de `TRUSTED_PROXIES` correctement renseigné ; documenté.
- **Page 500 sans session** : layout autonome, aucune dépendance à l'authentification ou à Livewire.
- **Purge et sauvegardes** : les sauvegardes antérieures conservent les données ; la durée de conservation des sauvegardes est documentée (30 jours).
