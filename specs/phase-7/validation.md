# Validation — Phase 7

## Tests automatisés (Pest)

### Dépôt
- Un foyer signe depuis son invitation : message créé à l'état en attente, rattaché à l'événement et au foyer, espaces de début et de fin retirés, redirection vers `#livre-dor` avec la confirmation.
- Nom vide ou trop long, message vide ou au-delà de 1 000 caractères : erreurs de validation, aucune écriture.
- Un jeton d'invitation inconnu renvoie 404.

### Anti-spam
- Un dépôt avec le champ leurre rempli affiche la même confirmation mais n'enregistre rien.
- Le sixième dépôt dans la même minute renvoie 429 ; les cinq premiers sont enregistrés.
- Le champ leurre est masqué et sans tabulation (vérifié dans le parcours navigateur).

### Affichage
- Seuls les messages approuvés apparaissent sur l'invitation, du plus récent au plus ancien ; les messages en attente, masqués et rejetés restent invisibles.
- Le formulaire est présent avec le nom du foyer prérempli et l'état vide affiché en l'absence de message publié.
- Le nom et le message sont échappés : `<script>` est rendu en texte, jamais exécuté.

### Modération
- Publier renseigne le statut, le modérateur et la date, puis rend le message visible ; masquer le retire de l'invitation ; rejeter le garde invisible ; un message masqué peut être republié.
- Un message d'un autre événement renvoie 404 sur les trois actions et conserve son état.
- Propriétaire et co-organisateur accèdent à la file et modèrent ; un tiers reçoit 403 ; un visiteur non connecté est redirigé vers la connexion.
- La file affiche sections, auteur, foyer, message, actions et `noindex, nofollow` ; l'état vide est affiché sans action.

### Compteurs
- Le détail de l'événement affiche « Livre d'or (2) » et le tableau de bord « 2 messages à modérer » ; les messages publiés ne sont pas comptés.

## Parcours navigateur

- Invitation du foyer « Famille Bamba » : section « Livre d'or », état vide, formulaire avec nom prérempli, champ leurre invisible, page non indexable.
- Dépôt d'un message multiligne : confirmation, message absent de l'invitation tant qu'il est en attente.
- Tableau de bord : pastille « 1 message à modérer » ; clic vers la file de modération.
- File : message, auteur et foyer, actions « Publier » et « Rejeter », `noindex`.
- Publication : confirmation, section « Publiés » ; le message apparaît sur l'invitation avec son auteur et sa date.
- Mobile 390 px : aucun débordement horizontal, rendu complet de la section.
- Masquage : confirmation, section « Masqués », message retiré de l'invitation ; republication : message de nouveau visible.
- Aucune erreur JavaScript.

## Vérifications manuelles

```bash
docker compose exec app php artisan migrate
docker compose exec app ./vendor/bin/pest
docker compose exec app ./vendor/bin/pint --test
npm run build
```

- Déposer six messages d'affilée et vérifier le refus 429 sur le sixième.
- Signer avec un message contenant du HTML et vérifier l'affichage littéral.
- Créer plus de dix messages publiés et vérifier la pagination du livre d'or sur l'invitation.
- Modérer depuis un compte co-organisateur et vérifier les métadonnées « Publié par … le … ».
- Vérifier la file de modération sur mobile réel.

## Résultats du 27 octobre 2026

### Automatisé

- Pest : **121 tests, 506 assertions**, tous verts (SQLite `:memory:`), dont 13 tests dédiés au livre d'or.
- Pint : **82 fichiers** conformes.
- Vite : build réussi (`app.css` 47,64 kB, `app.js` 53,88 kB, `scanner.js` 339,19 kB).
- Migration : `guestbook_entries` appliquée sur MySQL 8.4.
- Playwright (Chromium headless, 390 × 844 puis 1280 × 900), événement de démonstration « Mariage QR 2026 », foyer « Famille Bamba » :
  - Dépôt d'un message multiligne, confirmation et absence avant validation.
  - Compteur du tableau de bord, file de modération, publication, affichage sur l'invitation.
  - Masquage puis republication, avec vérification de l'invitation à chaque étape.
  - Mobile 390 px : débordement horizontal 0 px.
  - Captures : `26-livre-or-invitation.png`, `27-livre-or-moderation.png`, `28-livre-or-publie.png`, `29-livre-or-mobile.png`.

### Corrections pendant la validation

- **Directive Blade collée au texte** : `Livre d'or@if (...)` n'était pas compilé, car `@` suivait une lettre (limite de mot) ; la vue de l'événement rendait alors une erreur de syntaxe. Le libellé utilise désormais une expression ternaire. Les tests ont révélé l'erreur.
- **Doublon auteur/foyer** : lorsqu'un foyer signe avec son propre nom, la file affichait « Famille Bamba · Famille Bamba ». Le foyer n'est plus répété que si le nom saisi diffère, sous la forme « Awa Koné · foyer Famille Koné ».
- **Titre ambigu** : la section de l'invitation reprenait « Un mot pour les organisateurs », déjà utilisé par le champ RSVP ; elle s'intitule maintenant « Vos vœux et vos souvenirs ».

### Reste manuel

- Déposer un message depuis un téléphone réel, vérifier le clavier et le défilement jusqu'à la section.
- Vérifier la pagination au-delà de dix messages publiés.
- Tester un message avec emojis et caractères spéciaux, puis le relire sur l'invitation mobile.
