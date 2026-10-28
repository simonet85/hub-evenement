# Mise en ligne

Ce document décrit le déploiement de Hub Événement sur un hébergement mutualisé PHP 8.3+ / MySQL 8, avec SSH, Composer et cron. Docker sert uniquement au développement local.

## Prérequis

| Élément | Minimum |
|---|---|
| PHP | 8.3 avec `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `gd` ou `imagick`, `dom`, `xml`, `curl`, `zip` |
| MySQL | 8.0 (ou MariaDB 10.6+) |
| Composer | 2.x |
| Node.js | 20.x (uniquement pour compiler les assets) |
| Accès | SSH, cron, base de données dédiée |
| Disque | Prévoir l'espace des photos (10 Mo maximum par photo, dérivés compris) |

Le document root du site doit pointer sur le dossier `public/`, jamais sur la racine du projet.

## 1. Récupérer le code

```bash
cd /home/utilisateur/hub-evenement
git clone <dépôt> .
git checkout main
composer install --no-dev --optimize-autoloader
```

Les assets compilés ne sont pas versionnés. Sur une machine de build ou sur le serveur si Node est disponible :

```bash
npm ci
npm run build
```

Le dossier `public/build` doit être présent sur le serveur : `php artisan app:check-production` échoue sans son manifeste.

## 2. Configurer l'environnement

```bash
cp .env.production.example .env
php artisan key:generate
```

Renseignez au minimum :

- `APP_URL` avec l'URL HTTPS définitive (le QR d'entrée encode des URL absolues) ;
- `DB_*` avec la base et un utilisateur dédié à droits limités ;
- `MAIL_*` avec le SMTP du fournisseur et une adresse d'expédition autorisée ;
- `MONEYFUSION_*` avec les clés live et `MONEYFUSION_DRIVER=http` ;
- `TRUSTED_PROXIES` si un reverse proxy ou un CDN termine le HTTPS.

Ne réutilisez jamais les secrets de développement (`hub` / `secret`) et ne versionnez pas `.env`.

Vérifiez ensuite la configuration :

```bash
php artisan app:check-production
```

Le contrôle vérifie `APP_ENV=production`, `APP_DEBUG=false`, l'URL HTTPS, la clé, les cookies (`secure`, `encrypt`, `http_only`), la rotation quotidienne du journal, le niveau `warning` ou plus strict, un transport e-mail réel, une file et un cache partagés, le manifeste Vite, les droits d'écriture et la connexion MySQL. Il sort en erreur au premier contrôle en échec.

Pour tester le SMTP réel :

```bash
php artisan app:check-production --mail=adresse@exemple.test
```

### Derrière un reverse proxy

Si le proxy termine le HTTPS, ajoutez son adresse à `TRUSTED_PROXIES` (séparées par des virgules). Sans cela, Laravel voit des requêtes HTTP, les cookies `secure` sont ignorés et l'en-tête HSTS n'est pas envoyé. Le contrôle `APP_URL` reste obligatoire : les URL signées (retour de paiement, portail traiteur) doivent être générées en HTTPS.

## 3. Droits et dossiers

```bash
php artisan storage:link
chmod -R ug+rwx storage bootstrap/cache
```

Les photos restent sur le disque privé (`storage/app/private`) et sont servies par des URL signées ; aucun lien direct vers `storage/` n'est exposé.

## 4. Base de données

```bash
php artisan migrate --force
```

`--force` est nécessaire hors environnement local. Sauvegardez la base avant chaque migration.

## 5. Caches de production

```bash
php artisan optimize
```

Cette commande met en cache la configuration, les routes et les vues. Après chaque modification de `.env` ou déploiement :

```bash
php artisan optimize:clear
php artisan optimize
```

## 6. File d'attente

Les reçus RSVP, les relances et les dérivés photo passent par la file `database`. Ajoutez un cron (ou un superviseur) :

```cron
* * * * * cd /home/utilisateur/hub-evenement && php artisan queue:work --stop-when-empty --max-time=50 >> storage/logs/queue.log 2>&1
```

Un seul worker suffit : les e-mails d'un lot de relances partent l'un après l'autre.

## 7. Planificateur

Le cron du serveur appelle le planificateur chaque minute :

```cron
* * * * * cd /home/utilisateur/hub-evenement && php artisan schedule:run >> /dev/null 2>&1
```

Tâches planifiées :

| Commande | Fréquence | Rôle |
|---|---|---|
| `contributions:expire` | toutes les 5 minutes | Libère les paiements en attente après deux heures |
| `guests:purge` | chaque jour à 03:30 | Anonymise les données invités des événements terminés |

## 8. Rétention des données invités

`GUEST_DATA_RETENTION_DAYS` (90 par défaut) définit le délai après la date de l'événement au-delà duquel les données personnelles sont effacées :

- suppression des foyers (noms, e-mails, téléphones, jetons d'invitation), de leurs relances et de leurs messages du livre d'or ;
- anonymisation des contributions (`guest_id`, nom, téléphone, message et payload effacés), montants et statuts conservés ;
- l'événement, le programme, les photos et les cadeaux restent intacts.

Simulez avant d'appliquer :

```bash
php artisan guests:purge --dry-run
php artisan guests:purge --days=30
```

La commande est idempotente et journalise chaque événement traité (`guest-data.purged`).

## 9. Sauvegardes

Sauvegardez quotidiennement la base **et** les fichiers privés, puis conservez 30 jours.

```bash
#!/bin/sh
set -eu
BACKUP=/home/utilisateur/backups
mkdir -p "$BACKUP"
STAMP=$(date +%Y%m%d-%H%M)

