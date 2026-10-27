# Phase 2 — Événements, foyers et invités

## Objectif

Un organisateur connecté crée un ou plusieurs événements, y ajoute ses foyers manuellement ou par import CSV, et invite des co-organisateurs à l'aider.

## User stories

- En tant qu'organisateur, je crée un événement (nom, type, date, lieu, date limite de réponse) et j'arrive directement sur sa page.
- En tant qu'organisateur, je retrouve tous mes événements sur mon espace, avec le nombre de foyers.
- En tant qu'organisateur, j'ajoute un foyer (nom, e-mail, téléphone, nombre de places) à un événement.
- En tant qu'organisateur, j'importe une liste de foyers au format CSV et je vois combien de lignes ont été importées et ignorées.
- En tant qu'organisateur, je supprime un foyer saisi par erreur.
- En tant qu'organisateur, j'ajoute un co-organisateur par son adresse e-mail ; il voit l'événement et gère les foyers, mais ne modifie pas l'événement.
- En tant que co-organisateur, je vois uniquement les événements auxquels je suis rattaché.

## Règles

- Un compte peut posséder plusieurs événements.
- Le propriétaire est seul à pouvoir modifier l'événement et gérer les co-organisateurs.
- Propriétaire et co-organisateurs peuvent consulter l'événement et gérer les foyers.
- Un foyer accueille entre 1 et 20 places.
- Les types d'événement sont : mariage, baptême, anniversaire, fête ; le type détermine l'accent visuel.
- L'import CSV accepte les colonnes `nom, e-mail, téléphone, places` (virgule ou point-virgule), avec ou sans ligne d'en-tête. Les lignes invalides sont ignorées et signalées avec leur numéro.
- Un utilisateur inconnu reçoit une 403 sur un événement auquel il n'appartient pas.

## Hors périmètre

- Jeton d'invitation, RSVP, QR code et pointage (Phase 3).
- Programme, carte et livre d'or (Phases 4 à 7).
- Rôle traiteur et exports (Phase 10).
- Envoi d'e-mails d'invitation ou de relance.

## Critères d'acceptation

- Un organisateur crée un événement et importe 50 foyers sans erreur.
- Les lignes invalides d'un CSV sont signalées, les lignes valides sont enregistrées.
- Un co-organisateur accède à l'événement et ajoute un foyer ; il ne peut ni modifier l'événement ni gérer les co-organisateurs.
- Un tiers ne voit ni la liste ni le détail d'un événement qui ne lui appartient pas.
