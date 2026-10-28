# Design System « Papier & lumière » — v2

| Champ | Valeur |
|---|---|
| Version | 2.0 |
| Date | 27 octobre 2026 |
| Statut | Fondations et composants implémentés et vérifiés |
| Source de vérité | `design-system/tokens.json` |
| Implémentation CSS | `resources/css/app.css` (Tailwind 4, CSS-first) |
| Composants Blade | `resources/views/components` |
| Cadre produit | `specs/mission.md`, `docs/FRONTEND_DESIGN.txt` |

> **Périmètre v2.** La direction validée « Papier & lumière » est conservée et formalisée : jetons sémantiques, états complets, mouvement, responsive et accessibilité. Les vues existantes ne sont pas réécrites ; elles héritent des corrections via les classes et les alias legacy.

## 1. Direction

« L'invitation imprimée côté invité, le registre bien tenu côté organisateur. » Le système cherche une élégance éditoriale chaleureuse — papier crème, encre chaude, or discret — sans perdre la précision opérationnelle attendue d'un outil de gestion d'événements.

### 1.1 Principes de contenu

- Français. Tutoiement ou vouvoiement choisi par l'organisateur, jamais mélangés dans un même événement.
- Ton chaleureux et précis : « Tu viens ? », « Nous serons heureux de te retrouver le 12 décembre à 16 h ». Pas de jargon, pas d'emoji décoratif.
- Les actions sont des verbes : « Confirmer ma présence », « Offrir ma participation », « Partager une photo ».
- Montants en F CFA avec séparateur d'espace : « 25 000 F CFA ».

### 1.2 Principes visuels

- Un seul accent d'événement par écran, jamais deux.
- L'or sert aux ornements, aux chiffres clés et aux liens secondaires, jamais de seul indicateur d'état.
- Un seul moment manuscrit (`script`) par écran.
- La bordure d'abord ; l'ombre est réservée à la carte d'invitation et aux dialogues.
- Un statut s'exprime toujours par un mot et une icône, jamais par la couleur seule.
- Photos réelles et chaudes ; motifs africains en touche discrète (filets, séparateurs), jamais en fond plein.

## 2. Architecture des jetons

### 2.1 Trois couches

| Couche | Rôle | Emplacement |
|---|---|---|
| Primitives | Valeurs brutes, indépendantes du thème (`paper-50`, `ink-900`, `sage-700`…) | `tokens.json → color.primitives`, variables `:root` de `app.css` |
| Sémantiques | Rôle fonctionnel (`background`, `surface`, `text-secondary`, `border-strong`, `primary`…) | `tokens.json → color.semantic`, `:root` et `[data-theme='dark']` |
| Composants | Décisions réutilisables (`btn`, `field`, `card`, `status-pill`, `alert`…) | `tokens.json → components`, `@layer components` de `app.css` |

Les vues consomment uniquement la couche sémantique ou les classes de composants. Elles ne référencent jamais une primitive ni un code hexadécimal.

### 2.2 Passerelle Tailwind 4

`app.css` déclare `@theme inline` pour exposer les jetons sémantiques comme utilitaires Tailwind :

```css
@theme inline {
    --color-background: var(--background);
    --color-surface: var(--surface);
    --color-foreground: var(--text-primary);
    --color-primary: var(--primary);
    --color-on-primary: var(--on-primary);
    /* … */
    --text-heading: clamp(1.5rem, 3.5vw, 1.875rem);
    --font-display: var(--font-display-stack);
    --radius-sm: var(--control-radius);
    --shadow-card: var(--card-shadow);
    --container-guest: 42rem;
}
```

Exemples : `bg-background`, `bg-surface`, `text-foreground`, `text-muted-foreground`, `border-border-strong`, `bg-primary`, `text-on-primary`, `text-success`, `shadow-card`, `container-guest`, `text-heading`, `font-display`.

> **Attention.** Le texte courant utilise `text-foreground`. `text-primary` désigne la couleur d'accent de l'événement, pas le texte principal.

