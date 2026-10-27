# Validation — Phase 2

## Tests automatisés (Pest)

### Événements
- Un invité non connecté est redirigé vers la connexion.
- Création valide : redirection vers le détail, événement rattaché à l'utilisateur.
- Validation : nom obligatoire, type inconnu refusé, date limite postérieure à la date de début refusée.
- Le dashboard n'affiche que les événements dont l'utilisateur est propriétaire ou co-organisateur.
- Le propriétaire voit et modifie son événement ; le co-organisateur voit mais ne modifie pas ; un tiers reçoit 403.

### Foyers
- Ajout d'un foyer avec validation des champs et des places (1 à 20).
- Suppression d'un foyer ; suppression croisée entre deux événements refusée.
- Un co-organisateur peut ajouter un foyer ; un tiers reçoit 403.
- Import CSV : 2 foyers valides + 1 ligne invalide → 2 importés, 1 erreur signalée.
- Import avec séparateur point-virgule et en-tête.
- Fichier non CSV refusé.

### Co-organisateurs
- Ajout d'un utilisateur existant par e-mail.
- E-mail inconnu refusé ; ajout de soi-même refusé ; doublon refusé.
- Accès en lecture et gestion des foyers autorisés.
- Modification de l'événement et gestion des co-organisateurs interdites.
- Retrait d'un co-organisateur.

## Vérifications manuelles

```bash
docker compose exec app php artisan migrate
docker compose exec app ./vendor/bin/pest
docker compose exec app ./vendor/bin/pint --test
npm run build
```

- Créer un événement depuis le dashboard.
- Importer un CSV de 50 foyers et vérifier le compteur.
- Se connecter avec un second compte co-organisateur et vérifier les droits.

## Résultats — 27 octobre 2026

- Pest : 35 tests, 121 assertions, tous verts.
- Pint : 54 fichiers conformes.
- Migrations `events`, `event_roles` et `guests` appliquées sur MySQL.
- Build Vite réussi (`npm run build`).
- Parcours navigateur (Chromium, 1280 px et 390 px) : inscription, création d'un événement, ajout d'un foyer, import CSV (2 lignes valides, 1 invalide signalée), ajout d'un co-organisateur, tableau de bord partagé, ajout d'un foyer par le co-organisateur, édition refusée en 403, aucun débordement horizontal, aucune exception JavaScript.
- Accent du type et fond crème vérifiés dans les styles calculés du navigateur.
