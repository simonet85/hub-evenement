# Tech Stack

La plateforme est une application **Laravel 12** rendue côté serveur, avec **Livewire 3** pour les interactions, **Tailwind CSS** pour le style et **MySQL** pour les données. Les jetons du Design System « Papier & lumière » sont la source unique des couleurs, typographies, espacements et rayons.

Environnement de développement : **Docker Compose** (application PHP, MySQL, Mailpit), pour un socle reproductible sans PHP local.

Hébergement cible : **mutualisé PHP/MySQL**, avec les contraintes que cela impose (voir la checklist ci-dessous). Ce choix est assumé : il réduit les coûts et l'administration, au prix de l'absence de worker permanent. Docker ne peut pas tourner sur un mutualisé : une production conteneurisée nécessiterait un VPS, décision à confirmer.

## Core

| Couche | Choix | Raison |
|---|---|---|
| Langage | PHP 8.2+ | Base des maquettes, écosystème mature, hébergement mutualisé courant |
| Framework | **Laravel 12** | Auth, files, notifications, validation, migrations et tests intégrés |
| UI | **Livewire 3 + Blade** | Interactivité sans SPA, SSR complet, composants proches des maquettes |
| CSS | **Tailwind CSS** | Jetons du Design System traduits en configuration |
| Base de données | **MySQL** | Disponible partout en mutualisé, adapté à ce volume |
| Tests | **Pest** | Rapide, lisible, intégré à Laravel |
| Queue | **Database** (`QUEUE_CONNECTION=database`) | Pas de Redis sur mutualisé |
| Planification | Cron + `schedule:run` | Relances, nettoyages, vignettes |
| Stockage photos | Disque privé local + URLs signées | Hors dossier public, servi par contrôleur |
| QR codes | Génération SVG côté serveur (`endroid/qr-code`) | Pas de dépendance navigateur, imprimable |
| Images | Intervention Image | Compression et vignettes |
| E-mails | SMTP transactionnel externe (Brevo, Mailgun…) | Le SMTP mutualisé est limité et peu fiable |
| Développement | **Docker Compose** (PHP 8.3, MySQL 8.4, Mailpit) | Environnement reproductible, aucun PHP local requis |
| Production | Mutualisé PHP/MySQL | Coût et administration réduits (Docker impossible sur mutualisé) |

## Contraintes de l'hébergement mutualisé

Checklist à vérifier **avant** la phase 1 :

- PHP ≥ 8.2 avec `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` ou `imagick`, `zip`.
- Accès SSH et **Composer** pour `composer install` et `php artisan`.
- **Cron** autorisé, au minimum toutes les 5 minutes.
- HTTPS avec certificat, et un domaine ou sous-domaine dédié.
- Accès en écriture à `storage/` et `bootstrap/cache/`.
- SMTP sortant fourni par un service transactionnel externe.

Conséquences :

- **Pas de worker persistant** : la queue est traitée par cron (`queue:work --stop-when-empty --max-time=…`) et les envois partent par lots. Latence maximale acceptée : 5 minutes.
- **Pas de déploiement atomique** : mise en ligne par `git pull`, `composer install`, `php artisan migrate`, mode maintenance pendant l'opération.
- **Pas de Redis** : cache, sessions et queue en base de données.
- **Pas de traitement lourd en requête** : vignettes et e-mails passent par la queue, avec des limites de taille et de durée.

## Données et intégrations

- **Modèles** : Event, Guest (foyer), Invitation (jeton unique), Rsvp, ProgramItem, Venue, GuestbookEntry, Photo, GiftItem, Contribution, Payment, User, EventRole.
- **Multi-événements dès le départ** : un compte, plusieurs événements, rôles organisateur / co-organisateur / traiteur (lecture seule avec expiration).
- **Invitations** : jeton non devinable, URL signée `/i/{token}`, révocable ; aucun compte invité.
- **MoneyFusion** (jalon J5) : création du paiement côté serveur, redirection, retour utilisateur, webhook signé et idempotent. Aucun montant décidé par le navigateur. Clés dans `.env`, mode sandbox pour les tests et pour le développement.
- **OpenStreetMap** pour la carte, liens vers Google Maps et Waze pour l'itinéraire ; aucune clé payante.

## Ce que nous ne faisons pas

- Pas de SPA React/Vue ni d'Inertia : le rendu reste côté serveur.
- Pas d'API REST publique ni de client mobile natif en V1.
- Pas de Redis, Horizon ou worker permanent.
- Pas de Docker en production tant que la cible est un mutualisé ; Docker sert au développement local.
- Pas de multi-langue : français uniquement.
