---
name: Hub Événement
description: Invitations, RSVP, programme et cagnotte pour les grands moments — Papier & lumière.
colors:
  background: "#fbf6ee"
  surface: "#f3ebdd"
  surface-muted: "#e8dcc8"
  text-primary: "#2a1f17"
  text-secondary: "#66554a"
  border: "#d9c9b0"
  border-strong: "#8a7560"
  primary: "#4e6b48"
  primary-hover: "#445e3f"
  primary-active: "#3a5136"
  on-primary: "#fffaf2"
  gold: "#7a5c1c"
  focus-ring: "#2a1f17"
  success: "#2c6a4a"
  warning: "#7f5300"
  danger: "#a3362a"
  danger-hover: "#8f2f25"
  on-danger: "#fffaf2"
  accent-bapteme: "#42698f"
  accent-anniversaire: "#a0432a"
  accent-fete: "#7a3d6f"
typography:
  display-xl:
    fontFamily: 'Cormorant Garamond, Cormorant, Georgia, serif'
    fontSize: "clamp(2.5rem, 6vw, 4rem)"
    fontWeight: 500
    lineHeight: 1.02
    letterSpacing: "-0.02em"
  display-lg:
    fontFamily: 'Cormorant Garamond, Cormorant, Georgia, serif'
    fontSize: "clamp(2rem, 5vw, 2.75rem)"
    fontWeight: 500
    lineHeight: 1.05
  heading:
    fontFamily: 'Cormorant Garamond, Cormorant, Georgia, serif'
    fontSize: "clamp(1.5rem, 3.5vw, 1.875rem)"
    fontWeight: 600
    lineHeight: 1.13
  body-lg:
    fontFamily: 'DM Sans, system-ui, -apple-system, Segoe UI, sans-serif'
    fontSize: "1.125rem"
    fontWeight: 400
    lineHeight: 1.55
  body:
    fontFamily: 'DM Sans, system-ui, -apple-system, Segoe UI, sans-serif'
    fontSize: "1rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: 'DM Sans, system-ui, -apple-system, Segoe UI, sans-serif'
    fontSize: "0.875rem"
    fontWeight: 600
    lineHeight: 1.43
  caption:
    fontFamily: 'DM Sans, system-ui, -apple-system, Segoe UI, sans-serif'
    fontSize: "0.8125rem"
    fontWeight: 400
    lineHeight: 1.38
  eyebrow:
    fontFamily: 'DM Sans, system-ui, -apple-system, Segoe UI, sans-serif'
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.33
    letterSpacing: "0.16em"
  script:
    fontFamily: 'Pinyon Script, Snell Roundhand, cursive'
    fontSize: "clamp(2.5rem, 7vw, 3rem)"
    fontWeight: 400
    lineHeight: 1.08
rounded:
  sm: "2px"
  md: "6px"
  pill: "999px"
