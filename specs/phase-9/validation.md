# Validation — Phase 9

## Tests automatisés (Pest)

### Cadeaux et cagnotte

- Ajout d'un cadeau (nom, description, prix, quantité, visibilité), modification et masquage ; un prix sous 500 FCFA est refusé.
- Gestion réservée au propriétaire et aux co-organisateurs ; un tiers reçoit 403 ; un cadeau d'un autre événement renvoie 404.
- Configuration de la cagnotte (activation, objectif, message) ; un objectif sous 1 000 FCFA est refusé.
- Le lien « Cadeaux et cagnotte » apparaît sur l'invitation dès qu'une cagnotte est ouverte ou qu'un cadeau actif existe, et disparaît sinon.
- La page invitée affiche la cagnotte, les cadeaux actifs et masque les cadeaux inactifs ; un jeton inconnu renvoie 404.

### Contributions

- Une réservation calcule le montant côté serveur : un montant falsifié dans la requête est ignoré.
- Une participation libre est enregistrée avec le montant saisi ; sous 500 FCFA ou au-delà du disponible, elle est refusée.
- Les paiements en attente non expirés retiennent le disponible ; une réservation au-delà du disponible restant est refusée.
- Une contribution à la cagnotte est acceptée entre 500 et 2 000 000 FCFA ; une cagnotte fermée renvoie 404.
- Un cadeau masqué ne peut plus recevoir de contribution (404) ; un téléphone invalide est refusé.
- Quand aucun exemplaire entier ne reste mais qu'il reste un montant à financer, seule la participation libre est proposée (radio « Réserver » absente, « Participer librement » cochée par défaut).
- Le montant libre et la progression restent échappés ; les textes libres sont échappés à l'affichage.

### Paiement et fournisseur

- En mode `http`, la création appelle MoneyFusion avec le montant serveur, le téléphone, le libellé et les URLs signées de retour et de webhook ; la contribution reste `pending` avec token et URL fournisseur.
- Un refus du fournisseur marque la contribution `failed`, sans token, et renvoie l'invité vers l'ancre du cadeau avec une erreur.
- En mode `fake`, la passerelle redirige vers la page de simulation locale.
- Le simulateur paie ou échoue une contribution en local ; il renvoie 404 en mode `http`.

### Retour et webhook

- La page de retour signée affiche le statut, le montant, la destination, la référence et la date de confirmation ; une signature absente ou expirée renvoie 403.
- Un webhook sans signature renvoie 403 ; signé et vérifié `paid`, il confirme la contribution ; rejoué, il ne change rien (idempotence).
- Un webhook vérifié `failure` marque la contribution `failed` ; un statut inconnu la laisse `pending`.
- Un montant différent de celui attendu n'est jamais crédité ; une vérification indisponible renvoie 503 et laisse la contribution `pending`.

### Journal, expiration et intégrations

- Le journal et les totaux affichent confirmé, en attente, remboursé et la progression « Financé » de chaque cadeau.
- Encaissement manuel d'une contribution en attente, échec manuel, remboursement avec note obligatoire ; les transitions impossibles sont refusées (422).
- Un tiers reçoit 403 ; une contribution d'un autre événement renvoie 404.
- La commande `contributions:expire` passe les contributions en attente échues à `expired` sans toucher aux payées ni aux remboursées.
- Les paiements en attente sont comptés sur le détail de l'événement (« Cadeaux et cagnotte (n) ») et le tableau de bord (« n paiement(s) en attente »).
- Les contributions payées apparaissent côté invité, anonymisées le cas échéant ; les contributions en attente ou échouées restent invisibles.

## Parcours navigateur

Événement de démonstration « Mariage QR 2026 », foyer « Famille Bamba », mode `fake`, Chromium headless 1280 × 900 puis 390 × 844.

