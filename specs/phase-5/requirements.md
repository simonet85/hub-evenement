# Phase 5 — Programme et carte

## Objectif

Le propriétaire décrit le déroulé de la journée en étapes horaires et place le lieu sur une carte OpenStreetMap. Chaque foyer consulte le programme, le code vestimentaire et la carte depuis son invitation, avec des itinéraires Google Maps et Waze.

## User stories

- En tant qu'organisateur, j'ajoute les étapes du programme (heure, titre, lieu, description) et je les réordonne.
- En tant qu'organisateur, je définis un code vestimentaire commun à l'événement.
- En tant qu'organisateur, je place le repère du lieu sur une carte pour que les invités trouvent la salle.
- En tant qu'invité, j'ouvre le programme depuis mon invitation et je vois la frise horaire, le code vestimentaire et la carte.
- En tant qu'invité, je lance l'itinéraire dans Google Maps ou Waze depuis la page programme.
- En tant que co-organisateur, je gère le programme sans pouvoir modifier les réglages de l'événement.

## Règles

- Une étape possède une heure de début (`H:i`), une heure de fin facultative, un titre, un lieu facultatif et une description facultative.
- Les étapes sont affichées dans l'ordre défini par l'organisateur, pas triées automatiquement par heure.
- Le propriétaire et les co-organisateurs peuvent ajouter, modifier, réordonner et supprimer les étapes. Un tiers reçoit 403 ; une étape d'un autre événement renvoie 404.
- Le code vestimentaire est un texte court global à l'événement, modifiable par le propriétaire seul. Il apparaît sur l'invitation et la page programme.
- Les coordonnées du lieu (`latitude`, `longitude`) sont facultatives, choisies en cliquant sur une carte OpenStreetMap dans le formulaire d'édition. Elles peuvent aussi être saisies manuellement.
- Les coordonnées sont validées : latitude entre -90 et 90, longitude entre -180 et 180, les deux renseignées ensemble.
- La page programme est accessible par le jeton d'invitation : `/i/{token}/programme`. Même confidentialité que l'invitation, non indexable et sans `Referer`.
- La carte est affichée avec Leaflet et les tuiles OpenStreetMap, centrée sur le repère, sans zoom à la molette sur mobile.
- Les boutons Google Maps et Waze sont construits depuis les coordonnées. Sans coordonnées, la carte est absente, Google Maps recherche l'adresse et Waze n'est pas proposé.
- La page programme reste lisible sans JavaScript : étapes, code vestimentaire, adresse et liens d'itinéraire sont rendus côté serveur.

## Hors périmètre

- Géocodage automatique d'adresse (Nominatim ou autre service).
- Plan de salle, plan de table ou itinéraire interne au site.
- Programme multi-jours avec regroupement par journée.
- Code vestimentaire par étape ou par foyer.
- Jeton public distinct de l'invitation pour partager le programme aux prestataires.
- Export calendrier (.ics), rappels ou notifications de programme.
- Carte hors ligne ou fond de carte personnalisé.

## Critères d'acceptation

- L'organisateur ajoute trois étapes, en modifie une, en réordonne une et en supprime une ; l'ordre affiché correspond.
- Un co-organisateur gère le programme ; un tiers est refusé ; une étape d'un autre événement renvoie 404.
- Le code vestimentaire et les coordonnées sont enregistrés par le propriétaire ; les valeurs hors bornes sont refusées.
- La page programme par jeton affiche la frise, le code vestimentaire, la carte et les liens Google Maps/Waze.
- Sans coordonnées, la page affiche l'adresse et un lien Google Maps, sans Waze ni carte.
- La page est non indexable, sans `Referer`, lisible à 390 px sans débordement et utilisable sans JavaScript.
- Le build Vite inclut Leaflet et les tests Pest couvrent les autorisations et les cas limites.