mysqldump --single-transaction --routines --triggers \
  -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  | gzip > "$BACKUP/db-$STAMP.sql.gz"

tar -czf "$BACKUP/storage-$STAMP.tar.gz" \
  -C /home/utilisateur/hub-evenement storage/app/private

cp /home/utilisateur/hub-evenement/.env "$BACKUP/env-$STAMP.bak"

find "$BACKUP" -type f -mtime +30 -delete
```

Planifiez ce script une fois par nuit et testez une restauration au moins une fois avant l'ouverture.

### Restauration

```bash
gunzip < backups/db-20260101-0300.sql.gz | mysql -h "$DB_HOST" -u "$DB_USERNAME" -p "$DB_DATABASE"
tar -xzf backups/storage-20260101-0300.tar.gz -C /home/utilisateur/hub-evenement
php artisan optimize:clear
```

Le fichier `.env` sauvegardé contient des secrets : conservez-le hors du web et avec des droits restreints.

## 10. Retour arrière

1. Mettez le site en maintenance : `php artisan down --secret="jeton"`.
2. Restaurez le code : `git checkout <commit-précédent>` puis `composer install --no-dev --optimize-autoloader`.
3. Restaurez la base et les fichiers si la migration est irréversible.
4. Recompilez les assets si le commit précédent en diffère.
5. `php artisan optimize:clear && php artisan optimize`.
6. Sortez du mode maintenance : `php artisan up`.

## 11. Contrôle après déploiement

- [ ] `php artisan app:check-production` sort en succès.
- [ ] `https://votre-domaine.example` répond en HTTPS, sans avertissement de certificat.
- [ ] Une page inconnue affiche la page 404 personnalisée, sans trace technique.
- [ ] L'inscription, la connexion et la déconnexion fonctionnent.
- [ ] La création d'un événement, l'ajout d'un foyer, l'import CSV et l'invitation d'un co-organisateur fonctionnent sur mobile.
- [ ] Un e-mail de test arrive (reçu RSVP ou `--mail`).
- [ ] Le cron du planificateur et le worker de file tournent (`storage/logs`).
- [ ] Le paiement MoneyFusion live est testé avec un petit montant, puis remboursé.
- [ ] Les sauvegardes de la nuit existent et la restauration a été testée.
- [ ] `storage/logs/laravel-AAAA-MM-JJ.log` reçoit les erreurs, sans donnée personnelle en clair.

## 12. Supervision

- Surveillez la disponibilité de la page d'accueil et de `/login`.
- Surveillez l'espace disque (`storage/app/private`, `storage/logs`).
- Surveillez les journaux quotidiens (`LOG_DAILY_DAYS=14`) : erreurs 500, `payment.unverified`, `auth.failed` en rafale.
- Vérifiez chaque semaine que la file ne s'accumule pas (`php artisan queue:monitor database:default`).
- Renouvelez le certificat HTTPS avant expiration (Let's Encrypt ou fournisseur).
