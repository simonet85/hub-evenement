# Phase 12 — Durcissement et mise en ligne

## Objectif

L'application peut être exposée sur un mutualisé sans mauvaise surprise : les tentatives d'authentification et les envois publics sont limités, les entrées sont bornées, les erreurs affichent une page française sans fuite technique, les réponses portent les en-têtes de sécurité attendus, les actions destructrices sont journalisées, les données invités sont supprimées après l'événement, et un contrôle de production vérifie HTTPS, SMTP, caches et stockage avant l'ouverture.

## Décisions de cadrage

- **Limites par IP** : les routes d'authentification, d'import, d'envoi de photos, de pointage, de simulateur de paiement et de webhook reçoivent un `throttle`. Les limites publiques existantes (RSVP, cadeaux, cagnotte, livre d'or) sont conservées.
- **Validation bornée** : les adresses et mots de passe ont une longueur maximale, les sélections de relance sont plafonnées et dédupliquées, les jetons d'invitation sont contraints à 48 caractères alphanumériques avant toute requête, et les champs d'état des contributions ne sont plus assignables en masse.
- **Pages d'erreur sobres** : 403, 404, 419, 429, 500 et 503 partagent une mise en page minimale (sans session ni Livewire), non indexable, avec un titre, une explication et un retour à l'accueil. La page 500 ne montre jamais le message de l'exception.
- **En-têtes de sécurité** : toutes les réponses web reçoivent `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: no-referrer` et une `Permissions-Policy` limitée à la caméra locale (pointage). `Strict-Transport-Security` n'est ajouté que sur une requête HTTPS.
- **Proxys de confiance** : `TRUSTED_PROXIES` permet de faire confiance au reverse proxy du mutualisé pour détecter HTTPS et l'IP réelle, sans faire confiance à tout le monde par défaut.
- **Journalisation** : les échecs de connexion, les webhooks non vérifiés, les suppressions d'événement et les purges de données sont journalisés sans contenu métier sensible (pas de nom, message, téléphone ni charge fournisseur). En production, la rotation quotidienne est recommandée avec 14 jours de rétention.
- **Suppression d'événement** : réservée au propriétaire, avec saisie du nom de l'événement pour confirmer. Les lignes sont supprimées en cascade et le dossier `events/{id}` du disque privé est effacé.
- **Rétention invités** : une commande planifiée supprime les foyers des événements terminés depuis N jours (90 par défaut, configurable par `GUEST_DATA_RETENTION_DAYS`). Le livre d'or et les relances disparaissent avec les foyers ; les contributions sont anonymisées (nom, téléphone, message et charge effacés) et les montants/statuts sont conservés. L'événement, le programme, les photos et les cadeaux restent.
- **Contrôle de production** : `php artisan app:check-production` vérifie l'environnement, le débogage, HTTPS, les cookies, le journal, la file, le cache, la base, le stockage et le manifeste Vite ; `--mail=` envoie un message de test SMTP.

## User stories

- En tant qu'organisateur, je supprime un événement après la fête et je sais que les foyers, messages et fichiers photo ne restent pas.
- En tant qu'invité, une erreur de lien ou une session expirée m'affiche une page claire, en français, qui ne révèle rien de technique.
- En tant qu'administrateur, je vérifie avant l'ouverture que la production est en HTTPS, sans débogage, avec SMTP et files configurés, et je reçois un e-mail de test.
- En tant que responsable des données, je dispose d'une purge planifiée et documentée des données invités après l'événement.

## Règles

### Limitation de débit

