# Validation — Phase 11

## Environnement

- Application de développement Docker (PHP 8.3, MySQL 8.4) servie sur `http://localhost:8000`.
- Chromium headless (Playwright 1243) en 1280 × 900, 1280 × 900 avec `prefers-reduced-motion`, 390 × 300 et 390 × 844 mobile tactile.
- Événement de démonstration « Mariage QR 2026 » (n° 68) : coordonnées, deux étapes de programme, une photo publiée, cagnotte et deux cadeaux.
- Invité dédié « Famille Test Clavier » (2 places), utilisé pour le RSVP, le livre d'or et le parcours clavier.
- Le montage Windows (9p) ralentit les requêtes du conteneur : le parcours navigateur utilise des attentes de 90 s et bloque les polices Google et les tuiles OpenStreetMap pour rester déterministe.

## Tests automatisés (Pest)

`tests/Feature/AccessibilityTest.php` — 8 tests, 57 assertions :

- Les deux mises en page exposent `lang="fr"`, le lien d'évitement vers `#contenu`, `main#contenu` et `theme-color`.
- L'invitation marque au moins quatre sections `data-reveal` et n'en révèle aucune côté serveur.
- Le programme marque la carte en `role="region"` décrite par `carte-aide`, sans `role="application"`, et conserve les liens d'itinéraire ; trois sections sont marquées.
- Un RSVP hors limite relie l'erreur au champ : `aria-invalid`, `aria-describedby` vers l'aide et l'erreur, message `role="alert"`.
- Un livre d'or vide relie les erreurs du nom et du message, avec `autocomplete="name"`.
- La galerie sert les photos en `loading="lazy"` / `decoding="async"`, avec alternative textuelle et mention « nouvelle fenêtre ».
- Le registre prépare la saisie mobile : `autocomplete="tel"`, `inputmode="tel"` et `inputmode="numeric"`.
- CSS et JavaScript contiennent les règles `reveal-enabled`, `prefers-reduced-motion`, impression, `focusin` et `IntersectionObserver`.

## Parcours navigateur

1. **Révélation au scroll** : `reveal-enabled` posé, 5 sections marquées, aucune révélée au premier écran, toutes à l'opacité 1 après défilement ; axe-core sans violation critique ou sérieuse sur l'invitation.
2. **Mouvement réduit** : aucune classe `reveal-enabled`, toutes les sections visibles sans défiler.
3. **Sans JavaScript** : invitation, livre d'or et programme entièrement lisibles.
4. **Parcours clavier de l'invitation** : le lien d'évitement reçoit le premier focus avec un contour de 2 px ; Entrée amène le focus sur `#contenu` ; le lien programme est le premier lien du contenu ; les radios se choisissent avec Espace et les flèches ; les places se règlent au clavier ; prénoms, régime et message sont saisis ; le bouton « Enregistrer ma réponse » soumet ; le statut « Merci ! Votre présence pour 2 personne(s) est enregistrée. » est annoncé et la réponse est bien affichée.
5. **Livre d'or au clavier** : signature depuis le formulaire, statut « Merci ! Votre message sera publié après validation par les organisateurs. » annoncé.
6. **Programme et carte au clavier** : ouverture du programme par Entrée, 3 sections marquées, axe-core sans violation, Leaflet initialisé à l'approche du viewport, carte en région décrite et atteignable au clavier.
7. **Galerie et cadeaux au clavier** : ouverture des deux pages par Entrée, photo `loading="lazy"` / `decoding="async"` avec alternative et nouvelle fenêtre annoncée, téléphone et montants préparés pour le clavier mobile, axe-core sans violation sur les cadeaux.
8. **Chargement différé de la carte** : en 390 × 300, la carte est à 863 px pour un seuil de 500 px ; Leaflet n'est pas chargé avant le défilement, puis se charge après approche.
9. **Affichage mobile 390 × 844** : aucun débordement horizontal sur l'invitation, le programme, la galerie et les cadeaux (0 px) ; tous les boutons mesurent au moins 44 px ; aucune erreur JavaScript.

Captures : `46-invitation-reveal.png`, `47-invitation-clavier.png`, `48-invitation-mobile.png`, `49-programme-mobile.png`.

## Vérifications manuelles

```bash
docker compose exec app ./vendor/bin/pint --test
docker compose exec app ./vendor/bin/pest
npm run build
```

Parcours recommandé dans un navigateur réel :

- Activer puis désactiver « Réduire les animations » dans le système pendant une visite : le contenu doit apparaître immédiatement et la classe `reveal-enabled` disparaître.
- Imprimer l'invitation et le programme : toutes les sections doivent être visibles.
- Parcourir la carte au clavier (flèches, `+` et `−`) et vérifier les liens d'itinéraire.
- Tester sur un smartphone réel la taille des cibles, le défilement et le clavier numérique des montants.

## Résultats du 28 octobre 2026

### Automatisé

- Pest : **221 tests, 1 029 assertions**, tous verts (SQLite `:memory:`), dont **8 tests dédiés à l'accessibilité**.
- Pint : **119 fichiers** conformes.
- Vite : build réussi en 3,03 s (`app.css` 62,04 kB — gzip 11,72 ; `app.js` 54,16 kB — gzip 20,69 ; `reveal.js` 0,75 kB ; `program-map.js` 0,76 kB ; `leaflet.js` 149,51 kB ; `scanner.js` 339,19 kB).
- Playwright (Chromium headless) : parcours complet rejoué intégralement, code de sortie 0, aucune erreur JavaScript, aucune violation axe-core critique ou sérieuse sur l'invitation, le programme et les cadeaux.

### Corrections pendant la validation

- **Attente de chargement du programme** : le test attendait l'événement `load`, que les tuiles OpenStreetMap injectées par Leaflet retardaient au-delà de 90 s. Les navigations au clavier attendent désormais `domcontentloaded` et les tuiles distantes sont bloquées.
- **Prédicat de focus trop large** : après le livre d'or, le focus repartait de la section `#livre-dor` et le parcours tabulait jusqu'à `<body>`, dont le texte contient « Programme et carte » ; Entrée sur le corps ne déclenchait rien. Les recherches de focus exigent désormais un lien (`A`).
- **Captures mobiles** : la capture du programme était prise avant la révélation des sections, laissant un vide à la place de la carte. Le test défile jusqu'en bas, attend Leaflet puis capture. La capture clavier montre maintenant le contour de focus sur le lien programme.
- **Attributs ARIA** : les directives Blade `@error` dans `aria-describedby` produisaient des espaces doubles et un identifiant fantôme ; les valeurs sont construites avec une condition explicite.

### Reste manuel

- Passage avec un lecteur d'écran réel (NVDA, VoiceOver) sur le RSVP, le livre d'or et la carte.
- Vérification tactile sur iOS Safari et Android Chrome, y compris la saisie des montants.
- Contrôle de l'impression sur papier et du rendu avec les polices chargées (les polices Google sont bloquées pendant les tests).
- Validation éditoriale et technique de l'usage des tuiles OpenStreetMap en production.
