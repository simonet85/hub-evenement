# Plan — Phase 11

## Étapes

1. **Révélation** : module `resources/js/reveal.js` (IntersectionObserver, lots, mouvement réduit, focus, changement de réglage) et utilitaires CSS associés.
2. **Marquage** : attributs `data-reveal` sur les sections de l'invitation et du programme ; aucune sur la carte d'en-tête.
3. **Formulaires invités** : erreurs et aides reliées dans l'invitation (RSVP, livre d'or) et le registre (cagnotte, cadeaux), attributs `autocomplete` / `inputmode`.
4. **Contenu** : carte du programme en région décrite, galerie en chargement paresseux et décodage asynchrone, nouvelle fenêtre annoncée.
5. **Performance** : import différé de `program-map.js` à l'approche du viewport ; `theme-color` dans les deux mises en page.
6. **Tests** : Pest de sémantique et de câblage ARIA ; parcours E2E clavier, mouvement réduit, mobile et axe-core si disponible.
7. **Validation** : Pint, Pest, build, captures, README et `validation.md`.

## Fichiers

```
resources/js/reveal.js
resources/js/app.js
resources/css/app.css
resources/views/layouts/invitation.blade.php
resources/views/layouts/app.blade.php
resources/views/invitations/show.blade.php
resources/views/invitations/programme.blade.php
resources/views/gallery/show.blade.php
resources/views/registry/show.blade.php
tests/Feature/AccessibilityTest.php
specs/phase-11/requirements.md
specs/phase-11/plan.md
specs/phase-11/validation.md
```

## Détails techniques

### Reveal

- `[data-reveal]` visible par défaut ; `html.reveal-enabled [data-reveal]:not([data-reveal-shown])` masque avec `opacity: 0` et `translateY(12px)`.
- Transition `opacity` / `transform` de `--duration-reveal` avec `--motion-ease-enter`, `transition-delay: var(--reveal-delay, 0ms)`.
- `IntersectionObserver` avec `rootMargin: '0px 0px -10% 0px'`, `threshold: 0.05`.
- Chaque lot visible reçoit `--reveal-delay` = index × 70 ms, plafonné à 280 ms.
- `focusin` révèle le plus proche ancêtre `[data-reveal]` (formulaires atteints au clavier).
- `matchMedia('(prefers-reduced-motion: reduce)')` : pas de classe ; sur changement, révélation immédiate.
- Absence d'`IntersectionObserver` : révélation immédiate sans masquage.
- `@media print` et `prefers-reduced-motion` forcent `opacity: 1; transform: none`.
- Import conditionnel dans `app.js` si `[data-reveal]` existe.

### Formulaires

- Invitation, RSVP : `rsvp_status` (fieldset), `rsvp_seats`, `attendee_names`, `dietary_notes`, `rsvp_message`.
- Invitation, livre d'or : `author_name` (`autocomplete="name"`), `message` (aide existante reliée).
- Registre : `autocomplete="tel"` et `inputmode="tel"` sur les téléphones, `inputmode="numeric"` sur les montants ; l'alerte de synthèse `role="alert"` annonce les erreurs, les formulaires multiples partageant les noms de champs.
- Chaque erreur des formulaires RSVP et livre d'or : identifiant `{champ}-error`, `role="alert"`, champ en `aria-invalid="true"` et `aria-describedby`.
- Les aides (`help`) portent un identifiant et sont incluses dans `aria-describedby` avec l'erreur le cas échéant.

### Carte du programme

- Conteneur : `role="region"`, `aria-label`, `aria-describedby="carte-aide"`.
- Texte d'aide `#carte-aide` : carte interactive, clavier, itinéraires ci-dessous.
- `program-map.js` importé seulement quand la carte approche du viewport (`rootMargin: '200px'`), ou immédiatement sans `IntersectionObserver`.
- Leaflet conserve son support clavier et l'attribution OpenStreetMap.

### Galerie

- `loading="lazy"`, `decoding="async"`, alt existant.
- Lien d'agrandissement : mention « nouvelle fenêtre » accessible.
- Légendes et alternatives inchangées.

### Performance

- `theme-color` `#fbf6ee` sur les deux mises en page.
- Aucun nouveau paquet npm ; Vite découpe toujours Leaflet et le scanner.
- Le premier rendu de l'invitation n'importe pas Leaflet.

## Tests

### AccessibilityTest (Pest)

- Les deux mises en page exposent `lang="fr"`, le lien d'évitement et `main#contenu`.
- Invitation et programme : un seul `h1`, `data-reveal` sur les sections, non sur l'en-tête.
- RSVP invalide : `aria-invalid`, `aria-describedby` et message `role="alert"` présents.
- Livre d'or invalide : même câblage sur le nom et le message.
- Registre : `autocomplete`/`inputmode` sur les téléphones, erreurs reliées.
- Programme : carte en `region` décrite, pas de `role="application"`, liens d'itinéraire présents.
- Galerie : `loading="lazy"`, `decoding="async"`, mention de nouvelle fenêtre.
- CSS : règles `reveal-enabled`, `prefers-reduced-motion` et impression présentes.

### Parcours E2E

- Chromium 1280 × 900 puis 390 × 844, invité Koné, données de démonstration.
- Défilement : sections `[data-reveal]` masquées au départ puis `data-reveal-shown` après entrée ; opacité finale 1.
- Clavier : lien d'évitement, focus visible, radios aux flèches, RSVP soumis, livre d'or signé, programme et carte atteints, galerie et registre ouverts.
- Mouvement réduit : aucune section masquée, pas de classe `reveal-enabled`.
- Carte : Leaflet initialisé seulement après approche du viewport.
- Mobile : aucun débordement, boutons à 44 px minimum, captures.
- axe-core (invitation et programme) si le script est disponible : aucune violation critique ou sérieuse.

## Risques

- **Contenu masqué sans JavaScript** : par construction aucune classe n'est posée sans JS ; test explicite en désactivant JavaScript.
- **Focus dans un élément masqué** : révélation via `focusin` et contenu toujours focusable.
- **Leaflet et mesure de taille** : révélation par `opacity`/`transform` sans impact sur la mise en page ; `invalidateSize()` conservé après import.
- **Flash d'apparition** : le masquage n'est posé qu'après chargement du module ; les sections restent visibles si le module échoue.
- **Erreurs de validation imbriquées** : identifiants uniques par champ ; pas de focus automatique.