### 2.3 Règles d'usage

1. Une nouvelle couleur ou un nouveau rôle passe d'abord par `tokens.json`, puis par `app.css`, puis par cette documentation.
2. Les vues utilisent les utilitaires sémantiques ou les classes de composants ; pas de hex, pas de `rgb()`, pas de palette Tailwind brute (`gray-700`, `red-500`…).
3. Les motifs répétés (bouton, champ, pastille, alerte, carte) passent par une classe de composant et, quand le balisage ou l'ARIA est impliqué, par un composant Blade.
4. Le thème sombre s'active par `data-theme="dark"` sur `<html>` ; il n'est pas activé automatiquement aujourd'hui.
5. Le type d'événement s'applique par la classe `ev-*` renvoyée par `EventType::accentClass()`.

### 2.4 Alias legacy

Les alias restent supportés jusqu'à une v3 pour ne pas casser les vues existantes.

| Alias legacy | Jeton sémantique |
|---|---|
| `surface-100` | `background` |
| `surface-200` | `surface` |
| `surface-300` | `surface-muted` |
| `ink` | `text-primary` |
| `ink-muted` | `text-secondary` |
| `line` | `border` |
| `line-strong` | `border-strong` |
| `accent` | `primary` |
| `on-accent` | `on-primary` |
| `accent-mariage` / `accent-bapteme` / `accent-anniversaire` / `accent-fete` | accents d'événement |

## 3. Couleurs

### 3.1 Jetons sémantiques

| Jeton | Variable CSS | Utilitaire | Clair | Sombre | Usage |
|---|---|---|---|---|---|
| `background` | `--background` | `bg-background` | `#fbf6ee` | `#1d1711` | Fond de page. |
| `surface` | `--surface` | `bg-surface` | `#f3ebdd` | `#272019` | Cartes et blocs. |
| `surface-muted` | `--surface-muted` | `bg-surface-muted` | `#e8dcc8` | `#332a21` | Champs, zones enfoncées, vignettes vides. |
| `text-primary` | `--text-primary` | `text-foreground` | `#2a1f17` | `#f4ece0` | Texte principal. |
| `text-secondary` | `--text-secondary` | `text-muted-foreground` | `#66554a` | `#c4b5a3` | Légendes, aides, texte secondaire. |
| `border` | `--border` | `border-border` | `#d9c9b0` | `#3f352a` | Filets décoratifs et séparateurs. |
| `border-strong` | `--border-strong` | `border-border-strong` | `#8a7560` | `#8c7a68` | Bordures de champs et de contrôles. |
| `primary` | `--primary` | `bg-primary` / `text-primary` | `#4e6b48` | `#a4c19c` | Accent de l'événement. |
| `primary-hover` | `--primary-hover` | `hover:bg-primary-hover` | `#445e3f` | `#b3ccab` | Survol des actions principales. |
| `primary-active` | `--primary-active` | `active:bg-primary-active` | `#3a5136` | `#c2d7bb` | Appui sur les actions principales. |
| `on-primary` | `--on-primary` | `text-on-primary` | `#fffaf2` | `#1d1711` | Texte et icônes sur `primary`. |
| `gold` | `--gold` | `text-gold` | `#7a5c1c` | `#d9b36a` | Ornements, chiffres clés, liens secondaires. |
| `focus-ring` | `--focus-ring` | `outline-focus-ring` | `#2a1f17` | `#f4ece0` | Anneau de focus 2 px, décalé de 2 px. |
| `success` | `--success` | `text-success` | `#2c6a4a` | `#8fcfad` | Succès, avec un mot et une icône. |
| `warning` | `--warning` | `text-warning` | `#7f5300` | `#e6b655` | Alerte, avec un mot et une icône. |
| `danger` | `--danger` | `text-danger` / `bg-danger` | `#a3362a` | `#f0a094` | Erreur, avec un mot et une icône. |
| `danger-hover` | `--danger-hover` | `hover:bg-danger-hover` | `#8f2f25` | `#f4b0a6` | Survol des actions destructrices. |
| `on-danger` | `--on-danger` | `text-on-danger` | `#fffaf2` | `#1d1711` | Texte et icônes sur `danger`. |

