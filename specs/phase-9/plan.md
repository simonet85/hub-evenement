# Plan — Phase 9

## Dépendance

`simonet85/laravel-moneyfusion` (^1.1) :

- `MoneyFusionService::createPayment()` prépare le payload documenté (`totalPrice`, `article`, `numeroSend`, `nomclient`, `return_url`, `webhook_url`), crée le paiement et conserve une ligne `moneyfusion_payments` (token, montant, statut, réponse brute).
- `MoneyFusionService::checkPaymentStatus($token)` interroge `paiementNotif/{token}`, met à jour le paiement local, refuse de créditer un montant différent et renvoie `verified: true` uniquement pour une vraie réponse API.
- Le paquet expose des routes (`/api/moneyfusion/*`, `/payment/*`) et un webhook non signé : **toutes ses routes sont désactivées**. L'application fournit son propre webhook signé, qui appelle la vérification du paquet.
- Config publiée dans `config/moneyfusion.php` ; `routes.enabled = false`, endpoints de paiement publics désactivés.

## Données

### `events` (ajouts)

| Colonne | Type | Notes |
|---|---|---|
| `cagnotte_enabled` | boolean | défaut `false` |
| `cagnotte_goal` | unsigned int nullable | objectif XOF |
| `cagnotte_message` | string(500) nullable | message d'accueil |

### `gifts`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `event_id` | FK events | cascade |
| `name` | string(160) | |
| `description` | string(500) nullable | |
| `price` | unsigned int | prix unitaire XOF ≥ 500 |
| `quantity` | unsigned smallint | 1 à 99 |
| `is_active` | boolean | défaut `true` |
| `created_at` / `updated_at` | timestamps | |

Index `(event_id, is_active)`.

### `contributions`

| Colonne | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `reference` | char(26) unique | ULID public |
| `event_id` | FK events | cascade |
| `guest_id` | FK guests nullable | `nullOnDelete` |
| `gift_id` | FK gifts nullable | `nullOnDelete` |
| `kind` | string(20) | `gift` ou `cagnotte` |
| `amount` | unsigned int | XOF |
| `quantity` | unsigned tinyint nullable | réservation |
| `contributor_name` | string(160) | instantané |
| `phone` | string(30) | |
| `message` | string(300) nullable | |
| `anonymous` | boolean | défaut `false` |
| `status` | string(20) | `pending` par défaut |
| `provider` | string(20) | `moneyfusion` |
| `provider_token` | string(190) nullable unique | token MoneyFusion |
| `provider_url` | string(500) nullable | page de paiement |
| `expires_at` | timestamp nullable | +2 h |
| `paid_at` / `failed_at` | timestamp nullable | |
| `refunded_at` | timestamp nullable | |
| `refund_note` | string(255) nullable | |
| `refunded_by` | FK users nullable | `nullOnDelete` |
| `reconciled_by` | FK users nullable | `nullOnDelete` |
| `reconciled_at` | timestamp nullable | |
| `reconciliation_note` | string(255) nullable | |
| `payload` | json nullable | dernière vérification API |
| `created_at` / `updated_at` | timestamps | |

Index `(event_id, status)`, `(guest_id)`, `(gift_id)`.

## Modèles et énumérations

- `ContributionStatus` : `Pending` (warning), `Paid` (success), `Failed` (danger), `Expired` (neutral), `Refunded` (neutral) avec `label()`, `tone()`, `isTerminal()`, `countsAsPaid()`.
- `ContributionKind` : `Gift` (« Cadeau »), `Cagnotte`.
- `Gift` : `event()`, `contributions()` ; `totalPrice()`, `paidTotal()`, `pendingTotal()`, `availableAmount()`, `progressPercent()`, `isFunded()`, `availableUnits()`.
- `Contribution` : `event()`, `guest()`, `gift()`, `refunder()`, `reconciler()` ; casts statut/type/dates/booléens ; `getRouteKeyName()` = `reference` ; scopes `paid()`, `pendingActive()`, `refunded()` ; `isCagnotte()` ; `label()` (cadeau ou cagnotte).
- `Event` : `gifts()`, `contributions()`, `hasRegistry()` ; colonnes cagnotte fillable/castées.
- `Guest` : `contributions()`.
- `User` : `reconciledContributions()`, `refundedContributions()`.
- Factories `GiftFactory`, `ContributionFactory`.

