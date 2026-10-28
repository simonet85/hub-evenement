# Phase 10 — Tableau de bord et exports traiteur

## Objectif

L'organisateur pilote son événement depuis une page dédiée : réponses, couverts, régimes, évolution des réponses et files de modération. Il exporte les invités en CSV et Excel, produit un dossier PDF pour le traiteur, et partage un lien de lecture seule limité dans le temps. Le traiteur consulte les couverts et les régimes sans jamais voir les coordonnées des foyers.

## Décisions de cadrage

- **Page dédiée** : le pilotage vit sur `/events/{événement}/pilotage`, relié depuis la page de l'événement. La page événement garde la gestion des foyers, du programme et des liens.
- **Couverts = places confirmées** : un foyer présent compte `rsvp_seats` couverts ; un foyer absent ou en attente compte zéro couvert confirmé. Les places prévues restent la somme des `allowed_seats`.
- **Régimes en texte libre** : le RSVP enregistre déjà `dietary_notes` (500 caractères). Le pilotage et le dossier traiteur listent ces notes par foyer, sans catégorisation automatique.
- **Évolution** : nombre de réponses par jour sur les 14 derniers jours, à partir de `rsvp_responded_at`, avec un cumul depuis le début.
- **Trois familles d'exports** :
  - invités : CSV et XLSX complets, avec coordonnées, réponse, couverts, régimes et pointage ;
  - couverts : CSV et XLSX sans coordonnées, destinés au traiteur ;
  - dossier traiteur : PDF paysage avec synthèse et foyers présents.
- **Lien traiteur signé** : URL temporaire signée, sans compte. Durée choisie par l'organisateur : 24 heures, 7 jours (par défaut) ou 30 jours. Les exports téléchargés depuis ce lien portent la même expiration. Aucun jeton stocké en base : l'expiration suffit pour la V1.
- **Confidentialité** : le portail traiteur et ses exports n'affichent ni e-mail, ni téléphone, ni message privé, ni jeton d'invitation.
- **Bibliothèques** : `openspout/openspout` pour le XLSX, `barryvdh/laravel-dompdf` pour le PDF. Le CSV est écrit nativement.

## User stories

- En tant qu'organisateur, je vois combien de foyers ont répondu oui, non ou pas encore, et combien de couverts sont confirmés.
- En tant qu'organisateur, je vois la répartition des régimes et allergies signalés par les foyers présents.
- En tant qu'organisateur, je vois l'évolution des réponses sur les 14 derniers jours.
- En tant qu'organisateur, je vois les photos, messages et paiements en attente de modération.
- En tant qu'organisateur, je télécharge la liste complète des invités en CSV ou Excel.
- En tant qu'organisateur, je génère un lien traiteur valable 24 heures, 7 jours ou 30 jours.
- En tant que traiteur, j'ouvre ce lien sans compte, je consulte les couverts et régimes, et je télécharge le dossier PDF ou les couverts en CSV/Excel.
- En tant que traiteur, mon lien expire automatiquement et ne donne accès à aucune coordonnée.

## Règles

### Indicateurs

- Foyers : total, présents, absents, en attente (non répondu).
- Places prévues : somme des `allowed_seats`.
- Couverts confirmés : somme des `rsvp_seats` des foyers présents.
- Réponses : foyers ayant répondu, dont réponses hors délai.
- Régimes : foyers présents dont `dietary_notes` n'est pas vide.
- Modération : photos en attente, messages en attente, paiements en attente non expirés.
- Évolution : 14 jours glissants, y compris les jours sans réponse ; le cumul inclut les réponses antérieures à la fenêtre.
- Les indicateurs sont calculés à partir des foyers de l'événement, sans requête par foyer.

### Exports invités (organisateur)

- Format CSV : séparateur point-virgule, UTF-8 avec BOM, une ligne d'en-tête.
- Format XLSX : feuille « Invités » avec les mêmes colonnes, et feuille « Synthèse » avec les indicateurs.
- Colonnes : foyer, e-mail, téléphone, places autorisées, réponse, places confirmées, prénoms, régime/allergies, message, date de réponse, réponse hors délai, pointage, pointé par.
- Les valeurs sont échappées : une cellule texte commençant par `=`, `+`, `-` ou `@` est préfixée par une apostrophe pour empêcher l'exécution d'une formule.
- Les exports sont réservés aux membres de l'événement (propriétaire et co-organisateurs) ; un tiers reçoit 403.

### Exports couverts et PDF (traiteur)

- CSV/XLSX couverts : foyers présents uniquement, colonnes foyer, couverts, prénoms, régime/allergies, sans coordonnées.
- XLSX couverts : feuille « Couverts » et feuille « Synthèse ».
- PDF : A4 paysage, en-tête événement (nom, date, lieu), synthèse, tableau des foyers présents avec couverts, prénoms et régimes, date de génération, mention confidentielle.
- Les exports couverts et le PDF sont accessibles à l'organisateur authentifié et au traiteur via une URL signée.
- Un format inconnu renvoie 404.

### Lien traiteur

- Génération depuis la page pilotage par un membre de l'événement.
- Durées autorisées : 1, 7 ou 30 jours ; valeur par défaut 7 jours ; toute autre valeur est refusée (422).
- URL signée `GET /traiteur/{événement}?expires=…&signature=…`, sans authentification.
- Sans signature, signature altérée ou expirée : 403.
- La page est `noindex, nofollow`, sans `Referer`.
- Les liens de téléchargement sont signés avec la même expiration que la page.
- La page affiche la date et l'heure d'expiration.
- Une nouvelle génération produit un nouveau lien ; l'ancien reste valable jusqu'à son expiration.

### Accès et confidentialité

- Le pilotage et les exports invités sont réservés aux membres de l'événement.
- Le portail traiteur ne révèle ni e-mail, ni téléphone, ni message RSVP, ni jeton.
- Toutes les pages sont non indexables ; les liens traiteur utilisent `no-referrer`.
- Les textes libres (régimes, prénoms, noms) sont échappés à l'affichage HTML et dans le PDF.

## Hors périmètre

- Catégories de régime structurées, allergies déclarées séparément et comptage par type.
- Plan de table et affectation des convives.
- Envoi du lien traiteur par e-mail ou WhatsApp depuis l'application.
- Révocation manuelle d'un lien avant expiration, journal d'accès ou authentification du traiteur.
- Graphiques interactifs et export image.
- Modification des réponses par l'organisateur.

## Critères d'acceptation

- La page pilotage affiche les foyers présents, absents et en attente, les couverts confirmés, les places prévues, les régimes et les files de modération.
- L'évolution couvre 14 jours, affiche les jours sans réponse et un cumul correct.
- Le CSV invités contient le BOM, le séparateur `;`, les accents et les colonnes attendues ; une valeur `=…` est neutralisée.
- Le XLSX invités contient les feuilles « Invités » et « Synthèse » ; le XLSX couverts ne contient aucune coordonnée.
- Le PDF est téléchargeable, commence par `%PDF` et ne contient que les foyers présents.
- Le lien traiteur s'ouvre sans compte, affiche couverts et régimes, et ne contient ni e-mail ni téléphone.
- Les exports du portail traiteur fonctionnent avec la signature et échouent sans elle.
- Un lien expiré ou altéré renvoie 403.
- Un tiers ne peut ni ouvrir le pilotage, ni télécharger les exports invités (403).
- La génération du lien refuse une durée non autorisée.
