# Phase 9 — Cadeaux, cagnotte et MoneyFusion

## Objectif

Les invités participent aux cadeaux ou à la cagnotte depuis leur lien personnel, par mobile money via MoneyFusion. Les montants sont décidés côté serveur, le paiement est créé en attente, redirigé vers MoneyFusion, puis confirmé par un webhook signé et une vérification serveur. L'organisateur suit les cadeaux, l'objectif de la cagnotte et un journal des paiements (échecs, expirations, remboursements, rapprochement manuel).

## Décisions de cadrage

- **Réserver ou participer** : un invité peut réserver un cadeau (prix × quantité) ou participer d'un montant libre. Les deux passent par MoneyFusion et ne comptent qu'après confirmation du paiement.
- **Page dédiée** : cadeaux et cagnotte vivent sur `/i/{jeton}/cadeaux`, accessible depuis un bouton de l'invitation. L'invitation ne porte pas les formulaires.
- **Client réel + simulateur** : l'intégration utilise le paquet officiel `simonet85/laravel-moneyfusion` (création, vérification serveur, contrôle du montant). Un mode `fake` local reproduit la page de paiement pour valider le parcours sans clés sandbox. Le mode `http` appelle réellement MoneyFusion.
- **Journal manuel** : les remboursements et les encaissements hors ligne sont enregistrés par l'organisateur dans le journal. Aucun appel à l'API payout n'est fait.
- **Webhook signé applicatif** : MoneyFusion ne signe pas ses notifications. Notre URL de webhook est signée (URL temporaire Laravel), le contenu n'est jamais cru, et le statut réel est relu auprès de MoneyFusion avant toute modification.
- Montants en francs CFA entiers (XOF), sans décimales. Aucun montant fourni par le navigateur n'est accepté pour une réservation : il est recalculé côté serveur.
- Recherche et actions réservées au propriétaire et aux co-organisateurs (`manageRegistry`), comme la galerie et le livre d'or.

## User stories

- En tant qu'organisateur, je configure une cagnotte (objectif, message d'accueil) ou je la désactive.
- En tant qu'organisateur, j'ajoute, modifie et masque des cadeaux (nom, description, prix, quantité).
- En tant qu'invité, je vois l'objectif de la cagnotte, les montants suggérés et je contribue librement, avec un message et le choix de rester anonyme.
- En tant qu'invité, je réserve un cadeau ou participe partiellement à son prix.
- En tant qu'invité, je suis redirigé vers MoneyFusion puis vers une page de retour qui confirme le paiement après vérification.
- En tant qu'organisateur, je vois les totaux confirmés, en attente et remboursés, et la progression de chaque cadeau.
- En tant qu'organisateur, je consulte le journal des paiements et je peux encaisser, marquer en échec ou rembourser manuellement avec une note.

## Règles

### Cadeaux

- Un cadeau appartient à un événement : nom (160), description facultative (500), prix unitaire entier ≥ 500 FCFA, quantité de 1 à 99, actif ou masqué.
- Un cadeau masqué n'apparaît pas côté invité et ne peut plus recevoir de contribution ; il reste visible dans le journal et les totaux organisateur.
- Le total d'un cadeau est `prix × quantité`. La progression ne compte que les paiements **payés** ; les paiements en attente non expirés sont retenus pour le calcul du disponible.
- Disponible = `total − payé − en attente non expiré`. Une contribution ne peut pas dépasser le disponible au moment de sa création.
- Réservation : quantité d'exemplaires entiers, montant calculé côté serveur (`prix × quantité`), dans la limite du disponible.
- Participation libre : montant entier entre 500 FCFA et le disponible.
- Toute contribution porte le foyer, le téléphone, un message facultatif (300) et le choix anonyme.

### Cagnotte

- Réglages sur l'événement : activée ou non, objectif entier facultatif (≥ 1 000 FCFA), message d'accueil facultatif (500).
- Montants suggérés fixes : 5 000, 10 000, 25 000 et 50 000 FCFA, plus un montant libre entre 500 et 2 000 000 FCFA.
- La progression rapporte les paiements payés confirmés à l'objectif. Sans objectif, seuls le total collecté et le nombre de contributions sont affichés.
- Les contributions à la cagnotte n'ont pas de plafond de disponible.

### Paiement

