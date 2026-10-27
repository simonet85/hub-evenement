# Phase 3 — Invitation publique et RSVP

## Objectif

Chaque foyer reçoit un lien personnel et secret vers son invitation. Sans créer de compte, il consulte l'événement et répond : présence, nombre de personnes, prénoms, régime alimentaire et message. L'organisateur suit les réponses depuis la page de l'événement.

## User stories

- En tant qu'organisateur, je copie le lien personnel d'un foyer depuis la page de l'événement.
- En tant qu'organisateur, je peux regénérer le lien d'un foyer : l'ancien lien est immédiatement révoqué.
- En tant qu'invité, j'ouvre mon lien sans compte et je vois le nom de mon foyer, la date, le lieu et mes places.
- En tant qu'invité, je réponds depuis mon mobile et je reçois une confirmation.
- En tant qu'invité, je corrige ma réponse jusqu'à la date limite.
- En tant qu'organisateur, je vois qui a répondu, le nombre de couverts, les prénoms, les régimes et les messages.

## Règles

- Le jeton est une chaîne aléatoire de 48 caractères, unique, stockée en base. Regénérer le jeton remplace l'ancien : l'ancienne URL renvoie 404.
- La page d'invitation est publique, non indexable, et ne révèle que le foyer concerné.
- La présence vaut oui ou non. Si oui, le foyer indique entre 1 et `allowed_seats` personnes, et au plus autant de prénoms.
- Une réponse est modifiable tant que la date limite n'est pas dépassée.
- Après la date limite, une première réponse reste possible : elle est marquée « hors délai ». Une réponse déjà enregistrée n'est plus modifiable après la date limite.
- Sans date limite, la réponse reste modifiable.
- Un reçu e-mail est mis en file si le foyer a une adresse e-mail.
- Le QR code d'entrée et le pointage restent en Phase 4.

## Hors périmètre

- QR code, scan et pointage (Phase 4).
- Programme, carte et itinéraire (Phase 5).
- Relances WhatsApp et e-mails de rappel (Phase 6).
- Livre d'or, galerie, cadeaux et cagnotte (Phases 7 à 9).
- Envoi groupé des invitations par e-mail.

## Critères d'acceptation

- Un foyer répond depuis son lien sur mobile ; l'organisateur voit la réponse et les couverts.
- Un lien regénéré renvoie 404 et le nouveau lien fonctionne.
- Une réponse hors délai est acceptée et signalée.
- Un foyer ne peut pas dépasser ses places autorisées.
- Le reçu e-mail est émis pour chaque réponse d'un foyer ayant une adresse.
