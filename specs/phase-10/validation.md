# Validation — Phase 10

## Tests automatisés (Pest)

### Métriques de pilotage

- La synthèse renvoie des valeurs exactes : foyers, places prévues, réponses, présents, absents, en attente, couverts confirmés, foyers avec régime et réponses hors délai.
- Un foyer présent sans nombre de couverts précisé retombe sur ses places autorisées, dans `confirmedSeats()` comme dans la synthèse.
- L'évolution sur 14 jours compte les réponses par date, cumule celles antérieures à la fenêtre et n'additionne dans le total que les réponses de la période (max, libellés et pourcentages inclus).

### Accès et navigation

- Le pilotage est accessible au propriétaire et aux co-organisateurs, refuse un tiers (403) et redirige un visiteur non connecté vers la connexion.
- Le bouton « Pilotage et exports » apparaît sur la page de l'événement.
- La page de pilotage est marquée `noindex, nofollow`.

### Exports organisateur

- CSV invités : BOM UTF-8, séparateur `;`, en-tête complet, coordonnées présentes, régime contenant un `;` correctement entretenu par des guillemets.
- Une cellule commençant par `=` est neutralisée par une apostrophe (injection de formule).
- XLSX invités : deux feuilles nommées, contenu relu par OpenSpout, ligne retrouvée par foyer.
- PDF organisateur : signature `%PDF`.
- Les exports sont refusés à un tiers (403) et autorisés aux co-organisateurs.

### Lien traiteur et portail

- Génération d'un lien signé pour 1, 7 ou 30 jours, avec expiration exacte contrôlée à l'horodatage près ; une durée hors liste renvoie 422 ; un simple membre de l'équipe peut générer le lien.
- Le portail traiteur s'ouvre sans compte, avec `noindex` et `no-referrer`, sans aucune coordonnée (e-mail, téléphone, message privé) et sans les foyers absents ou en attente.
- Les liens non signés, altérés ou expirés renvoient 403 ; un format d'export inconnu renvoie 404.
- Les liens des exports enfants réutilisent l'expiration de la page et s'ouvrent seuls.
- CSV couverts : BOM, en-tête `Foyer;Couverts;Prénoms;Régime / allergies`, ligne complète, aucun contact, aucun foyer non présent.
- XLSX couverts : feuilles « Couverts » et « Synthèse », sans contacts.
- PDF couverts : signé, rendu vérifié en HTML (prénoms et régimes présents, aucun e-mail, téléphone ni message privé).

## Parcours navigateur

Événement de démonstration « Mariage QR 2026 », trois foyers (Koné présent 2 places, Yao présent 3 places, Bamba en attente), Chromium headless 1280 × 900 puis 390 × 844.

1. Depuis l'événement, ouverture du pilotage : couverts confirmés (5), évolution, régimes et allergies, section lien traiteur, page non indexable.
2. Téléchargement des exports invités : CSV avec BOM et en-tête complet, coordonnées et régimes présents, XLSX signé `PK`, PDF signé `%PDF`.
3. Génération d'un lien traiteur de 7 jours : URL signée, expiration affichée.
4. Ouverture du portail traiteur sans compte : synthèse des couverts, foyers Koné et Yao listés, prénoms et régimes visibles, foyer Bamba exclu, aucun e-mail ni téléphone, `noindex` et `no-referrer`.
5. Téléchargements du portail : CSV couverts avec BOM et ligne complète, XLSX sans contact, PDF signé ; tous sans coordonnées ni foyer en attente.
6. Refus des liens non signés ou altérés (403) et d'un format d'export inconnu (404).
7. Mobile 390 px : portail traiteur lisible en cartes, 0 px de débordement.
8. Mobile 390 px : pilotage lisible, graphique sur 15 → 28 sans découpe, 0 px de débordement, aucune erreur JavaScript.

Captures : `41-pilotage-desktop.png`, `42-pilotage-lien-traiteur.png`, `43-portail-traiteur.png`, `44-portail-traiteur-mobile.png`, `45-pilotage-mobile.png`.

Contrôle complémentaire : `pdftotext` sur `phase10-dossier.pdf` confirme les 5 couverts, les 2 foyers, les régimes et la mention « Aucune coordonnée d'invité n'est incluse ».

## Vérifications manuelles

```bash
docker compose exec app php artisan migrate
docker compose exec app ./vendor/bin/pest
docker compose exec app ./vendor/bin/pint --test
npm run build
docker compose exec app php artisan route:list --path=pilotage
docker compose exec app php artisan route:list --path=traiteur
docker compose exec app php artisan route:list --path=exports
```

- Vérifier qu'aucune route de portail traiteur n'est protégée par `auth` : la signature seule fait foi.
- Tester l'expiration réelle d'un lien de 1 jour en modifiant l'horloge du conteneur ou en attendant l'échéance.
- Contrôler l'ouverture des fichiers XLSX et PDF dans Excel, LibreOffice et un lecteur mobile.

## Résultats du 28 octobre 2026

### Automatisé

- Pest : **213 tests, 972 assertions**, tous verts (SQLite `:memory:`), dont **23 tests dédiés au pilotage et au dossier traiteur**.
- Pint : **118 fichiers** conformes.
- Vite : build réussi (`app.css` 61,49 kB, `app.js` 53,88 kB, `scanner.js` 339,19 kB, `leaflet.js` 149,51 kB).
- Playwright (Chromium headless, 1280 × 900 puis 390 × 844), parcours complet rejoué intégralement, sans erreur JavaScript, code de sortie 0.

### Environnement de validation

Le montage Windows (9p) ralentit fortement les requêtes du conteneur ; le parcours navigateur a été exécuté avec des attentes de 90 s. Aucun compte traiteur n'est créé : le portail repose uniquement sur l'URL signée. Les fichiers de démonstration sont ceux de l'événement 68.

### Corrections pendant la validation

- **Total de l'évolution** : le total additionnait les réponses hors fenêtre alors que le libellé indiquait « sur la période ». Seules les réponses des 14 jours sont désormais totalisées ; le cumul conserve la base antérieure.
- **Débordement mobile du pilotage** : le graphique imposait une largeur minimale de 32 rem, qui élargissait toute la grille. Les cartes ont reçu `min-w-0`, le graphique est passé en colonnes flexibles avec des libellés jour seul, et la période est rappelée en sous-titre.
- **Tableau traiteur tronqué sur mobile** : le tableau à six colonnes est remplacé sous 640 px par des cartes par foyer, avec les couverts en pastille et le total en pied de liste.
- **Eyebrow en double** : la page traiteur affichait « Dossier traiteur » deux fois ; la mise en page d'invitation accepte désormais un eyebrow et un conteneur personnalisés (« Lecture seule », `max-w-4xl`).
- **Tests de rendu** : les assertions de texte ont été basées sur `textContent` et non `innerText`, que la mise en capitale CSS modifiait.

### Reste manuel

- Valider l'ouverture des classeurs XLSX et des PDF sur Excel, LibreOffice et un smartphone.
- Vérifier l'expiration d'un lien signé en conditions réelles (lien de 1 jour) et le comportement d'un lien révoqué si l'événement est supprimé.
- Confirmer avec un traiteur que la liste (foyer, couverts, prénoms, régimes) suffit à la préparation, et décider si les allergies doivent être mises en évidence séparément.