## Paiement

### Interfaces et implémentations

- `App\Services\MoneyFusion\PaymentGateway` : `create(Contribution, string $returnUrl, string $webhookUrl): PaymentSession`, `status(Contribution): PaymentResult`.
- `PaymentSession` (token, url), `PaymentResult` (status string normalisé, verified bool, amount int|null, raw array).
- `PackageMoneyFusionGateway` : enveloppe `MoneyFusionService` du paquet ; mappe `paid|failed|cancelled|pending` ; renvoie `verified` tel quel.
- `FakeMoneyFusionGateway` : token `sim_…`, URL vers la page de simulation ; `status()` renvoie le statut local de la contribution.
- Liaison dans `AppServiceProvider` selon `config('services.moneyfusion.driver')` (`http` ou `fake`).
- `config/services.php` : `moneyfusion.driver` (défaut `fake` en local, `http` documenté en production), `return_ttl_hours`, `webhook_ttl_days`.

### Service métier

`ContributionService` :

1. `startCagnotte(Guest, amount, phone, message, anonymous)`.
2. `startGift(Guest, Gift, mode, amount, quantity, phone, message, anonymous)` : transaction + verrou sur le cadeau, recalcul du disponible (payé + en attente non expiré), calcul du montant, création, puis appel fournisseur.
3. `providerPayload()` : appel `create`, enregistre token/url ; en cas d'échec, statut `failed`, motif journalisé.
4. `applyStatus(Contribution, PaymentResult)` : transaction + verrou, idempotent, contrôle `verified` et montant, transitions `pending → paid|failed` sans retour arrière.
5. `reconcilePaid|reconcileFailed|refund` : transitions manuelles avec auteur, date, note.

### Parcours

1. Formulaire invité → `ContributionService` crée `pending` et appelle MoneyFusion.
2. Redirection externe vers `provider_url`.
3. Retour signé ou webhook signé.
4. Vérification serveur → `applyStatus`.
5. Page de retour : confirmation, montant, lien vers l'invitation.

### Mode simulateur

- `GET /paiement-simule/{token}` : page locale (404 si driver ≠ `fake`) affichant le montant et les boutons « Payer », « Échouer ».
- `POST /paiement-simule/{token}` : applique `paid` ou `failed` via `ContributionService`, puis redirige vers la page de retour signée.
- Permet la validation navigateur sans clés sandbox ; ne crée pas de ligne `moneyfusion_payments`.

## Routes

| Méthode | URI | Nom | Accès |
|---|---|---|---|
| GET | `i/{token}/cadeaux` | `invitations.registry` | jeton invité |
| POST | `i/{token}/cagnotte` | `contributions.cagnotte` | jeton + throttle 10/min |
| POST | `i/{token}/cadeaux/{gift}` | `contributions.gift` | jeton + throttle 10/min |
| GET | `paiements/retour/{contribution}` | `payments.return` | `signed` |
| POST | `paiements/webhook/{contribution}` | `payments.webhook` | `signed`, sans CSRF |
| GET/POST | `paiement-simule/{token}` | `payments.simulator` / `payments.simulator.complete` | driver `fake` |
| GET | `events/{event}/cadeaux` | `registry.index` | auth + `manageRegistry` |
| POST | `events/{event}/cadeaux` | `gifts.store` | auth + `manageRegistry` |
| PUT | `events/{event}/cadeaux/{gift}` | `gifts.update` | auth + `manageRegistry` |
| PUT | `events/{event}/cagnotte` | `cagnotte.update` | auth + `manageRegistry` |
| POST | `events/{event}/contributions/{contribution}/journal` | `contributions.reconcile` | auth + `manageRegistry` |

`manageRegistry` = propriétaire ou co-organisateur. Contrôle systématique `event_id` (404) et `gift->event_id` (404).

## Vues

### `invitations/show.blade.php`