Le `warning` clair a été assombri de `#8a5a00` à `#7f5300` pour passer AA sur `surface-muted` (4,94:1). Ne pas revenir à l'ancienne valeur.

### 3.2 Contrastes vérifiés

Matrice recalculée le 27 octobre 2026 (WCAG 2.x, rapport de luminance relative).

| Paire | Clair | Sombre |
|---|---|---|
| `text-primary` sur `background` | 14,94:1 | 15,15:1 |
| `text-primary` sur `surface` | 13,57:1 | 13,71:1 |
| `text-primary` sur `surface-muted` | 11,86:1 | 12,00:1 |
| `text-secondary` sur `background` | 6,59:1 | 8,86:1 |
| `text-secondary` sur `surface` | 5,99:1 | 8,02:1 |
| `text-secondary` sur `surface-muted` | 5,23:1 | 7,02:1 |
| `gold` sur `surface-muted` | 4,59:1 | 7,10:1 |
| `success` sur `surface-muted` | 4,74:1 | 7,83:1 |
| `warning` sur `surface-muted` | 4,94:1 | 7,49:1 |
| `danger` sur `surface-muted` | 4,97:1 | 6,80:1 |
| `on-primary` sur l'accent le plus faible en clair (baptême) | 5,54:1 | — |
| `on-primary` sur l'accent le plus faible en sombre (anniversaire) | — | 7,83:1 |
| `border-strong` sur `surface-muted` (minimum interface) | 3,23:1 | 3,41:1 |
| `border-strong` sur `background` | 4,07:1 | 4,31:1 |
| `focus-ring` sur `background` | 14,94:1 | 15,15:1 |

- Tout texte atteint au moins 4,5:1 sur les trois surfaces.
- Toute bordure de contrôle atteint au moins 3:1. `border` reste décoratif et ne porte jamais de sens seul.
- L'anneau de focus est aussi contrasté que le texte principal.

### 3.3 Accents par type d'événement

| Événement | Classe | Ambiance | Primaire clair | Primaire sombre |
|---|---|---|---|---|
| Mariage | `ev-mariage` | Élégance sobre | sauge `#4e6b48` | sauge claire `#a4c19c` |
| Baptême | `ev-bapteme` | Douceur | ciel `#42698f` | ciel clair `#9bbfe2` |
| Anniversaire | `ev-anniversaire` | Chaleur | terre cuite `#a0432a` | terre claire `#e59a80` |
| Fête | `ev-fete` | Festif, plus profond | prune `#7a3d6f` | prune claire `#d3a3c8` |

Chaque accent fournit aussi `primary-hover` et `primary-active`, définis dans `tokens.json`. La classe se pose sur un ancêtre ; les variables cascadent vers les boutons, liens, barres de progression et le focus. En thème sombre, les variantes claires remplacent automatiquement les variantes foncées.

### 3.4 Thèmes

- **Clair** : thème par défaut, espace invité et pages publiques.
- **Sombre** : espace organisateur et préférence système, activé par `data-theme="dark"`. Le câblage CSS existe, mais l'activation automatique n'est pas branchée (voir § 12).

## 4. Typographie

### 4.1 Familles

| Famille | Utilitaire | Pile |
|---|---|---|
| Display | `font-display` | `"Cormorant Garamond", "Cormorant", Georgia, serif` |
| Sans | `font-sans` | `"DM Sans", system-ui, -apple-system, "Segoe UI", sans-serif` |
| Script | `font-script` | `"Pinyon Script", "Snell Roundhand", cursive` |

Les trois familles sont chargées depuis Google Fonts avec `display=swap`. Limite absolue : ces trois familles.

### 4.2 Échelle fluide

