# Phase 4 — QR code d'entrée et pointage

## Objectif

Chaque foyer reçoit un QR code personnel, intégré à son invitation. Le jour de l'événement, le propriétaire ou un co-organisateur ouvre une page de scan sur son mobile, scanne le QR et enregistre l'entrée du foyer. Le pointage est immédiat, visible dans les compteurs et annulable en cas d'erreur.

## User stories

- En tant qu'organisateur, je télécharge ou transmets l'invitation avec le QR d'entrée du foyer.
- En tant qu'invité, je présente le QR de mon invitation à l'entrée.
- En tant qu'organisateur ou co-organisateur, j'ouvre la page de scan depuis l'événement et je scanne les QR.
- En tant qu'agent d'entrée, je vois le foyer, son statut RSVP, ses places et ses prénoms avant de valider.
- En tant qu'agent, je peux pointer un foyer non confirmé ou déclaré absent après confirmation explicite.
- En tant qu'organisateur, je vois combien de foyers et de couverts sont entrés, et je peux annuler un pointage.

## Règles

- Chaque foyer possède un `entry_token` aléatoire de 48 caractères, unique et distinct du jeton d'invitation. Les foyers existants en reçoivent un lors de la migration.
- Le QR encode l'URL absolue `/check-in/{entry_token}`. Il est généré côté serveur en SVG, sans service externe, et peut être téléchargé.
- Le QR est affiché dans l'invitation du foyer avec un lien de téléchargement. L'organisateur peut aussi télécharger le QR de chaque foyer.
- Le pointage est réservé aux comptes qui gèrent l'événement (propriétaire et co-organisateurs). Un visiteur non connecté est redirigé vers la connexion, puis revient au pointage.
- Un jeton inconnu ou appartenant à un autre événement est invalide : aucun pointage n'est écrit.
- Le premier pointage enregistre l'horodatage et l'auteur. Un scan répété affiche « déjà pointé » avec l'heure et ne remplace pas l'horodatage.
- L'annulation du pointage est réservée aux comptes qui gèrent l'événement ; elle efface l'horodatage et l'auteur.
- Un foyer présent confirmé est pointé directement. Un foyer sans réponse ou déclaré absent n'est pointé qu'après une confirmation explicite de l'agent.
- La page de scan propose la caméra et une saisie manuelle du code ou de l'URL. La caméra nécessite HTTPS ou `localhost` ; en cas d'indisponibilité, la saisie manuelle reste utilisable.
- Les compteurs affichent les foyers pointés sur le total, et les couverts pointés. Pour un foyer présent, les couverts sont ses places confirmées ; sinon, ses places autorisées.
- La page de pointage directe est non indexable et n'envoie pas le jeton dans le `Referer`.
- Le scan et le pointage direct fonctionnent après la date limite de réponse : le pointage concerne le jour de l'événement.

## Hors périmètre

- Comptes ou lien dédiés au personnel d'entrée (le pointage passe par les comptes de l'événement).
- Scan hors ligne, synchronisation différée ou file de scans.
- Impression d'un billet ou d'un PDF, QR sur badge.
- Historique détaillé des scans, journal d'audit ou multi-entrées d'un même foyer.
- Ajustement du nombre de personnes à l'entrée ou saisie d'un nom non prévu.

## Critères d'acceptation

- Chaque foyer possède un jeton d'entrée unique et un QR SVG téléchargeable, visible dans son invitation.
- Un propriétaire ou co-organisateur scanne ou saisit un code et voit le pointage enregistré avec les compteurs mis à jour.
- Un foyer sans réponse ou décliné n'est pointé qu'après confirmation.
- Un scan répété affiche l'heure du premier pointage ; l'annulation efface le pointage.
- Un jeton inconnu ou d'un autre événement est refusé sans écriture.
- Un visiteur non connecté est redirigé vers la connexion ; un tiers reçoit 403.
- La page de pointage directe est utilisable sans JavaScript et non indexable.
