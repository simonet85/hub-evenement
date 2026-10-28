# Hub Événement

Plateforme d'événements privés pour les particuliers en Côte d'Ivoire : invitations personnalisées, RSVP, QR code d'entrée, programme et carte, livre d'or, galerie modérée, cadeaux et cagnotte MoneyFusion, tableau de bord et exports traiteur.

## État : Phase 12 — Durcissement et mise en ligne

- Laravel 12, Livewire 3, Tailwind CSS 4, MySQL 8.4, Pest 3.
- Design System « Papier & lumière » : jetons (`design-system/tokens.json`) traduits en variables CSS et thème Tailwind.
- Authentification organisateur : inscription, connexion, déconnexion, mot de passe oublié et réinitialisation.
- Événements mariage, baptême, anniversaire ou fête, avec un accent visuel par type.
- Foyers : ajout manuel avec 1 à 20 places, suppression et import CSV (virgule ou point-virgule, en-tête facultative) avec lignes invalides signalées.
- Co-organisateurs invités par e-mail : ils consultent l'événement et gèrent les foyers ; le propriétaire garde les réglages et la gestion des co-organisateurs.
- Invitation publique par lien personnel (`/i/{jeton}`), non indexable : présentation de l'événement et du foyer.
- RSVP par foyer : présence ou absence, places dans la limite du foyer, prénoms, régime et message ; réponse modifiable jusqu'à la date limite.
- Réponse tardive acceptée et signalée « hors délai » ; réponse déjà enregistrée verrouillée après la date limite.
- Jeton révocable : régénérer le lien invalide immédiatement l'ancien.
- Reçu de réponse envoyé par e-mail via la file d'attente ; compteurs foyers, places, réponses et couverts côté organisateur.
- QR code d'entrée personnel par foyer, généré côté serveur en SVG, téléchargeable depuis l'organisation et intégré à l'invitation.
- Pointage mobile réservé aux comptes de l'événement : scan caméra ou saisie manuelle du code, sans dépendance à un service externe.
- Pointage direct, répété (« déjà pointé »), confirmation explicite pour un foyer sans réponse ou décliné, annulation possible.
- Page directe de pointage utilisable sans JavaScript, non indexable et sans fuite du jeton dans le `Referer`.
- Compteurs foyers pointés et couverts pointés côté organisateur.
- Programme structuré en étapes horodatées (titre, lieu, description), réordonnables et modifiables par le propriétaire et les co-organisateurs.
- Code vestimentaire global affiché sur l'invitation et la page programme.
- Repère placé sur une carte OpenStreetMap (Leaflet) à la création ou l'édition de l'événement ; coordonnées saisissables manuellement.
- Page programme et carte accessible par le lien personnel de chaque foyer (`/i/{jeton}/programme`), avec itinéraire Google Maps et Waze, non indexable et sans fuite du jeton.
- Relances manuelles groupées des foyers sans réponse : sélection, compteurs, deux relances au maximum espacées de 24 heures.
- E-mail de relance avec invitation personnelle et désinscription propre à l'événement ; le foyer désinscrit garde l'accès à son invitation et au RSVP.
- Lien WhatsApp pré-rempli par foyer (nom, événement, date, lieu, invitation) avec normalisation du numéro.
- Livre d'or : les foyers signent depuis leur invitation ; chaque message est publié, masqué ou rejeté par les organisateurs avant d'apparaître aux invités.
- Anti-spam du livre d'or : champ leurre et limitation à cinq dépôts par minute, compteurs de modération sur l'événement et le tableau de bord.
- Galerie photo : les organisateurs déposent jusqu'à 10 photos JPEG ou PNG par envoi (10 Mo maximum chacune) ; chaque photo est publiée, masquée ou supprimée avant d'apparaître aux invités.
- Photos stockées sur le disque privé, vignettes et versions web générées en tâche de fond, servies par URLs signées ; l'original n'est jamais public.
- Galerie invitée par lien personnel (`/i/{jeton}/galerie`), paginée par 12, avec légende facultative et compteurs de photos à modérer côté organisateur.
- Légendes modifiables photo par photo ; le rejet supprime définitivement le fichier et ses dérivés.
- Liste de cadeaux gérée par le propriétaire et les co-organisateurs : nom, description, prix unitaire et quantité.
- Réservation d'un ou plusieurs exemplaires, ou participation libre dans la limite du montant restant ; le montant d'une réservation est toujours recalculé côté serveur.
- Disponibilité d'un cadeau = total − paiements confirmés − paiements en attente non expirés ; les remboursements libèrent la place.
- Cagnotte facultative avec objectif, message d'accueil, montants suggérés (5 000 à 50 000 FCFA) et montant libre ; contribution anonyme possible.
- Page invité « Cadeaux et cagnotte » (`/i/{jeton}/cadeaux`), accessible depuis l'invitation, non indexable.
- Paiements MoneyFusion via le paquet officiel `simonet85/laravel-moneyfusion` : redirection, page de retour signée et webhook signé idempotent.
- Le statut n'est jamais accepté tel quel : il est revérifié côté serveur, et un montant différent de celui attendu n'est jamais crédité.
- Mode `fake` par défaut en local : une page de simulation remplace MoneyFusion, sans clé ni débit réel ; `http` active l'appel réel en production.
- Journal des paiements : référence, token, statut, note, remboursement manuel avec note obligatoire et encaissement hors ligne d'un paiement échoué.
- Expiration automatique des paiements en attente après deux heures ; les contributions en attente retiennent leur montant jusqu'à expiration.
- Compteurs de paiements en attente sur l'événement et le tableau de bord ; seuls les paiements confirmés comptent dans les totaux.
- Page de pilotage dédiée (`/events/{événement}/pilotage`) : foyers présents, absents et en attente, places prévues, couverts confirmés, régimes signalés et files de modération.
- Évolution des réponses sur 14 jours glissants, avec jours sans réponse et cumul incluant les réponses antérieures.
- Export des invités en CSV (UTF-8 avec BOM, séparateur point-virgule) ou Excel, avec coordonnées, réponse, couverts, régimes et pointage ; les cellules commençant par `=`, `+`, `-` ou `@` sont neutralisées.
- Dossier traiteur PDF paysage : synthèse, foyers présents, couverts, prénoms et régimes, sans aucune coordonnée.
- Exports couverts CSV/Excel réservés aux foyers présents, sans e-mail, téléphone, message privé ni jeton.
- Lien traiteur signé, utilisable sans compte, valable 24 heures, 7 jours (par défaut) ou 30 jours ; les téléchargements reprennent la même expiration.
- Portail traiteur non indexable et sans `Referer` ; un lien absent, altéré ou expiré renvoie 403.
- Révélation progressive des sections de l'invitation et du programme, avec décalage par lot, désactivée sans JavaScript ou en mouvement réduit.
- Formulaires invités : erreurs et aides reliées au champ (`aria-invalid`, `aria-describedby`, `role="alert"`), saisie mobile préparée (`autocomplete`, `inputmode`).
- Carte du programme en région nommée et décrite, utilisable au clavier ; Leaflet et les tuiles ne sont chargés qu'à l'approche du viewport.
- Galerie invitée en chargement paresseux (`loading="lazy"`, `decoding="async"`), ouverture dans une nouvelle fenêtre annoncée.
- Lien d'évitement, contour de focus visible et cibles tactiles principales de 44 px minimum sur le parcours invité.
- Limitation de débit sur les écritures publiques et sensibles : connexion, inscription, mot de passe, import CSV, galerie, relances, scan et paiements.
- Validation stricte des envois (longueurs maximales, sélections plafonnées) et jetons d'invitation acceptés uniquement au format attendu.
- Pages d'erreur personnalisées 403, 404, 419, 429, 500 et 503, sans trace technique ni donnée sensible.
- En-têtes de sécurité (`nosniff`, anti-iframe, `Referrer-Policy`, `Permissions-Policy`) et HSTS derrière HTTPS ; cookies de session `Secure`, `HttpOnly` et chiffrés en production.
- Journalisation des échecs de connexion, paiements non vérifiés, suppressions et purges ; rotation quotidienne des journaux.
- Suppression d'un événement par son propriétaire, avec confirmation par le nom et nettoyage des fichiers privés.
- Anonymisation planifiée des données invités après l'événement (`guests:purge`, 90 jours par défaut).
- Contrôle de production `app:check-production` (HTTPS, cookies, journal, file, cache, stockage, base, SMTP).
- Guide de déploiement, sauvegardes, restauration et retour arrière dans [`docs/deployment.md`](docs/deployment.md).
- Tableau de bord listant les événements accessibles et leur nombre de foyers.
- Infrastructure Docker de développement : application PHP 8.3, MySQL, Mailpit.

