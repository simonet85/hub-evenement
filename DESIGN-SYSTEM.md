# Design System « Papier & lumière »

Système visuel de la plateforme d'événements privés. Source de vérité : l'artifact Design System créé avec Claude Design (jetons dans `tokens.json`). Direction et références : `assets/inspiration/brief.md`.

## 1. Principes

Papier & lumière est le système visuel de la plateforme d'événements privés (mariage, baptême, anniversaire, fête). Il vise une élégance éditoriale chaleureuse : faire-part imprimé d'un côté, site mobile léger de l'autre.

## Principes de contenu

- Écris en français, en tutoyant l'invité ou en vouvoyant selon le choix de l'organisateur, jamais un mélange dans le même événement.
- Ton chaleureux et précis : « Tu viens ? », « Nous serons heureux de te retrouver le 12 décembre à 16 h ». Pas de jargon, pas d'emoji décoratif.
- Les actions sont des verbes : « Confirmer ma présence », « Offrir ma participation », « Partager une photo ».
- Montants toujours en F CFA avec séparateur d'espace : « 25 000 F CFA ».

## Fondations visuelles

- Pose les pages sur `surface-100`, les cartes sur `surface-200`, les champs sur `surface-300`. Texte en `ink`, secondaire en `ink-muted`.
- Un seul accent par événement : `accent-mariage`, `accent-bapteme`, `accent-anniversaire` ou `accent-fete`. N'en mélange jamais deux sur un même écran. Le texte posé sur un accent est en `on-accent`.
- `gold` sert aux ornements et chiffres clés, jamais comme seul indicateur d'état.
- Titres en `display-xl`, `display-lg` et `heading` (famille display). Texte en `body-lg`, `body`, `label`, `caption`. Sur-titres en `eyebrow`. La police manuscrite `script` est réservée à un grand moment par écran (RSVP, Merci).
- Espacements : gouttière mobile `space-4`, entre sections `space-12` (mobile) et `space-20` (ordinateur). Toute cible tactile mesure au moins `tap`.
- Angles : `radius-sm` pour boutons, champs et cartes ; `radius-md` pour les photos ; `radius-pill` pour les pastilles de statut.
- Profondeur : bordure `line` d'abord, `shadow-card` seulement pour la carte d'invitation.
- Bordures de champs et anneau de focus en `line-strong` (focus : 2 px solide, décalé de 2 px).
- Mouvement : apparitions douces de 300 à 600 ms, jamais pendant un paiement. Respecte `prefers-reduced-motion`.
- Statuts : `success`, `warning`, `danger` s'accompagnent toujours d'un mot et d'une icône, jamais de la couleur seule.
- Photos : portraits et scènes réelles, chaudes. Motifs africains en touche discrète (bordures, séparateurs), jamais en fond plein.

## Thèmes

Le thème `light` est le thème par défaut de l'espace invité. Le thème `dark` est réservé à l'espace organisateur et aux préférences système sombres. Les deux respectent 4.5:1 pour le texte et 3:1 pour bordures et focus.

## Polices

`display` : Cormorant Garamond. `sans` : DM Sans. `script` : Pinyon Script. Les trois se chargent depuis Google Fonts (poids 400 à 600) avec `font-display: swap`. Limite-toi à ces trois familles pour garder des pages légères.

## 2. Couleurs

| Jeton | Clair | Sombre | Usage |
|---|---|---|---|
| `surface-100` | `#fbf6ee` | `#1d1711` | Fond de page (crème papier). |
| `surface-200` | `#f3ebdd` | `#272019` | Cartes et blocs posés sur surface-100. |
| `surface-300` | `#e8dcc8` | `#332a21` | Zones enfoncées, champs de formulaire, vignettes vides. |
| `ink` | `#2a1f17` | `#f4ece0` | Texte principal sur surface-100, surface-200 et surface-300. |
| `ink-muted` | `#66554a` | `#c4b5a3` | Texte secondaire, légendes, aides de champ, sur surface-100 à surface-300. |
| `line-strong` | `#8a7560` | `#8c7a68` | Bordures de champs et de contrôles (3:1 minimum), anneau de focus. |
| `line` | `#d9c9b0` | `#3f352a` | Filets décoratifs fins et séparateurs (ne portent pas de sens seuls). |
| `gold` | `#7a5c1c` | `#d9b36a` | Or doux : ornements, chiffres clés, liens secondaires, sur surface-100 et surface-200. |
| `accent-mariage` | `#4e6b48` | `#a4c19c` | Accent du thème Mariage (sauge) : boutons principaux, titres d'accent. |
| `accent-bapteme` | `#42698f` | `#9bbfe2` | Accent du thème Baptême (ciel poudré foncé). |
| `accent-anniversaire` | `#a0432a` | `#e59a80` | Accent du thème Anniversaire (terre cuite). |
| `accent-fete` | `#7a3d6f` | `#d3a3c8` | Accent du thème Fête (prune). |
| `on-accent` | `#fffaf2` | `#1d1711` | Texte et icônes posés sur un fond accent-* (4.5:1 minimum dans les deux thèmes). |
| `success` | `#2c6a4a` | `#8fcfad` | Texte de succès (Présence confirmée, Paiement reçu), toujours avec un mot et une icône. |
| `warning` | `#8a5a00` | `#e6b655` | Texte d'alerte (En attente, Photo à modérer), toujours avec un mot et une icône. |
| `danger` | `#a3362a` | `#f0a094` | Texte d'erreur (Paiement échoué, Refusé), toujours avec un mot et une icône. |

Contrastes vérifiés : tout texte atteint 4.5:1 sur les fonds nommés dans les deux thèmes ; bordures de champs (`line-strong`) 3:1 minimum. `line` est décoratif.