- `POST login`, `POST register`, `POST reset-password` : 5 requêtes par minute et par IP.
- `POST forgot-password` : 3 requêtes par minute et par IP.
- `POST events/{event}/guests/import` : 5 par minute ; `POST events/{event}/galerie` : 10 par minute.
- `POST events/{event}/scan` : 120 par minute ; `POST check-in/{entryToken}` : 60 par minute.
- `POST paiement-simule/{token}` : 10 par minute ; `POST paiements/webhook/{contribution}` : 120 par minute.
- Les limites existantes (RSVP 20/min, cadeaux et cagnotte 10/min, livre d'or 5/min, relances 10/min) restent inchangées.

### Validation stricte

- `email` : `max:255` sur connexion, inscription, mot de passe oublié et réinitialisation ; `password` : `max:255` sur connexion et réinitialisation.
- `guest_ids` : tableau de 200 identifiants au plus, entiers distincts.
- `co_email` : `max:255`.
- Les routes `i/{token}` n'acceptent qu'un jeton de 48 caractères alphanumériques.
- `payload` n'est plus dans `$fillable` de `Contribution` ; il reste écrit par `forceFill`.
- Aucune donnée personnelle n'est ajoutée aux journaux.

### Pages d'erreur

- 403 « Accès refusé », 404 « Page introuvable », 419 « Session expirée », 429 « Trop de tentatives », 500 « Une erreur est survenue », 503 « Service momentanément indisponible ».
- Mise en page autonome, `lang="fr"`, lien d'évitement, `main#contenu`, un seul `h1`, `noindex, nofollow`, `no-referrer`, `theme-color`.
- La page 500 ne contient ni trace, ni classe, ni message d'exception.

### En-têtes et cookies

- En-têtes globaux sur le groupe `web` : `nosniff`, `DENY`, `no-referrer`, `Permissions-Policy: camera=(self), microphone=(), geolocation=()`.
- `Strict-Transport-Security: max-age=31536000` uniquement si `$request->isSecure()`.
- En production : `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`, `APP_DEBUG=false`, `APP_URL` en HTTPS.

### Suppression et rétention

- `EventPolicy::delete` : propriétaire uniquement.
- `DELETE /events/{event}` exige `confirmation` égale au nom de l'événement ; sinon erreur de validation.
- La suppression efface le dossier `events/{event}/` du disque `local`, puis la ligne et ses dépendances (foyers, relances, livre d'or, photos, cadeaux, contributions, rôles, étapes).
- `guests:purge` accepte `--days=` et `--dry-run` ; sans option, `GUEST_DATA_RETENTION_DAYS` (90) s'applique.
- Seuls les événements dont `starts_at` est antérieur à la date limite sont traités.
- Foyers supprimés ; livre d'or et relances supprimés par cascade ; contributions anonymisées (nom `Invité anonymisé`, téléphone vide, message et charge nuls, foyer détaché) ; montants et statuts conservés.
- La commande est idempotente, journalise un résumé sans donnée personnelle et est planifiée chaque jour à 03:30.

### Mise en ligne

- `app:check-production` sort en échec si un contrôle bloquant échoue et affiche chaque point avec ✓ ou ✗.
- Contrôles : `APP_ENV=production`, `APP_DEBUG=false`, URL HTTPS, cookies sécurisés et chiffrés, niveau de journal non verbeux, clé applicative, mailer non `log`/`array`, file non `sync`, cache non `array`, connexion base, manifeste Vite, dossiers inscriptibles.
- `--mail=adresse` envoie un message de test et échoue proprement si le transport refuse.
- `docs/deployment.md` décrit prérequis, build, `.env`, permissions, migrations, caches, cron, file, HTTPS/proxy, SMTP, sauvegardes/restauration, rétention, contrôle après déploiement et retour arrière.
- `.env.production.example` ne contient aucun secret réel.

## Hors périmètre

- Pare-feu applicatif, CDN, WAF, CSP stricte et nonces.
- Double authentification, politique de mot de passe renforcée, chiffrement au repos.
- Sauvegarde automatique hors site : la procédure et les commandes sont documentées, l'hébergeur ou l'exploitant les planifie.
- Déploiement réel sur un domaine de production et test SMTP réel.
- Conseil juridique sur les durées de conservation.

## Critères d'acceptation

- La sixième tentative de connexion en une minute renvoie la page 429 française.
- Un jeton d'invitation mal formé renvoie 404 sans requête métier.
- Les pages 403, 404, 429 et 500 sont personnalisées ; la 500 ne divulgue pas l'exception.
- Toutes les réponses web portent les en-têtes de sécurité ; HSTS n'apparaît qu'en HTTPS.
- Le propriétaire supprime un événement avec confirmation ; un tiers ou un co-organisateur est refusé ; les fichiers et lignes associés disparaissent ; l'action est journalisée sans donnée personnelle.
- La purge supprime les foyers anciens, anonymise les contributions et laisse intacts les événements récents ; `--dry-run` ne modifie rien.
- `app:check-production` réussit sur une configuration conforme et échoue avec un message explicite sinon.
- Pint, Pest et le build Vite restent verts ; le parcours mobile invité ne régresse pas.