- Route de contribution : réservée aux invités munis de leur jeton ; limitation de débit 10 contributions par minute.
- Le téléphone est obligatoire (prérempli avec celui du foyer si connu) : 8 à 20 chiffres, espaces, `+`, `.`, `-` ou parenthèses.
- À la création : contribution `pending`, référence ULID publique, expiration à 2 heures, puis appel MoneyFusion. L'URL de retour et l'URL de webhook sont signées et passées au fournisseur.
- Si MoneyFusion refuse la création, la contribution passe `failed` avec le motif et l'invité revient au formulaire avec un message d'erreur.
- Le navigateur est redirigé vers la page de paiement MoneyFusion ; aucun montant n'est transmis depuis le client.
- **Retour** : page signée (`GET paiements/retour/{référence}`) valable 24 h. Si le paiement est encore en attente, le statut est revérifié côté serveur ; en cas d'indisponibilité, la page indique que la confirmation est en cours.
- **Webhook** : `POST paiements/webhook/{référence}` signé (7 jours), exempté de CSRF. Le corps n'est jamais cru : le statut est relu auprès de MoneyFusion. Une notification en double est un no-op. Une API injoignable renvoie 503 pour permettre le renvoi.
- Seul un statut vérifié `paid` avec un montant identique à la contribution confirme le paiement. `failure` ou `no paid` marque la contribution `failed`. Un statut inconnu reste `pending`.
- Un paiement confirmé ne revient jamais en arrière automatiquement.
- Expiration : une commande artisan `contributions:expire` passe les contributions `pending` échues à `expired` ; elle est planifiée toutes les cinq minutes.

### Journal et rapprochement

- Le journal de l'organisateur liste toutes les contributions (cadeau ou cagnotte), triées de la plus récente à la plus ancienne, avec référence, foyer, téléphone, montant, statut, token fournisseur et dates.
- Totaux : confirmé (payé − remboursé), en attente, remboursé.
- Rapprochement manuel : un membre peut marquer `payé` une contribution `pending`, `failed` ou `expired` (encaissement hors ligne), ou `échoué` une contribution en attente, avec note facultative.
- Remboursement : uniquement depuis `paid`, avec note obligatoire ; la contribution passe `refunded` et reste dans le journal.
- Toutes les transitions enregistrent l'auteur et la date ; les actions impossibles sont refusées (422).

### Confidentialité et affichage

- Côté invité, seules les contributions **payées** apparaissent dans la liste récente (nom du foyer, montant, message) ; une contribution anonyme affiche « Invité anonyme » et masque le nom.
- Le téléphone n'est jamais affiché côté invité.
- Les pages organisateur sont non indexables ; les pages invité restent `noindex`.
- Tous les textes libres (nom, description, message, note) sont échappés à l'affichage.

## Hors périmètre

- Envoi d'e-mails de reçu ou de relance de paiement.
- Paiement par carte saisi dans l'application (MoneyFusion gère sa page).
- Remboursement automatique via l'API payout MoneyFusion.
- Promesses de don sans paiement, paiement en plusieurs fois, prélèvements récurrents.
- Modification du montant après création, changement de cadeau pour une contribution existante.
- Tableau de bord global des paiements (Phase 10).
- Multi-devise : tout est en XOF.

## Critères d'acceptation

- Un cadeau actif et une cagnotte configurée sont visibles depuis le lien invité ; un cadeau masqué ne l'est pas.
- Une réservation calcule le montant côté serveur : un montant falsifié dans la requête est ignoré.
- Une participation libre est refusée sous 500 FCFA, au-delà du disponible, ou avec un téléphone invalide.
- Une contribution valide crée une ligne `pending` avec référence et token, puis redirige vers l'URL MoneyFusion.
- Un échec de création fournisseur marque la contribution `failed` et affiche une erreur.
- Le webhook sans signature renvoie 403 ; signé et vérifié `paid`, il confirme la contribution ; rejoué, il ne change rien.
- Un webhook vérifié `failure` marque la contribution `failed` ; une vérification injoignable renvoie 503 et laisse `pending`.
- La page de retour signée affiche le statut confirmé ; une signature absente ou expirée renvoie 403.
- Le simulateur local n'existe qu'en mode `fake` ; en mode `http`, il renvoie 404.
- L'organisateur peut encaisser une contribution en attente, la marquer en échec, ou rembourser une contribution payée avec note ; les totaux suivent.
- Un tiers reçoit 403 ; une contribution ou un cadeau d'un autre événement renvoie 404.
- La commande d'expiration ne touche ni les paiements payés ni les remboursés.
- Les tests Pest couvrent cadeaux, cagnotte, montants serveur, disponibilité, paiement, webhook, retour, simulateur, journal, expiration, autorisations et échappement.