## 3. Typographie

| Famille | Pile CSS |
|---|---|
| `display` | `"Cormorant Garamond", "Cormorant", Georgia, serif` |
| `sans` | `"DM Sans", system-ui, -apple-system, "Segoe UI", sans-serif` |
| `script` | `"Pinyon Script", "Snell Roundhand", cursive` |

| Style | Taille / interligne | Graisse | Usage |
|---|---|---|---|
| `display-xl` | 64px / 60px | 500 | Nom des mariés ou de l'événement en couverture. |
| `display-lg` | 44px / 46px | 500 | Titres de section. |
| `heading` | 30px / 34px | 600 | Titres de carte et d'écran. |
| `body-lg` | 18px / 28px | 400 | Texte d'invitation, programme. |
| `body` | 16px / 24px | 400 | Texte courant et formulaires. |
| `label` | 14px / 20px | 600 | Libellés de champs, boutons. |
| `caption` | 13px / 18px | 400 | Légendes, aides, mentions. |
| `eyebrow` | 12px / 16px | 600 | Sur-titre en capitales (DATE, LIEU). |
| `script` | 48px / 52px | 400 | Grands moments : RSVP, Merci. Un par écran au maximum. |

## 4. Espacements, angles, ombre

| Jeton | Valeur | Usage |
|---|---|---|
| `space-1` | `4px` | Écart minimal entre icône et texte. |
| `space-2` | `8px` | Écart interne serré. |
| `space-3` | `12px` | Écart entre champs liés. |
| `space-4` | `16px` | Gouttière mobile, padding de carte. |
| `space-6` | `24px` | Padding de carte confortable. |
| `space-8` | `32px` | Écart entre blocs. |
| `space-12` | `48px` | Écart entre sections sur mobile. |
| `space-20` | `80px` | Écart entre sections sur ordinateur. |
| `tap` | `44px` | Taille minimale d'une cible tactile. |
| `radius-sm` | `2px` | Boutons, champs, cartes : angles presque droits, esprit papier. |
| `radius-md` | `6px` | Vignettes photo, bandeaux. |
| `radius-pill` | `999px` | Pastilles de statut. |
| `shadow-card` | `0 1px 2px rgba(42,31,23,0.10), 0 8px 24px rgba(42,31,23,0.08)` | Carte d'invitation posée sur la page. |

## 5. Thèmes par type d'événement

| Événement | Accent | Ambiance |
|---|---|---|
| Mariage | `accent-mariage` (sauge) | élégance sobre |
| Baptême | `accent-bapteme` (ciel poudré) | douceur |
| Anniversaire | `accent-anniversaire` (terre cuite) | chaleur |
| Fête | `accent-fete` (prune) | festif, plus profond |

Un accent par événement, appliqué via le même jeu de composants.

## 6. Composants

### InvitationCard

Carte d'invitation personnalisée. Le consommateur fournit : noms de l'événement, nom de l'invité ou de la famille, date, lieu et l'URL signée du QR code.

- Fond `surface-200`, bordure `line`, ombre `shadow-card`, angles `radius-sm`.
- Titre en `display-lg` ou `display-xl`, nom de l'invité en `script` (un seul moment manuscrit).
- Le QR code est généré côté serveur (SVG), contraste maximal : noir `ink` sur fond clair, même en thème sombre.
- Légende en `caption`, couleur `ink-muted`.

### Button

Bouton d'action. Le consommateur fournit le libellé (verbe à l'infinitif) et l'accent de l'événement.

- Un seul bouton principal (`accent-*` avec `on-accent`) par écran.
- Hauteur minimale `tap` (44 px), angles `radius-sm`, texte `label`.
- Le secondaire a une bordure `line-strong`. Le discret est en `gold`, souligné.
- Pendant un paiement, le bouton passe en état « chargement » et reste désactivé.

### RsvpForm

Formulaire de réponse. Le consommateur fournit le nombre de places de l'invitation et les options de régime.

- Choix présence/absence en deux boutons segmentés (`tap`), sélection en `accent-*` avec `on-accent`.
- Champs sur `surface-300`, bordure `line-strong`, libellé `label`, aide `caption` en `ink-muted`.
- Titre en `heading`. Confirmation animée après envoi, avec le mot « Merci » en `script`.
- Une erreur s'affiche en `danger` avec un mot et une icône sous le champ.

### GiftProgress

Jauge de cagnotte ou de cadeau. Le consommateur fournit le montant collecté, l'objectif (facultatif) et le nombre de participants.

- Montant en `display-lg`, format « 620 000 F CFA ».
- Barre `accent-*` sur `surface-300`, avec `role="progressbar"` et le pourcentage en texte, jamais en couleur seule.
- Aucun effet décoratif sur le parcours de paiement : montant, bénéficiaire et étapes restent visibles.

### StatusPill

Pastille de statut : présence (Présent, En attente, Absent), paiement (Reçu, En attente, Échoué), modération (Approuvée, À modérer, Rejetée).

- Toujours un mot et une icône : la couleur ne porte jamais seule le sens.
- Couleurs `success`, `warning`, `danger` sur `surface-200`.

## 7. Accessibilité et performance

- Mobile d'abord, cibles de 44 px, contraste AA, mouvement réduit respecté.
- Trois familles de polices maximum, poids 400 à 600, `font-display: swap`.
- Statuts jamais portés par la couleur seule.

## 8. Étapes suivantes

- Maquettes (type Design) avec ce système : invitation, RSVP, programme, carte, livre d'or, galerie, cadeaux, tableau de bord.
- Traduire les jetons en configuration Tailwind ou en variables CSS au moment de l'implémentation.