1. Registre vide, journal vide, page organisateur non indexable.
2. Cagnotte activée (objectif 500 000 FCFA, message d'accueil) puis deux cadeaux ajoutés : « Service de table » (25 000 × 2) et « Machine à coudre » (120 000 × 1).
3. Invitation : bouton « Cadeaux et cagnotte » ; page invitée avec avertissement de simulation, jauge de cagnotte et cadeaux.
4. Réservation d'un service de table depuis l'invité : simulateur à 25 000 FCFA, paiement réussi, page de retour « Merci, votre participation est confirmée. ».
5. Retour aux cadeaux : progression 25 000 / 50 000, disponible 25 000, contribution nominative visible.
6. Cagnotte de 10 000 FCFA anonyme : simulateur, confirmation, « Invité anonyme » et message affichés.
7. Participation libre de 5 000 FCFA à la machine à coudre, échec simulé : page de retour « Le paiement n'a pas abouti. » et statut « Échoué ».
8. Réservation abandonnée sur le simulateur : contribution en attente.
9. Tableau de bord « 1 paiement en attente » et événement « Cadeaux et cagnotte (1) ».
10. Journal : quatre lignes (payé, payé, échoué, en attente).
11. Remboursement de la réservation payée avec note : statut « Remboursé », note conservée.
12. Encaissement hors ligne de la participation échouée avec note : statut « Payé ».
13. Échec manuel de la réservation en attente : plus aucune ligne en attente.
14. Totaux : confirmé 15 000 FCFA, en attente 0 FCFA, remboursé 25 000 FCFA ; le service de table redevient disponible (50 000 FCFA) ; la machine affiche 5 000 / 120 000 et 115 000 FCFA disponibles.
15. Badge « paiement en attente » retiré du tableau de bord.
16. Mobile 390 px : contributions et formulaires lisibles, aucun débordement (0 px), aucune erreur JavaScript.

Captures : `34-registre-organisateur.png`, `35-cadeaux-invite.png`, `36-simulateur.png`, `37-paiement-confirme.png`, `38-journal-avant-actions.png`, `39-journal-paiements.png`, `40-cadeaux-mobile.png`.

## Vérifications manuelles

```bash
docker compose exec app php artisan migrate
docker compose exec app ./vendor/bin/pest
docker compose exec app ./vendor/bin/pint --test
npm run build
docker compose exec app php artisan route:list --path=paiement
```

- Vérifier que les routes du paquet MoneyFusion restent absentes (`MONEYFUSION_ROUTES_ENABLED=false`).
- Lancer `php artisan schedule:work` et vérifier l'exécution de `contributions:expire`.
- Contrôler le rendu d'un journal volumineux sur mobile (défilement horizontal du tableau).

## Résultats du 28 octobre 2026

### Automatisé

- Pest : **186 tests, 808 assertions**, tous verts (SQLite `:memory:`), dont **46 tests dédiés aux cadeaux et aux paiements** (159 assertions).
- Pint : **111 fichiers** conformes.
- Vite : build réussi (`app.css` 60,74 kB, `app.js` 53,88 kB, `scanner.js` 339,19 kB).
- Migrations appliquées sur MySQL 8.4 : colonnes de cagnotte sur `events`, tables `gifts`, `contributions` et table du paquet MoneyFusion.
- Playwright (Chromium headless, 1280 × 900 puis 390 × 844), mode `fake`, parcours complet rejoué intégralement, sans erreur JavaScript.

### Environnement de validation

Le montage Windows (9p) ralentit fortement les requêtes du conteneur. Le parcours navigateur a été exécuté sur le service `app` avec des attentes de 90 s ; toutes les étapes ont abouti. Le simulateur local remplace MoneyFusion : aucun paiement réel n'a été créé.

### Corrections pendant la validation

- **Assertion de test** : après un refus du fournisseur, le contrôleur redirige volontairement vers le fragment `#cadeau-{id}` ; le test attendait l'URL sans fragment.
- **Progression organisateur** : la barre « Financé » des cadeaux n'avait pas de libellé, ce qui laissait la valeur à gauche dans un espace vide. Le libellé « Financé » a été ajouté, comme côté invité.
- **Réservation impossible** : lorsqu'un cadeau n'avait plus d'exemplaire entier disponible mais gardait un montant à financer, le formulaire proposait encore « Réserver » sans champ quantité, ce qui menait à une erreur de validation. Seule « Participer librement » est désormais proposée et cochée par défaut ; un test de non-régression couvre ce cas.

### Reste manuel

- Tester avec de vraies clés MoneyFusion (sandbox puis production) et un webhook HTTPS public (ngrok ou domaine de recette) : création, paiement réussi, échec, renvoi de notification et montant incohérent.
- Vérifier un paiement depuis un vrai téléphone mobile money ivoirien.
- Activer le cron `schedule:run` en production et contrôler l'expiration après deux heures.
- Valider la politique de remboursement avec l'organisateur (le remboursement MoneyFusion reste externe à l'application).