| Style | Utilitaire | Taille | Interligne | Graisse | Usage |
|---|---|---|---|---|---|
| `display-xl` | `text-display-xl` | `clamp(2.5rem, 6vw, 4rem)` | 1,02 | 500 | Nom des mariés ou de l'événement en couverture. |
| `display-lg` | `text-display-lg` | `clamp(2rem, 5vw, 2.75rem)` | 1,05 | 500 | Titres de section. |
| `heading` | `text-heading` | `clamp(1.5rem, 3.5vw, 1.875rem)` | 1,13 | 600 | Titres de carte et d'écran. |
| `body-lg` | `text-body-lg` | `1.125rem` | 1,55 | 400 | Texte d'invitation, programme. |
| `body` | `text-body` | `1rem` | 1,50 | 400 | Texte courant et formulaires. |
| `label` | `text-label` | `0.875rem` | 1,43 | 600 | Libellés, boutons. |
| `caption` | `text-caption` | `0.8125rem` | 1,38 | 400 | Légendes, aides, mentions. |
| `eyebrow` | `text-eyebrow` | `0.75rem` | 1,33 | 600, `0.16em` | Sur-titre en capitales (DATE, LIEU). |
| `script` | `text-script` | `clamp(2.5rem, 7vw, 3rem)` | 1,08 | 400 | RSVP, Merci. Un par écran maximum. |

Les titres `h1`–`h3` utilisent automatiquement la famille display ; `text-wrap: balance` s'applique aux titres et `text-wrap: pretty` aux paragraphes.

### 4.3 Mesures et chiffres

- `.measure` : 65ch pour le texte courant ; `.measure-narrow` : 48ch pour l'invitation ; 80ch pour les légendes longues.
- `.tabular` (ou classe `.num` dans un tableau) pour les montants, compteurs, dates et colonnes de données.
- Montants toujours en F CFA avec séparateur d'espace : « 620 000 F CFA ».

## 5. Espacement et mise en page

### 5.1 Échelle

| Jeton | Valeur | Usage |
|---|---|---|
| `space-0` | 0 | Suppression d'espace. |
| `space-1` | 4px | Icône et texte. |
| `space-2` | 8px | Écart interne serré. |
| `space-3` | 12px | Champs liés, padding d'alerte. |
| `space-4` | 16px | Gouttière mobile, padding de carte. |
| `space-5` | 20px | Padding horizontal de bouton. |
| `space-6` | 24px | Carte confortable, gouttière tablette. |
| `space-8` | 32px | Blocs, gouttière desktop. |
| `space-10` | 40px | Sous-section. |
| `space-12` | 48px | Sections sur mobile. |
| `space-16` | 64px | Sections sur tablette. |
| `space-20` | 80px | Sections sur ordinateur. |
| `space-24` | 96px | Respiration éditoriale exceptionnelle. |
| `tap` | 44px | Cible tactile minimale. |

L'échelle correspond à celle de Tailwind (`p-4` = 16px). Dans le CSS custom, utiliser `var(--space-*)`.

### 5.2 Conteneurs

| Classe | Largeur | Usage |
|---|---|---|
| `.container-guest` | 42rem (672px) | Invitation, RSVP, programme. |
| `.container-app` | 64rem (1024px) | Espace organisateur. |
| `.container-wide` | 72rem (1152px) | Tableaux et vues larges. |

Padding horizontal : 16px sur mobile, 24px à partir de 640px, 32px à partir de 1024px ; centrage automatique.

### 5.3 Points de rupture et grille

| Nom | Valeur |
|---|---|
| `sm` | 640px |
| `md` | 768px |
| `lg` | 1024px |
| `xl` | 1280px |

Grille : 4 colonnes sur mobile, 8 sur tablette, 12 sur ordinateur ; gouttière 16px, 24px sur ordinateur. Les mises en page restent utilisables à 320px et à 200 % de zoom.

### 5.4 Profondeur

| Jeton | Valeur |
|---|---|
| `z-content` | 0 |
| `z-sticky` | 10 |
| `z-header` | 20 |
| `z-overlay` | 40 |
| `z-dialog` | 50 |
| `z-toast` | 60 |

