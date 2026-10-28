# Phase 11 — Animations et accessibilité

## Objectif

L'expérience invitée gagne en douceur sans exclure personne : les sections de l'invitation et du programme apparaissent à l'approche du viewport, le contenu reste lisible sans JavaScript et avec `prefers-reduced-motion`, les formulaires annoncent leurs erreurs, la carte et les photos sont utilisables au clavier comme au lecteur d'écran, et le chargement mobile reste léger.

## Décisions de cadrage

- **Amélioration progressive** : le contenu est visible par défaut. Le JavaScript ajoute la classe `reveal-enabled` sur `<html>`, puis révèle chaque élément `[data-reveal]` via `IntersectionObserver`. Sans JavaScript, sans `IntersectionObserver` ou en mouvement réduit, rien n'est masqué.
- **Pas de bibliothèque d'animation** : transitions CSS de 600 ms (`opacity`, `translateY(12px)`), décalage progressif plafonné à environ 280 ms par lot visible. L'animation ne concerne que les sections sous la ligne de flottaison ; le titre et la carte d'invitation restent peints immédiatement.
- **Mouvement réduit** : `prefers-reduced-motion: reduce` désactive révélations et transitions. Le module n'active pas `reveal-enabled` et révèle tout si le réglage change en cours de visite.
- **Focus protégé** : un élément masqué par la révélation reste dans l'ordre de tabulation ; l'événement `focusin` révèle immédiatement son conteneur `[data-reveal]`. L'impression force aussi l'affichage.
- **Erreurs reliées** : chaque champ en erreur reçoit `aria-invalid="true"` et `aria-describedby` vers le message (`role="alert"`), y compris les groupes de radios. Les aides persistantes portent un identifiant et restent liées au champ.
- **Saisie mobile** : `autocomplete` (`name`, `tel`, `email`) et `inputmode` adaptés sur les champs invités ; les cibles tactiles restent à 44 px minimum.
- **Carte accessible** : la carte du programme devient une région nommée et décrite, atteignable au clavier ; l'adresse et les liens d'itinéraire restent l'alternative textuelle.
- **Photos** : `loading="lazy"`, `decoding="async"`, alternative textuelle existante et ouverture dans une nouvelle fenêtre annoncée.
- **Performance mobile** : Leaflet et le fond de carte ne sont importés que lorsque la carte approche du viewport (`rootMargin` 200 px), avec repli immédiat sans `IntersectionObserver`. Les polices gardent `preconnect` et `display=swap`. `theme-color` harmonise la barre du navigateur.

## User stories

- En tant qu'invité, je vois l'invitation s'installer doucement en faisant défiler la page, sans que le contenu soit inaccessible si mon navigateur n'exécute pas JavaScript.
- En tant qu'invité sensible au mouvement, je vois tout le contenu immédiatement, sans animation.
- En tant qu'invité au clavier, je peux sauter au contenu, parcourir les liens dans l'ordre visuel, choisir ma présence, remplir le RSVP, signer le livre d'or et ouvrir le programme sans souris.
- En tant qu'invité utilisant un lecteur d'écran, j'entends le champ fautif, son message d'erreur et les aides de saisie.
- En tant qu'invité sur mobile, je consulte l'invitation sans débordement et la carte ne bloque pas le premier affichage.

## Règles

### Révélation au scroll

- Éléments concernés : sections de l'invitation (invitation pour, QR, réponse, RSVP, livre d'or) et du programme (programme, code vestimentaire, carte).
- Attribut unique `data-reveal` ; aucun contenu interactif n'est retiré du DOM.
- `reveal-enabled` n'est posé que si `IntersectionObserver` existe et que le mouvement n'est pas réduit.
- Chaque élément est révélé une seule fois (`data-reveal-shown`), puis retiré de l'observateur.
- Le décalage est attribué par lot visible, plafonné à quatre crans de 70 ms.
- `prefers-reduced-motion: reduce`, `@media print` et absence de JavaScript affichent le contenu sans transition.
- Un changement de réglage en cours de session révèle immédiatement tout le contenu.

### Accessibilité

- Chaque page conserve `lang="fr"`, le lien d'évitement, `main#contenu` et un seul `h1`.
- Les formulaires RSVP et livre d'or relient chaque erreur et chaque aide au champ concerné (`aria-invalid`, `aria-describedby`) ; le registre annonce ses erreurs par une alerte de synthèse `role="alert"`, ses formulaires multiples partageant les noms de champs.
- Les erreurs sont annoncées (`role="alert"`), sans déplacer le focus automatiquement.
- Les groupes de radios sont dans un `fieldset` avec `legend`.
- Les champs de contact portent `autocomplete="tel"` / `inputmode="tel"` ; les noms `autocomplete="name"`.
- Les images ont une alternative textuelle ; les liens vers une nouvelle fenêtre l'annoncent.
- La carte est une région nommée, décrite par un texte visible ou masqué, et reste utilisable au clavier via Leaflet.
- Les états ne reposent jamais sur la couleur seule : les pastilles portent un texte.
- Le contour de focus reste visible (2 px) sur tous les éléments interactifs.
- Les cibles tactiles principales mesurent au moins 44 px.

### Performance mobile

- La carte n'est initialisée que lorsqu'elle entre dans une marge de 200 px autour du viewport.
- Sans `IntersectionObserver`, l'import est immédiat.
- Les images de galerie utilisent `loading="lazy"` et `decoding="async"`.
- Aucune police ni ressource tierce supplémentaire n'est ajoutée.
- Le premier rendu de l'invitation ne dépend ni de Leaflet ni de la carte.

## Hors périmètre

- Thème sombre activable, sélecteur de contraste renforcé et préférences persistées.
- Auto-hébergement des polices et suppression des requêtes Google Fonts.
- Traduction, lecteur d'écran dédié, application mobile native.
- Certification WCAG complète et audit externe.
- Animation de transition entre pages.
- Réduction de la taille des photos existantes (traitement déjà en place).

## Critères d'acceptation

- Sans JavaScript, l'invitation et le programme affichent toutes les sections.
- Avec JavaScript et mouvement autorisé, les sections `[data-reveal]` sont masquées puis révélées à l'entrée dans le viewport, avec un décalage par lot.
- Avec `prefers-reduced-motion: reduce`, aucune section n'est masquée et aucune transition de révélation n'est appliquée.
- Le focus clavier révèle immédiatement la section qui le reçoit.
- Un parcours clavier complet (invitation → RSVP → livre d'or → programme → galerie → cadeaux) aboutit sans souris.
- Un RSVP invalide affiche un message relié au champ (`aria-invalid`, `aria-describedby`, `role="alert"`).
- La carte du programme est une région nommée et décrite, et les liens d'itinéraire restent disponibles.
- Les photos de la galerie sont chargées paresseusement avec un décodage asynchrone et une alternative textuelle.
- Sur mobile 390 px, invitation, programme, galerie et cadeaux ne débordent pas horizontalement et les boutons restent à 44 px.
- Le build Vite réussit et la suite Pest reste verte.