La constitution du projet (mission, stack, roadmap) est dans [`specs/`](specs/).

## Démarrage

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
npm install
npm run build
```

| Service | URL |
|---|---|
| Application | http://localhost:8000 |
| Mailpit (e-mails de test) | http://localhost:8025 |
| MySQL | `localhost:3307` (`hub` / `secret`, base `hub_evenement`) |

Les secrets de développement sont déjà alignés entre `.env.example`, `compose.yaml` et MySQL. Ne les réutilisez pas en production.

Le QR d'entrée encode des URL absolues : en production, renseignez `APP_URL` avec le domaine HTTPS définitif avant de générer les invitations.

### Paiements MoneyFusion

En développement, `MONEYFUSION_DRIVER=fake` (valeur par défaut) affiche une page de simulation locale : aucun paiement n'est débité et aucune clé n'est nécessaire.

Pour activer l'appel réel, renseignez dans `.env` :

```dotenv
MONEYFUSION_DRIVER=http
MONEYFUSION_API_URL=https://api.moneyfusion.net/api/create-payment
MONEYFUSION_APP_KEY=votre-cle
MONEYFUSION_CHECK_PAYMENT_URL=https://pay.moneyfusion.net/paiementNotif
MONEYFUSION_ROUTES_ENABLED=false
```

L'application fournit ses propres routes de retour et de webhook signées ; les routes du paquet restent désactivées (`MONEYFUSION_ROUTES_ENABLED=false`). MoneyFusion ne signe pas ses notifications : le montant et le statut sont donc toujours revérifiés auprès du fournisseur avant crédit.

Les reçus de réponse, les relances et la génération des dérivés photo passent par la file d'attente `database`. En développement, lancez un worker dans un terminal dédié :

```bash
docker compose exec app php artisan queue:work
```

L'expiration des paiements en attente est planifiée toutes les cinq minutes. En développement : `docker compose exec app php artisan schedule:work`. En production, un cron appelle `php artisan schedule:run` chaque minute.

En production, un cron exécute `php artisan queue:work --stop-when-empty` ou un superviseur équivalent. Un seul worker traite la file : les e-mails d'un lot de relances partent l'un après l'autre.

## Tests

```bash
docker compose exec app ./vendor/bin/pest
```

Les tests forcent SQLite en mémoire via `phpunit.xml`, même dans le conteneur : ils ne touchent pas la base MySQL de développement. Les variables sont déclarées à la fois dans `<env>` et `<server>`, car Docker les injecte dans `$_SERVER`, que Laravel lit avant `$_ENV`.

## Structure

```
app/Http/Controllers/Auth/   inscription, connexion, réinitialisation
app/Http/Controllers/        événements, foyers, import CSV, co-organisateurs, invitation, pointage, programme, relances, livre d'or, galerie photo, cadeaux et paiements, pilotage, exports
app/Console/Commands/        contrôle de production et purge des données invités
app/Http/Middleware/         en-têtes de sécurité
app/Enums/                   statuts et types de contribution
app/Jobs/                    envoi des e-mails de relance et génération des dérivés photo (file d'attente)
app/Mail/                    reçu de réponse RSVP et relance (file d'attente)
app/Models/                  modèles Eloquent
app/Policies/                autorisations des événements
app/Services/                import CSV, QR d'entrée, pointage, compteurs, contributions, métriques de pilotage
app/Services/Exports/        exports invités et couverts (CSV, XLSX OpenSpout, PDF Dompdf)
app/Services/MoneyFusion/    passerelle réelle (paquet) et simulateur local
design-system/tokens.json    source de vérité des jetons
resources/css/app.css        variables CSS + thème Tailwind 4
resources/js/scanner.js      caméra et pointage (html5-qrcode)
resources/js/map-picker.js   choix du repère sur la carte (Leaflet)
resources/js/program-map.js  carte en lecture seule de la page programme
resources/js/reveal.js       révélation progressive des sections (IntersectionObserver)
resources/views/caterer/     portail traiteur signé en lecture seule
resources/views/check-in/    page de scan et page directe de pointage
resources/views/errors/     pages 403, 404, 419, 429, 500 et 503
resources/views/events/      création, édition et détail d'un événement
resources/views/exports/     gabarit PDF du dossier traiteur
resources/views/gallery/     galerie invitée et modération des photos
resources/views/guestbook/   file de modération du livre d'or
resources/views/invitations/ invitation publique, QR, formulaire RSVP et page programme
resources/views/payments/    simulateur local et page de retour signée
resources/views/pilotage/    tableau de bord de pilotage et génération du lien traiteur
resources/views/registry/    page invité cadeaux et cagnotte, journal organisateur
resources/views/reminders/   page relances et confirmation de désinscription
resources/views/mail/        reçus et relances e-mail
specs/                       mission, tech-stack, roadmap, spécifications de phase
docs/deployment.md           mise en ligne, sauvegardes et exploitation
.docker/php/Dockerfile       image PHP de développement
compose.yaml                 app, MySQL, Mailpit, build des assets
```

## Prochaines étapes

Les douze phases planifiées sont livrées. Les évolutions non planifiées (multi-langue, plan de table avancé, application mobile) sont listées dans [`specs/roadmap.md`](specs/roadmap.md).

## Déploiement

La cible est un hébergement mutualisé PHP 8.3+ / MySQL, avec SSH, Composer et cron. Docker sert au développement local : un mutualisé ne peut pas exécuter de conteneurs. Les assets compilés (`public/build`) ne sont pas versionnés ; lancez `npm ci && npm run build` à chaque déploiement.

La procédure complète — prérequis, `.env` de production, droits, migrations, caches, cron, file d'attente, sauvegardes, restauration et retour arrière — est dans [`docs/deployment.md`](docs/deployment.md). Après déploiement, `php artisan app:check-production` vérifie la configuration.