spacing:
  space-1: "4px"
  space-2: "8px"
  space-3: "12px"
  space-4: "16px"
  space-5: "20px"
  space-6: "24px"
  space-8: "32px"
  space-10: "40px"
  space-12: "48px"
  space-16: "64px"
  space-20: "80px"
  space-24: "96px"
  tap: "44px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.on-primary}"
    rounded: "{rounded.sm}"
    padding: "0 20px"
    height: "44px"
  button-primary-hover:
    backgroundColor: "{colors.primary-hover}"
    textColor: "{colors.on-primary}"
    rounded: "{rounded.sm}"
  button-primary-active:
    backgroundColor: "{colors.primary-active}"
    textColor: "{colors.on-primary}"
    rounded: "{rounded.sm}"
  button-secondary:
    backgroundColor: "transparent"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.sm}"
    padding: "0 20px"
    height: "44px"
  button-secondary-hover:
    backgroundColor: "{colors.surface-muted}"
    textColor: "{colors.text-primary}"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.sm}"
    padding: "0 20px"
    height: "44px"
  button-ghost-hover:
    backgroundColor: "{colors.surface-muted}"
  button-danger:
    backgroundColor: "{colors.danger}"
    textColor: "{colors.on-danger}"
    rounded: "{rounded.sm}"
    padding: "0 20px"
    height: "44px"
  button-danger-hover:
    backgroundColor: "{colors.danger-hover}"
    textColor: "{colors.on-danger}"
  button-link:
    backgroundColor: "transparent"
    textColor: "{colors.gold}"
    rounded: "{rounded.sm}"
    padding: "0 4px"
    height: "44px"
  field:
    backgroundColor: "{colors.surface-muted}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.sm}"
    padding: "10px 12px"
    height: "44px"
  card:
    backgroundColor: "{colors.surface}"
    rounded: "{rounded.sm}"
    padding: "24px"
  card-invitation:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.sm}"
  status-pill-neutral:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.pill}"
    padding: "2px 10px"
    height: "24px"
  status-pill-success:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.success}"
    rounded: "{rounded.pill}"
    padding: "2px 10px"
    height: "24px"
  status-pill-warning:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.warning}"
    rounded: "{rounded.pill}"
    padding: "2px 10px"
    height: "24px"
  status-pill-danger:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.danger}"
    rounded: "{rounded.pill}"
    padding: "2px 10px"
    height: "24px"
  alert-info:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.gold}"
    rounded: "{rounded.sm}"
    padding: "12px 16px"
  alert-success:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.success}"
    rounded: "{rounded.sm}"
    padding: "12px 16px"
  alert-warning:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.warning}"
    rounded: "{rounded.sm}"
    padding: "12px 16px"
  alert-danger:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.danger}"
    rounded: "{rounded.sm}"
    padding: "12px 16px"
  dialog:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text-primary}"
    rounded: "{rounded.sm}"
    padding: "24px"
    width: "32rem"
---

# Design System: Hub Événement

## Overview

**Creative North Star: « Papier & lumière » — l'invitation imprimée côté invité, le registre bien tenu côté organisateur.**

Hub Événement vit sur deux registres qui doivent sembler sortir de la même papeterie. Côté invité, l'écran est une invitation : papier crème, encre chaude, un titre en Cormorant Garamond, un accent d'événement unique, un or discret pour les ornements et un seul moment manuscrit. Côté organisateur, le même papier devient un registre : filets fins, chiffres tabulaires, statuts explicites et actions nommées par des verbes. L'émotion vient du matériau, jamais d'effets ajoutés.

Le système est chaleureux mais opérationnel. La bordure porte la structure ; l'ombre est un événement rare, réservée à la carte d'invitation et aux couches flottantes. Cette retenue donne confiance à un organisateur qui vérifie des couverts sur son téléphone et laisse à l'invité une impression d'objet imprimé, pas de tableau de bord.

La direction assume un ancrage ivoirien par petites touches — filets, séparateurs, photos réelles et chaudes — jamais par un motif plein cadre. Les rejets confirmés sont explicites : deux accents d'événement sur un même écran, l'emoji décoratif, la couleur seule comme statut, l'ombre utilisée comme séparateur et toute animation pendant un paiement.

**Key Characteristics:**
- Papier crème, encre brune, un seul accent d'événement, un or d'ornement.
- Cormorant Garamond pour les titres, DM Sans pour tout ce qui se lit ou s'actionne, Pinyon Script une fois par écran au maximum.
- La bordure d'abord ; deux ombres seulement, pour la carte d'invitation et les dialogues.
- Angles presque droits (2 px), filets de 1 px, pastilles et progression en pilule.
- Mobile d'abord, français uniquement, cibles tactiles de 44 px et focus toujours visible.
- Cible WCAG 2.2 AA ; aucune conformité formelle n'est revendiquée à ce jour.

## Colors

La palette est celle d'un papier de luxe discret : neutres chauds, encre brune, sauge par défaut, quatre accents d'événement et un or d'ornement. Les valeurs ci-dessous sont celles du thème clair, référence de l'espace invité.