## 6. Géométrie et profondeur

| Jeton | Valeur | Usage |
|---|---|---|
| `radius-sm` / `rounded-sm` | 2px | Boutons, champs, cartes : angles presque droits, esprit papier. |
| `radius-md` / `rounded-md` | 6px | Vignettes photo, bandeaux. |
| `radius-pill` / `rounded-pill` | 999px | Pastilles et barres de progression. |
| `border` | 1px | Filets et séparateurs. |
| `border-strong` | 1px | Contour des champs et contrôles. |
| `focus-width` | 2px | Anneau de focus. |
| `focus-offset` | 2px | Décalage de l'anneau. |
| `shadow-card` | `0 1px 2px rgba(42,31,23,.10), 0 8px 24px rgba(42,31,23,.08)` | Carte d'invitation. |
| `shadow-overlay` | `0 24px 64px rgba(42,31,23,.18)` | Dialogue et menu flottant. |

La bordure porte la structure ; l'ombre ne doit pas devenir un substitut de séparation.

## 7. Mouvement

### 7.1 Durées

| Jeton | Valeur | Usage |
|---|---|---|
| `instant` | 0ms | Focus : état immédiat. |
| `micro` | 75ms | Appui, micro-interaction. |
| `fast` | 150ms | Survol, couleur, bordure. |
| `base` | 250ms | Dialogue, révélation courte. |
| `slow` | 400ms | Apparition de contenu. |
| `reveal` | 600ms | Moment scénarisé unique par écran. |

### 7.2 Courbes

| Jeton | Valeur |
|---|---|
| `standard` | `cubic-bezier(0.2, 0, 0, 1)` |
| `enter` | `cubic-bezier(0.16, 1, 0.3, 1)` |
| `exit` | `cubic-bezier(0.4, 0, 1, 1)` |

### 7.3 Règles

- Survol et focus : 150 ms, courbe standard. Le focus est immédiat et ne doit jamais être différé.
- Appui : 75 ms.
- Une seule apparition scénarisée par écran (`.reveal`, `.reveal-delay-1/2/3`), réservée à la couverture ou à la confirmation.
- Aucune animation pendant un paiement : le montant, le bénéficiaire et les étapes restent visibles et stables.
- Une animation est interrompue dès que l'utilisateur agit ; ne jamais bloquer une action derrière une animation.
- `prefers-reduced-motion` réduit transitions et animations à 0,01 ms et désactive le défilement fluide.
- Animer `opacity` et `transform` en priorité ; éviter les propriétés qui déclenchent un recalcul de mise en page.

## 8. Composants

### 8.1 Mise en page

Classes `.container-guest`, `.container-app`, `.container-wide`. Les pages existantes continuent d'utiliser `max-w-*` ; les nouvelles pages doivent préférer les conteneurs dédiés.

### 8.2 Bouton

Classes : `.btn` + `.btn-primary` | `.btn-secondary` | `.btn-ghost` | `.btn-danger` | `.btn-link`, plus `.btn-sm` | `.btn-lg`, `.btn-block`.

Composant :

```blade
<x-button :href="route('events.create')">Créer un événement</x-button>
<x-button type="submit" variant="secondary" size="sm">Annuler</x-button>
<x-button type="submit" variant="danger" :block="true">Supprimer</x-button>
```

- Un seul bouton principal par écran. `link` sert aux actions inline, `ghost` aux actions tertiaires.
- Hauteur minimale 44px pour toutes les tailles (`sm` compris) ; `lg` 52px.
- États : survol, appui, focus visible, désactivé (`disabled` ou `aria-disabled="true"`), chargement (`aria-busy="true"`).
- Chargement : spinner intégré, `pointer-events: none`, libellé conservé. Ne pas remplacer le libellé par le spinner seul.
- Pendant un paiement : désactivé, `aria-busy`, aucune animation décorative.

### 8.3 Champs et formulaires