- Bouton « Cadeaux et cagnotte » si l'événement a une cagnotte activée ou un cadeau actif.

### `registry/show.blade.php` (invité)

- En-tête et retour à l'invitation.
- Carte cagnotte : objectif, collecté, progression, message d'accueil, formulaire (montants suggérés + libre, message, anonymat, téléphone).
- Liste des cadeaux actifs : prix unitaire, quantité, progression, disponible ; formulaire par cadeau (mode réserver/participer, quantité ou montant, message, anonymat, téléphone).
- Liste récente des contributions payées (nom ou anonyme, montant, message).
- États vides et `noindex`.

### `registry/index.blade.php` (organisation)

- Fil d'Ariane, titre, retour à l'événement.
- Totaux : confirmé, en attente, remboursé.
- Réglages cagnotte (case activée, objectif, message).
- Formulaire d'ajout de cadeau ; cartes cadeaux avec édition (nom, description, prix, quantité, actif) et progression.
- Journal : lignes avec statut, type, foyer, téléphone, montant, token, dates ; actions Encaisser / Marquer échoué / Rembourser avec note.
- `noindex`, sans JavaScript.

### `payments/return.blade.php`

- Statut (payé, en attente, échoué), montant, destination (cadeau/cagnotte), référence, lien de retour à l'invitation.
- Re-vérification serveur si `pending` (mode `http`).

### `payments/simulator.blade.php`

- Page locale neutre : montant, nom, boutons Payer / Échouer ; avertissement « simulation ».

### Intégrations organisateur

- `events/show.blade.php` : bouton « Cadeaux et cagnotte » avec le nombre de paiements en attente.
- `dashboard.blade.php` : pastille « N paiement(s) en attente » par événement.

## Commande

`contributions:expire` : passe à `expired` les contributions `pending` dont `expires_at <= now()`. Planification `everyFiveMinutes()` dans `routes/console.php`.

## Cas limites

- Paiement créé mais abandonné : expire au bout de 2 h ; le disponible du cadeau est restitué.
- Deux invités réservent le dernier exemplaire en même temps : le verrou du cadeau sérialise les créations ; le second est refusé.
- Paiement confirmé après expiration : `expired → paid` accepté (l'argent est arrivé), y compris via webhook.
- Webhook rejoué : aucun changement, réponse 200.
- API injoignable : webhook 503, retour affiche « confirmation en cours ».
- Montant API différent : pas de crédit, journalisé, contribution laissée en attente.
- Cadeau masqué pendant un paiement en cours : le paiement reste suivi dans le journal ; aucune nouvelle contribution n'est acceptée.
- Foyer supprimé : la contribution reste (guest_id null) et le nom instantané est conservé.
- Driver `fake` en production : documenté comme interdit ; la page simulateur n'existe qu'en `fake`, et la config par défaut de `.env.example` est locale.

## Tests

- Cadeaux : création, validation, édition, masquage, 403 tiers, 404 autre événement.
- Cagnotte : réglages, validation objectif/message.
- Page invitée : bouton invitation, cadeaux actifs seulement, cagnotte désactivée, états vides, liste des contributions payées et anonymat.
- Contributions : réservation (montant serveur, quantité), participation libre, limites (min, disponible incluant en attente), téléphone, cadeau masqué, échec fournisseur, redirection externe.
- Paiement : création `pending` avec référence/token, webhook non signé 403, signé et vérifié payé, idempotence, échec, montant différent, API injoignable 503, retour signé, signature expirée, simulateur `fake` uniquement.
- Journal : totaux, encaissement manuel, échec manuel, remboursement avec note obligatoire, transitions interdites, autorisations.
- Expiration : commande, planification, paiements payés intacts.
- Échappement : nom de cadeau et message.
- Modèles : progression et disponible.

## Ordre d'implémentation

1. Config paquet, variables d'environnement, migrations, enums, modèles, factories.
2. Gateway et service paiement, simulateur.
3. Contrôleurs, policy, routes, commande d'expiration.
4. Vues et intégrations.
5. Tests Pest, Pint, build.
6. Parcours navigateur (simulateur), documentation et validation.