### Primary
- **Sauge de mariage (#4E6B48)** : accent de l'événement par défaut, celui du mariage. Il colore le bouton principal, la barre de progression, la sélection, le filet supérieur de la carte d'invitation et les chiffres clés. Survol #445E3F, appui #3A5136, texte posé dessus #FFFAF2. Ce n'est pas une couleur de marque fixe : chaque événement remplace `primary` par son accent.

### Secondary
Les trois autres accents d'événement remplacent `primary` via une classe `ev-*` posée sur un ancêtre. Chacun fournit ses propres étapes de survol et d'appui dans `design-system/tokens.json`.
- **Ciel de baptême (#42698F)** : bleu doux, registre de la douceur.
- **Terre cuite (#A0432A)** : terre chaude, registre de la chaleur.
- **Prune de fête (#7A3D6F)** : prune profond, registre festif.

### Tertiary
- **Or discret (#7A5C1C)** : filets d'ornement, chiffres clés, liens secondaires et alertes d'information. L'or ne porte jamais seul un état, une erreur ou une sélection.

### Neutral
- **Crème de papier (#FBF6EE)** : fond de page clair.
- **Papier ivoire (#F3EBDD)** : surface des cartes et des blocs.
- **Carton patiné (#E8DCC8)** : champs, zones enfoncées, vignettes vides.
- **Encre chaude (#2A1F17)** : texte principal et anneau de focus.
- **Encre délavée (#66554A)** : texte secondaire, légendes et aides.
- **Filet de lin (#D9C9B0)** : filets décoratifs et séparateurs ; ne porte jamais de sens seul.
- **Trait de plume (#8A7560)** : bordure des champs et des contrôles (3,23:1 minimum sur `surface-muted`).
- **Crème lumineuse (#FFFAF2)** : texte et icônes posés sur un accent.

### Statuts
- **Vert feuille (#2C6A4A)** : succès.
- **Ambre brûlé (#7F5300)** : alerte. Cette valeur a été assombrie pour passer AA sur les champs ; ne pas revenir à l'ancienne teinte.
- **Terre de brique (#A3362A)** : erreur et actions destructrices ; survol #8F2F25, texte posé dessus #FFFAF2.
- Chaque statut s'exprime par un mot, une icône et une couleur, dans cet ordre de priorité.

### Thème sombre
Le thème sombre est câblé par `[data-theme="dark"]`, prévu pour l'espace organisateur, mais **pas activé automatiquement** aujourd'hui. Il inverse les rôles : fond #1D1711, surface #272019, surface enfoncée #332A21, texte #F4ECE0, texte secondaire #C4B5A3, filet #3F352A, bordure de contrôle #8C7A68, or #D9B36A, focus #F4ECE0, succès #8FCFAD, alerte #E6B655, danger #F0A094, survol danger #F4B0A6. Les accents s'éclaircissent : sauge #A4C19C, ciel #9BBFE2, terre cuite #E59A80, prune #D3A3C8. Le QR d'entrée fait exception et garde son fond crème.

### Named Rules
**The One Accent Rule.** Un seul accent d'événement par écran. Deux classes `ev-*` ne se combinent jamais ; l'accent se pose une fois, sur un ancêtre, et cascade vers les boutons, liens, barres et focus.

**The Gold Ornament Rule.** L'or orne, il n'informe pas. Il peut colorer un filet, un chiffre clé ou un lien secondaire, jamais le seul signe d'un état.

**The Never Color Alone Rule.** Un statut, une erreur ou une sélection associe toujours un mot et une icône à la couleur. La couleur seule est interdite.

**The QR Exception Rule.** Le QR d'entrée conserve un fond #FFFAF2 même en thème sombre, pour garantir la lecture par les lecteurs. C'est la seule couleur codée en dur tolérée dans une vue.

## Typography

**Display Font:** Cormorant Garamond (avec Cormorant, Georgia, serif)  
**Body Font:** DM Sans (avec system-ui, -apple-system, Segoe UI, sans-serif)  
**Script Font:** Pinyon Script (avec Snell Roundhand, cursive)

**Character:** un contraste éditorial entre un serif ancien, fin et généreux, et un sans-serif humaniste et neutre. Le serif donne le ton de l'invitation ; le sans porte les formulaires, les tableaux et les actions. Le script est un accent cérémoniel, jamais un outil de lecture.

### Hierarchy
- **Display XL** (500, `clamp(2.5rem, 6vw, 4rem)`, 1.02, -0.02em) : nom des mariés ou de l'événement en couverture.
- **Display LG** (500, `clamp(2rem, 5vw, 2.75rem)`, 1.05) : titres de page et de section.
- **Heading** (600, `clamp(1.5rem, 3.5vw, 1.875rem)`, 1.13) : titres de carte et d'écran.
- **Body LG** (400, 1.125rem, 1.55) : texte d'invitation et programme.
- **Body** (400, 1rem, 1.5) : texte courant et formulaires.
- **Label** (600, 0.875rem, 1.43) : libellés de champs et de boutons.
- **Caption** (400, 0.8125rem, 1.38) : légendes, aides et mentions.
- **Eyebrow** (600, 0.75rem, 1.33, 0.16em, capitales) : sur-titres DATE, LIEU, INVITER.
- **Script** (400, `clamp(2.5rem, 7vw, 3rem)`, 1.08) : RSVP, Merci, Bienvenue. Un seul moment par écran, décoratif et masqué aux lecteurs d'écran.

### Named Rules
**The One Script Rule.** Un seul moment manuscrit par écran. Le script est décoratif (`aria-hidden`), jamais porteur d'information.

**The Display Titles Rule.** Les titres `h1`–`h3` utilisent Cormorant Garamond ; les libellés, boutons, champs, tableaux et messages restent en DM Sans.

**The Tabular Numbers Rule.** Montants, compteurs, dates et colonnes de données utilisent des chiffres tabulaires. Les montants s'écrivent en F CFA avec un séparateur d'espace : « 25 000 F CFA ».

**The Measure Rule.** Texte courant 65ch, invitation 48ch, légendes longues 80ch. Les titres sont équilibrés (`text-wrap: balance`), les paragraphes évitent les veuves (`text-wrap: pretty`).

## Layout

Le système est mobile d'abord. La grille passe de 4 colonnes sur mobile à 8 sur tablette et 12 sur ordinateur, avec une gouttière de 16 px puis 24 px sur ordinateur. Les points de rupture sont 640 px, 768 px, 1024 px et 1280 px.

Trois conteneurs portent les largeurs de lecture : `.container-guest` à 42 rem (672 px) pour l'invitation, le RSVP et le programme ; `.container-app` à 64 rem (1024 px) pour l'espace organisateur ; `.container-wide` à 72 rem (1152 px) pour les tableaux et vues larges. Le padding horizontal est de 16 px sur mobile, 24 px dès 640 px et 32 px dès 1024 px, avec centrage automatique. Les vues existantes utilisent encore `max-w-*` ; toute nouvelle page préfère les conteneurs dédiés.

Le rythme vertical oppose 48 px entre sections sur mobile à 80 px sur ordinateur. Les cartes se contentent de 16 px, ou 24 px pour une carte confortable. Une seule action principale par écran, placée dans l'en-tête ou en fin de section ; sur mobile, elle peut être empilée ou pleine largeur. Les tableaux ne poussent jamais la page : ils défilent dans `.table-wrap`. La mise en page reste utilisable à 320 px et à 200 % de zoom.

La profondeur d'empilement est fixée : contenu 0, collant 10, en-tête 20, voile 40, dialogue 50, toast 60.

## Elevation & Depth

Le système est **border-first**. La séparation vient d'un filet de 1 px et d'un empilement tonal — `background` → `surface` → `surface-muted` —, pas d'une ombre. Les boutons, champs et cartes ordinaires sont plats au repos ; leur survol change la bordure ou le fond, jamais l'élévation. Les ombres sont réservées à deux rôles : l'objet « invitation » et la couche flottante.

### Shadow Vocabulary
- **Carte d'invitation** (`box-shadow: 0 1px 2px rgba(42, 31, 23, 0.10), 0 8px 24px rgba(42, 31, 23, 0.08)`) : carte d'invitation et carte d'événement du tableau de bord. Elle signale un objet posé sur la page.
- **Couche flottante** (`box-shadow: 0 24px 64px rgba(42, 31, 23, 0.18)`) : dialogue natif et menu flottant. Le voile du dialogue utilise l'encre à 55 %.

L'anneau de focus n'est pas une ombre : c'est un contour de 2 px, décalé de 2 px, immédiat et jamais supprimé.

### Named Rules
**The Border-First Rule.** La bordure porte la structure. Une ombre ne remplace jamais un filet de séparation.

**The Structural Shadow Rule.** Une ombre signale un objet (carte d'invitation) ou une couche flottante (dialogue, menu), jamais une hiérarchie, un survol ou une carte ordinaire.

## Shapes

La géométrie évoque le papier : angles presque droits, filets fins, aucune rondeur décorative. Les boutons, champs et cartes utilisent 2 px ; les vignettes photo 6 px ; les pastilles, barres de progression et spinners 999 px. Les bordures font 1 px, l'anneau de focus 2 px avec un décalage de 2 px.

La carte d'invitation et la carte d'événement du tableau de bord portent un filet d'accent de 4 px en haut. Le QR d'entrée est un carré à angles de 2 px, sur fond crème, avec 8 px de padding. Les séparateurs sont des filets de 1 px ; les motifs ivoiriens restent des traits et des séparateurs discrets, jamais un fond plein cadre ni un cadre ornemental lourd.

Aucune grande rondeur, aucun blob, aucune pastille de bouton : la pilule est réservée aux statuts, à la progression et aux spinners.

## Components

### Buttons
- **Shape:** rayon 2 px, hauteur minimale 44 px (52 px en `lg`), padding horizontal 20 px, texte 14 px/600 (13 px en `sm`, 16 px en `lg`), écart icône-texte 8 px.
- **Primary:** fond `primary`, texte `on-primary`, bordure de la même couleur. Un seul bouton principal par écran.
- **Hover / Focus:** survol `primary-hover` en 150 ms, appui `primary-active` en 75 ms, courbe standard. Focus visible : anneau 2 px `focus-ring`, décalé de 2 px.
- **Secondary / Ghost / Danger / Link:** secondaire transparent avec bordure `border-strong` et survol `surface-muted` ; fantôme sans bordure avec le même survol ; danger en `danger`/`on-danger` avec survol `danger-hover` ; lien en or souligné, survol en encre.
- **Disabled / Loading:** désactivé à 50 % d'opacité, curseur interdit. Chargement : spinner de 16 px, `aria-busy="true"`, action bloquée, libellé conservé — jamais le spinner seul.

### Inputs / Fields
- **Style:** hauteur 44 px, fond `surface-muted`, bordure 1 px `border-strong`, rayon 2 px, padding 10 px/12 px, texte 16 px. Le libellé est toujours visible et associé par `for`/`id` ; le placeholder ne le remplace jamais.
- **Focus:** bordure encre et anneau 2 px `focus-ring`, décalé de 2 px.
- **Error / Disabled:** erreur en bordure `danger`, message avec icône, `aria-invalid="true"` et `aria-describedby`. Désactivé à 50 % avec curseur interdit ; lecture seule sur fond `surface`. Cases et radios en `accent-color: primary`, cible de 44 px.

### Cards / Containers
- **Corner Style:** 2 px.
- **Background:** `surface`, avec `surface-muted` pour les zones enfoncées et les vignettes vides.
- **Shadow Strategy:** aucune ombre, sauf `card-invitation` qui porte l'ombre « objet » et le filet d'accent de 4 px.
- **Border:** 1 px `border`.
- **Internal Padding:** 16 px, ou 24 px pour une carte confortable.
- **Interactive:** seule la bordure passe à `border-strong` au survol ; pas de soulèvement, pas d'ombre.

### Status Pills
- **Style:** hauteur 24 px, padding 2 px/10 px, rayon pilule, texte 13 px/600, bordure et texte dans la teinte du statut, fond `surface`, icône de 12 px.
- **State:** neutre, succès, alerte, danger. Le mot est toujours présent ; l'icône par défaut est check, alert, x, ou aucune pour le neutre. Une pastille cliquable doit offrir une zone tactile de 44 px — la pastille-lien du tableau de bord, encore à 24 px, est un écart connu à corriger.

### Alerts
- **Style:** fond `surface`, bordure 1 px dans la teinte, rayon 2 px, padding 12 px/16 px, icône de 18 px, titre optionnel en 600.
- **State:** `role="status"` par défaut, `role="alert"` pour le danger. Les messages de session passent par ce composant.

### Empty States
- **Style:** carte bordée `surface`, padding 32 px/24 px, centrée, titre en display, description en texte secondaire, actions centrées.
- **Signature:** un mot manuscrit optionnel (`aria-hidden`) peut ouvrir l'état vide. Un seul par écran, jamais deux.

### Progress
- **Style:** piste de 6 px en `surface-muted`, barre `primary`, rayon pilule. La valeur est aussi écrite en texte (`valueLabel`), jamais portée par la couleur seule.
- **Behavior:** `role="progressbar"` avec min, max et valeur. Aucune animation pendant un paiement.

### Tables
- **Style:** `.table-wrap` assure le défilement horizontal ; `caption` décrit le tableau ; en-têtes en eyebrow capitales 12 px/600/0.16em ; cellules padding 12 px/16 px ; lignes séparées par un filet.
- **Numbers:** chiffres tabulaires, alignés à droite, montants en F CFA. Les actions sont alignées à droite avec des cibles de 44 px.

### Dialog
- **Style:** `<dialog>` natif, largeur maximale 32 rem, padding 24 px, rayon 2 px, ombre « couche flottante », voile encre à 55 %.
- **Behavior:** `showModal()` fournit le piège de focus et Échap ; le focus revient au déclencheur. Ouverture en 250 ms, jamais animée pour un paiement.

### Navigation
- **Style:** en-tête avec filet inférieur de 1 px, marque en Cormorant 20 px, liens en label 14 px/600 `text-secondary` avec survol encre, cibles de 44 px.
- **Invitation:** le même en-tête porte l'eyebrow « INVITATION PRIVÉE » et reste volontairement discret ; le pied de page est en caption secondaire. Le lien d'évitement « Aller au contenu » précède l'en-tête dans les deux layouts.

### Signature — Invitation Card & QR
Avec la carte d'événement du tableau de bord, la carte d'invitation est le seul objet qui a le droit de « sortir » de la page : ombre douce, filet d'accent de 4 px, titre en Cormorant, mesure de 48ch, séparateurs fins et QR sur fond crème. La carte d'événement reprend ce langage — filet d'accent, eyebrow, titre display, pastilles et actions — pour que l'organisateur et l'invité semblent tenir la même papeterie.

## Do's and Don'ts

### Do:
- **Do** utiliser les jetons sémantiques (`bg-surface`, `text-foreground`, `border-border-strong`, `bg-primary`) ; les vues ne référencent jamais une primitive ni un hex.
- **Do** poser une seule classe `ev-*` sur un ancêtre, puis laisser l'accent cascader vers les boutons, liens, barres et focus.
- **Do** réserver l'ombre à la carte d'invitation, à la carte d'événement, au dialogue et au menu flottant.
- **Do** garder 44 px de cible tactile, 8 px entre les cibles principales et un anneau de focus de 2 px décalé de 2 px.
- **Do** accompagner chaque statut d'un mot et d'une icône, et écrire les montants en chiffres tabulaires avec un séparateur d'espace.
- **Do** utiliser `text-foreground` pour le texte courant ; `text-primary` désigne l'accent de l'événement, pas le texte.
- **Do** réserver Cormorant Garamond aux titres et DM Sans aux libellés, champs, boutons, tableaux et messages.
- **Do** limiter le script à un moment par écran, décoratif et masqué aux lecteurs d'écran.
- **Do** garder le QR d'entrée sur fond #FFFAF2 et le rendre téléchargeable.
- **Do** écrire en français, avec un ton chaleureux et précis ; les actions sont des verbes.

### Don't:
- **Don't** combiner deux accents d'événement sur un même écran.
- **Don't** utiliser la couleur seule pour un statut, une erreur ou une sélection.
- **Don't** coder un hex ou une palette Tailwind brute dans une vue ; la seule exception documentée est le fond du QR.
- **Don't** utiliser une ombre comme séparateur, comme hiérarchie ou comme effet de survol.
- **Don't** employer deux moments manuscrits sur le même écran.
- **Don't** animer quoi que ce soit pendant un paiement ; le montant, le bénéficiaire et les étapes restent stables.
- **Don't** supprimer l'anneau de focus ni le différer.
- **Don't** utiliser d'emoji décoratif ni de motif africain en fond plein cadre.
- **Don't** utiliser un placeholder comme libellé, ni remplacer le libellé d'un bouton en chargement par le spinner seul.
- **Don't** mélanger tutoiement et vouvoiement dans un même événement.
- **Don't** utiliser de grandes rondeurs ni transformer un bouton en pilule ; la pilule est réservée aux statuts, à la progression et aux spinners.
- **Don't** mettre du texte courant en Cormorant ou en script.
- **Don't** utiliser `surface` comme fond du QR, même en thème sombre.