Classes : `.field-group`, `.label`, `.help`, `.error-text`, `.field`, `.choice`.

- `label` toujours visible et associé par `for` / `id` ; le placeholder ne remplace jamais le libellé.
- Erreur : bordure `danger`, texte `.error-text` avec icône, `aria-invalid="true"` et `aria-describedby` vers le message.
- `focus-visible` : anneau 2px `focus-ring`, décalé de 2px.
- Désactivé : opacité 0,5, curseur interdit ; lecture seule : fond `surface`.
- Les cases et boutons radio utilisent `accent-color: var(--primary)` et une cible de 44px via `.choice`.
- Jamais de validation uniquement par la couleur.

### 8.4 Carte

Classes : `.card`, `.card-invitation`, `.card-interactive`, `.card-inset`.

- Fond `surface`, bordure `border`, rayon 2px.
- `card-invitation` ajoute `shadow-card` ; c'est la seule carte qui porte une ombre.
- `card-interactive` change uniquement la bordure au survol ; le curseur et le focus doivent rester cohérents avec l'élément interactif réel.

### 8.5 Pastille de statut

Classes : `.status-pill` + `.status-pill-neutral` | `-success` | `-warning` | `-danger`.

Composant :

```blade
<x-status-pill :tone="$guest->rsvp_status->tone()">{{ $guest->rsvp_status->label() }}</x-status-pill>
<x-status-pill tone="warning" icon="clock">En attente</x-status-pill>
<x-status-pill tone="danger" :icon="false">Rejeté</x-status-pill>
```

Icônes par défaut : `success` → `check`, `warning` → `alert`, `danger` → `x`, `neutral` → aucune. Surcharger avec `icon="clock"`, `icon="info"`, `icon="dot"`… ou supprimer avec `:icon="false"`. Un mot reste toujours présent.

### 8.6 Alerte

Classes : `.alert` + `.alert-info` | `-success` | `-warning` | `-danger`, `.alert-title`.

Composant :

```blade
<x-alert tone="success">Invitation envoyée.</x-alert>
<x-alert tone="danger" title="Paiement refusé">Vérifie ta carte puis réessaie.</x-alert>
```

`role="status"` par défaut ; `role="alert"` pour `danger`. L'icône est automatique (`info`, `check`, `alert`, `alert`) et peut être supprimée avec `:icon="false"`. Les alertes de session des layouts utilisent ce composant.

### 8.7 État vide

Composant :

```blade
<x-empty-state
    title="Aucun foyer invité"
    description="Ajoute les personnes que tu souhaites inviter."
    script="Merci"
>
    <x-button type="button">Ajouter un foyer</x-button>
</x-empty-state>
```

`title` est obligatoire. `script` est décoratif (`aria-hidden`) et limité à un par écran. Le slot reçoit les actions, centrées.

### 8.8 Progression

Composant :

```blade
<x-progress
    :value="$raised"
    :max="$goal"
    label="Cagnotte"
    :value-label="number_format($raised, 0, ',', ' ').' F CFA'"
/>
```

- `role="progressbar"`, `aria-valuemin`, `aria-valuemax`, `aria-valuenow` et `aria-label` fournis.
- La valeur est aussi affichée en texte (`valueLabel`) : la couleur ne porte jamais l'information seule.
- Barre `primary` sur piste `surface-muted`, hauteur 6px.
- Aucune animation pendant un paiement.

### 8.9 Tableau

Classes : `.table-wrap`, `.table`, `.num`, `.actions`.

- `.table-wrap` assure le défilement horizontal sur mobile.
- `caption` décrit le tableau ; chaque `th` porte `scope="col"` ou `scope="row"`.
- Montants et compteurs : `.num` (chiffres tabulaires, alignement à droite).
- Actions : `.actions`, alignées à droite, avec des boutons de 44px.

### 8.10 Dialogue

Classe `.dialog` sur un `<dialog>` natif. `showModal()` fournit le piège de focus et Échap ; le fond utilise `::backdrop`. L'ouverture est animée sur 250 ms. Ne pas animer un dialogue de paiement.

