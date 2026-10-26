# Hub Événement

Plateforme d'événements privés pour les particuliers en Côte d'Ivoire : invitations personnalisées, RSVP, QR code d'entrée, programme et carte, livre d'or, galerie modérée, cadeaux et cagnotte MoneyFusion, tableau de bord et exports traiteur.

## État : Phase 1 — socle

- Laravel 12, Livewire 3, Tailwind CSS 4, MySQL 8.4, Pest 3.
- Design System « Papier & lumière » : jetons (`design-system/tokens.json`) traduits en variables CSS et thème Tailwind.
- Authentification organisateur : inscription, connexion, déconnexion, mot de passe oublié et réinitialisation.
- Tableau de bord vide, protégé par l'authentification.
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

## Tests

```bash
docker compose exec app ./vendor/bin/pest
```

## Structure

```
app/Http/Controllers/Auth/   inscription, connexion, réinitialisation
app/Models/                  modèles Eloquent
design-system/tokens.json    source de vérité des jetons
resources/css/app.css        variables CSS + thème Tailwind 4
resources/views/             layout, accueil, authentification, tableau de bord
specs/                       mission, tech-stack, roadmap
.docker/php/Dockerfile       image PHP de développement
compose.yaml                 app, MySQL, Mailpit, build des assets
```

## Prochaines étapes

La Phase 2 couvre les événements, les foyers, les invités et l'import CSV. Voir [`specs/roadmap.md`](specs/roadmap.md).

## Déploiement

La cible est un hébergement mutualisé PHP 8.3+ / MySQL, avec SSH, Composer et cron. Docker sert au développement local : un mutualisé ne peut pas exécuter de conteneurs. Les assets compilés (`public/build`) ne sont pas versionnés ; lancez `npm ci && npm run build` à chaque déploiement.
