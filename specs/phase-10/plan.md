# Plan — Phase 10

## Étapes

1. **Métriques** : `App\Services\EventMetrics` calcule les compteurs RSVP, couverts, régimes et l'évolution sur 14 jours à partir des foyers déjà chargés.
2. **Exporteur** : `App\Services\Exports\EventExporter` produit les lignes invités et couverts, le CSV, le XLSX (OpenSpout) et le PDF (Dompdf).
3. **Contrôleurs et routes** : page pilotage, génération du lien traiteur, exports authentifiés, portail traiteur signé et exports signés.
4. **Vues** : page pilotage complète, portail traiteur en lecture seule, dossier PDF.
5. **Tests** : métriques et évolution, autorisations, en-têtes et contenu des exports, neutralisation des formules, signatures et expiration, confidentialité du portail.
6. **Validation** : Pest, Pint, build, parcours navigateur et captures, README et `validation.md`.

## Fichiers

```
app/Services/EventMetrics.php
app/Services/Exports/EventExporter.php
app/Http/Controllers/PilotageController.php
app/Http/Controllers/ExportController.php
app/Http/Controllers/CatererController.php
resources/views/pilotage/show.blade.php
resources/views/caterer/show.blade.php
resources/views/exports/caterer-pdf.blade.php
resources/views/layouts/invitation.blade.php   (conteneur et eyebrow variables)
resources/views/events/show.blade.php          (bouton Pilotage)
tests/Feature/PilotageTest.php
tests/Feature/CatererTest.php
specs/phase-10/requirements.md
specs/phase-10/plan.md
specs/phase-10/validation.md
```

## Données et calculs

- `EventMetrics::summary()` : foyers, places, réponses, présents, absents, en attente, couverts, régimes, réponses hors délai.
- `EventMetrics::evolution(14)` : jours de la fenêtre, nombre de réponses, cumul, maximum.
- `EventMetrics::attending()` / `dietary()` : foyers présents et notes de régime.
- `Guest::confirmedSeats()` : `rsvp_seats` si présent, sinon 0.
- Les files de modération (photos, messages, paiements) restent des requêtes agrégées du contrôleur.

## Routes

| Méthode | URI | Nom | Accès |
|---|---|---|---|
| GET | `/events/{event}/pilotage` | `pilotage.show` | membres |
| POST | `/events/{event}/pilotage/lien-traiteur` | `pilotage.caterer-link` | membres |
| GET | `/events/{event}/exports/invites.csv` | `exports.guests.csv` | membres |
| GET | `/events/{event}/exports/invites.xlsx` | `exports.guests.xlsx` | membres |
| GET | `/events/{event}/exports/traiteur.pdf` | `exports.caterer.pdf` | membres |
| GET | `/traiteur/{event}` | `caterer.show` | URL signée |
| GET | `/traiteur/{event}/exports/{format}` | `caterer.export` | URL signée |

Formats du portail : `csv`, `xlsx`, `pdf`. Le portail réutilise l'expiration de sa propre URL pour signer les téléchargements.

## Détails techniques

### CSV

- BOM `\xEF\xBB\xBF`, séparateur `;`, fins de ligne `\r\n`.
- Neutralisation des cellules commençant par `=`, `+`, `-`, `@`, tabulation ou retour chariot.
- En-têtes fixes, dates au format `d/m/Y H:i`.

### XLSX

- `OpenSpout\Writer\XLSX\Writer` vers un fichier temporaire, puis contenu renvoyé en mémoire.
- En-tête en gras, lignes gelées, largeurs de colonnes adaptées.
- Feuille « Invités » ou « Couverts », puis « Synthèse ».

### PDF

- `Barryvdh\DomPDF\Facade\Pdf::loadView('exports.caterer-pdf', …)`, A4 paysage, police DejaVu Sans.
- Aucune ressource distante ; CSS simple compatible Dompdf.
- En-tête, synthèse, tableau des foyers présents, pied de page confidentiel.

### Lien traiteur

- `URL::temporarySignedRoute('caterer.show', now()->addDays($days), ['event' => $event])`.
- Durées validées : `1`, `7`, `30`.
- La page lit `expires` (garanti par la signature) et signe les exports avec ce timestamp.
- Affichage dans la session après redirection : `caterer_link`, `caterer_expires_at`.

### Sécurité

- `Gate::authorize('view', $event)` sur le pilotage et les exports invités.
- Middleware `signed` sur le portail et ses exports, contrainte `csv|xlsx|pdf`.
- `noindex`, `no-referrer`, aucune coordonnée dans les vues traiteur.
- Échappement Blade et neutralisation CSV.

## Tests

### PilotageTest

- Indicateurs exacts avec présents, absents, en attente, régimes et hors délai.
- Évolution : fenêtre de 14 jours, jours vides, cumul incluant les réponses antérieures.
- Accès propriétaire et co-organisateur ; tiers 403 ; visiteur redirigé.
- Bouton « Pilotage et exports » sur l'événement.
- Génération du lien 1/7/30 jours, durée invalide, tiers refusé.
- CSV invités : BOM, en-tête, valeurs, accents, formule neutralisée.
- XLSX invités : signature `PK`, feuilles « Invités » et « Synthèse ».
- PDF : `%PDF`, en-tête de téléchargement.
- Exports refusés à un tiers.

### CatererTest

- Lien signé accessible sans compte, `noindex`, couverts/régimes, sans e-mail ni téléphone.
- URL non signée, altérée ou expirée : 403.
- Exports CSV/XLSX/PDF signés, sans coordonnées ; signature absente ou expirée : 403.
- Format inconnu : 404.
- Les liens d'export reprennent l'expiration de la page.

## Risques

- **Dompdf et CSS moderne** : utiliser un gabarit dédié, tableaux et styles simples.
- **Mémoire XLSX** : volume plafonné par la V1 (400 invités) ; fichier temporaire supprimé après lecture.
- **Fuseau et dates** : utiliser le fuseau applicatif et `translatedFormat` pour les libellés.
- **Confidentialité** : tests explicites d'absence d'e-mail et de téléphone dans les vues et exports traiteur.