### 8.11 Pagination

Classe `.pagination`. Chaque lien ou page courante mesure au moins 44px ; l'état courant est signalé par `aria-current="page"`, pas seulement par la couleur.

### 8.12 Utilitaires

| Classe | Effet |
|---|---|
| `.measure` | Largeur de lecture 65ch. |
| `.measure-narrow` | Largeur 48ch pour l'invitation. |
| `.tabular` | Chiffres tabulaires. |
| `.rule` | Filet supérieur `border`. |
| `.rule-gold` | Filet supérieur or. |
| `.eyebrow` | Sur-titre capitales, 12px, interlettrage 0,16em. |
| `.reveal`, `.reveal-delay-1/2/3` | Apparition scénarisée ; une seule par écran. |
| `.sr-only-focusable` | Masqué jusqu'au focus ; utilisé par le lien d'évitement. |

## 9. Accessibilité

Cible : **WCAG 2.2 AA**.

- **Contraste** : texte ≥ 4,5:1, contrôles et focus ≥ 3:1 (matrice § 3.2).
- **Focus** : anneau 2px `focus-ring`, décalé de 2px, jamais supprimé. Le focus est immédiat.
- **Cibles tactiles** : 44px minimum, 8px d'espacement entre cibles principales. Les liens inline dans une phrase sont exemptés.
- **Structure** : un `h1` par page, hiérarchie de titres continue, landmarks `header`/`nav`/`main`/`footer`, lien d'évitement « Aller au contenu » dans les deux layouts.
- **Formulaires** : libellé visible, `autocomplete` pertinent, erreurs associées (`aria-invalid`, `aria-describedby`), message explicite.
- **Statuts** : mot + icône + couleur. Les messages de session utilisent `role="status"` ou `role="alert"`.
- **Tableaux** : `caption`, `scope`, en-têtes visibles ; ne pas utiliser la couleur seule pour signaler une ligne.
- **Dialogues** : `<dialog>` natif, titre associé par `aria-labelledby`, focus rendu au déclencheur à la fermeture.
- **Mouvement** : `prefers-reduced-motion` respecté ; aucune animation pendant un paiement.
- **Zoom et reflux** : lisible à 200 % et utilisable à 320px sans défilement horizontal (hors tableaux, qui défilent dans `.table-wrap`).
- **Langue** : `lang="fr"` sur `<html>`.

## 10. Thèmes d'événement et pages

- Le thème clair est la référence de l'espace invité. Le thème sombre est prévu pour l'espace organisateur, mais n'est pas encore activé automatiquement.
- L'accent d'événement se pose une fois, sur un ancêtre de la page : `<div class="{{ $event->type->accentClass() }}">`. Tous les composants héritent de `--primary`.
- Ne jamais combiner deux classes `ev-*` sur un même écran.
- Le QR code conserve un fond crème `#fffaf2` même en thème sombre : c'est une exception volontaire pour garantir la lecture par les lecteurs. Il ne doit pas être remplacé par `surface`.
- Les e-mails ne font pas partie de ce périmètre : ils gardent leurs couleurs et Georgia (voir § 12).

## 11. Conventions d'implémentation

### 11.1 Fichiers

- Jetons : `design-system/tokens.json` (source de vérité, versionné).
- CSS : `resources/css/app.css` — primitives, sémantique clair/sombre, accents, `@theme inline`, base, composants, utilitaires, keyframes.
- Blade : `resources/views/components/*.blade.php`.
- Documentation : ce fichier.

### 11.2 Nouvelles pages

```blade
<div class="container-app py-12">
    <p class="eyebrow">Invités</p>
    <h1 class="mt-2 font-display text-display-lg text-ink">Foyers</h1>

    <div class="card mt-8 p-6">
        <x-alert tone="info" class="mb-4">Les invitations partent à 9 h.</x-alert>
        <!-- contenu -->
    </div>
</div>
```

