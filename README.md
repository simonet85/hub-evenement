# Hub Événement

Plateforme d'événements privés pour les particuliers en Côte d'Ivoire : invitations personnalisées, RSVP, QR code d'entrée, programme et carte, livre d'or, galerie modérée, cadeaux et cagnotte MoneyFusion, tableau de bord et exports traiteur.

## État : Phase 7 — Livre d'or

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

Les reçus de réponse et les relances passent par la file d'attente `database`. En développement, lancez un worker dans un terminal dédié :

```bash
docker compose exec app php artisan queue:work
```

En production, un cron exécute `php artisan queue:work --stop-when-empty` ou un superviseur équivalent. Un seul worker traite la file : les e-mails d'un lot de relances partent l'un après l'autre.

## Tests

```bash
docker compose exec app ./vendor/bin/pest
```

Les tests forcent SQLite en mémoire via `phpunit.xml`, même dans le conteneur : ils ne touchent pas la base MySQL de développement. Les variables sont déclarées à la fois dans `<env>` et `<server>`, car Docker les injecte dans `$_SERVER`, que Laravel lit avant `$_ENV`.

## Structure

```
app/Http/Controllers/Auth/   inscription, connexion, réinitialisation
app/Http/Controllers/        événements, foyers, import CSV, co-organisateurs, invitation, pointage, programme, relances, livre d'or
app/Jobs/                    envoi des e-mails de relance (file d'attente)
app/Mail/                    reçu de réponse RSVP et relance (file d'attente)
app/Models/                  modèles Eloquent
app/Policies/                autorisations des événements
app/Services/                import CSV, QR d'entrée, pointage et compteurs
design-system/tokens.json    source de vérité des jetons
resources/css/app.css        variables CSS + thème Tailwind 4
resources/js/scanner.js      caméra et pointage (html5-qrcode)
resources/js/map-picker.js   choix du repère sur la carte (Leaflet)
resources/js/program-map.js  carte en lecture seule de la page programme
resources/views/check-in/    page de scan et page directe de pointage
resources/views/events/      création, édition et détail d'un événement
resources/views/guestbook/   file de modération du livre d'or
resources/views/invitations/ invitation publique, QR, formulaire RSVP et page programme
resources/views/reminders/   page relances et confirmation de désinscription
resources/views/mail/        reçus et relances e-mail
specs/                       mission, tech-stack, roadmap, spécifications de phase
.docker/php/Dockerfile       image PHP de développement
compose.yaml                 app, MySQL, Mailpit, build des assets
```

## Prochaines étapes

La Phase 8 couvre la galerie photo modérée. Voir [`specs/roadmap.md`](specs/roadmap.md).

## Déploiement

La cible est un hébergement mutualisé PHP 8.3+ / MySQL, avec SSH, Composer et cron. Docker sert au développement local : un mutualisé ne peut pas exécuter de conteneurs. Les assets compilés (`public/build`) ne sont pas versionnés ; lancez `npm ci && npm run build` à chaque déploiement.