Règles : conteneur dédié, titres display, texte `ink`/`ink-muted` (ou sémantique), cartes `.card`, actions `.btn` ou `<x-button>`, statuts `<x-status-pill>`, messages `<x-alert>`.

### 11.3 Migration

- Les alias legacy permettent aux vues existantes de fonctionner sans modification.
- Toute nouvelle vue utilise les utilitaires sémantiques (`bg-surface`, `text-foreground`, `border-border-strong`…).
- Toute vue modifiée pour une autre raison migre ses pastilles vers `<x-status-pill>` et ses boutons vers `<x-button>` si le balisage est simple.
- Ne pas ajouter de nouvelle classe legacy ; elles disparaissent en v3.

### 11.4 Interdits

- Hex ou palette Tailwind brute dans une vue (exception documentée : QR).
- Couleur seule pour un état, une erreur ou une sélection.
- Animation pendant un paiement.
- Deux accents d'événement sur le même écran.
- Deux moments `script` sur le même écran.
- Suppression de l'anneau de focus.

## 12. Gouvernance, écarts connus et livraison

### 12.1 Écarts connus

1. Les e-mails codent les couleurs en dur et utilisent Georgia au lieu de la pile display ; migration à planifier.
2. Le thème sombre est câblé via `data-theme` mais n'est pas activé automatiquement pour l'organisateur.
3. `assets/inspiration/brief.md`, `MAQUETTES.md` et `PLAN.md` sont référencés mais absents du dépôt.
4. Certaines vues utilisent encore `.status-pill` directement, sans icône ; migration progressive vers `<x-status-pill>`.
5. Le lien-pastille du tableau de bord (`a.status-pill`) mesure 24px de haut ; prévoir une zone tactile de 44px ou un bouton texte.
6. Le fond du QR code reste codé en dur (`#fffaf2`) pour garantir le scan.
7. Les polices sont chargées depuis Google Fonts ; l'auto-hébergement reste à évaluer.
8. Aucune régression visuelle automatisée : le contrôle en navigateur reste manuel.
9. Les alias legacy restent supportés jusqu'à une v3.

### 12.2 Processus de modification

1. Décrire le besoin et le rôle sémantique visé.
2. Mettre à jour `tokens.json` (primitive si nécessaire, puis sémantique, puis composant).
3. Mettre à jour `app.css` et, si le balisage ou l'ARIA change, le composant Blade.
4. Vérifier les contrastes dans les deux thèmes et sur les trois surfaces.
5. Mettre à jour ce document et la liste des écarts.
6. Tester au clavier, à 375px, 768px et 1024px, puis avec `prefers-reduced-motion`.

### 12.3 Contrôles de livraison

Vérifications effectuées le 27 octobre 2026 : build Vite, 121 tests Pest (506 assertions), compilation de toutes les vues Blade, matrice de contraste, rendu Chrome à 375, 768 et 1024px, focus clavier, thème sombre et `prefers-reduced-motion`. Aucune erreur console ni débordement horizontal détecté.

- [x] `npm install && npm run build` passe sans erreur.
- [x] La matrice de contraste est recalculée après toute modification de couleur.
- [x] La suite Pest `Feature` passe (la suite `Unit` n'existe pas dans le dépôt).
- [x] Rendu vérifié dans un navigateur à 375px, 768px et 1024px.
- [x] Focus clavier, anneau visible et lien d'évitement vérifiés sur les layouts et la connexion.
- [x] `prefers-reduced-motion` vérifié (transitions à 0,01ms, défilement auto).
- [x] Thème sombre vérifié par `data-theme="dark"`.
- [x] Aucune couleur brute dans les vues (hors QR et e-mails).
- [x] Statuts migrés avec mot + icône ; accent unique vérifié sur l'événement et l'invitation.
- [ ] Parcours clavier complet de tous les écrans (au-delà des layouts et de la connexion).
- [ ] Aucune animation pendant le parcours de paiement : règle à revérifier lors de l'implémentation du paiement, hors périmètre v2.
