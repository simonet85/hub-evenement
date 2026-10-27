# Phase 7 — Livre d'or

## Objectif

Les foyers signent un livre d'or depuis leur invitation personnelle. Les messages passent par une file de modération et n'apparaissent aux autres invités qu'après validation par le propriétaire ou un co-organisateur.

## User stories

- En tant qu'invité, je signe le livre d'or depuis mon invitation avec mon nom et un message.
- En tant qu'invité, je lis sur mon invitation les messages publiés par les autres foyers.
- En tant qu'organisateur, je vois les messages en attente et je les approuve, les masque ou les rejette.
- En tant que co-organisateur, je modère le livre d'or comme le propriétaire.
- En tant qu'organisateur, je vois le nombre de messages à modérer sur l'événement et le tableau de bord.

## Règles

- Le dépôt est réservé aux foyers munis de leur jeton d'invitation (`POST i/{token}/livre-dor`) ; aucun compte n'est requis.
- Champs : nom affiché (80 caractères, prérempli avec le nom du foyer) et message (1 000 caractères). Les espaces de début et de fin sont retirés.
- Tout message est créé à l'état **en attente** ; aucun message n'est visible avant approbation.
- États : `pending` → `approved` → `hidden` ; `rejected` depuis n'importe quel état ; un message masqué ou rejeté peut être approuvé à nouveau. Chaque modération enregistre son auteur et sa date.
- Le message reste rattaché au foyer pour la traçabilité, mais l'invitation publiée n'affiche que le nom saisi.
- Anti-spam : champ leurre (honeypot) et limitation à 5 dépôts par minute et par adresse IP. Un honeypot rempli est ignoré silencieusement : aucune écriture, mais la même confirmation que pour un dépôt valide.
- Les messages publiés sont affichés du plus récent au plus ancien sur l'invitation, paginés par 10.
- La modération est réservée au propriétaire et aux co-organisateurs. Un tiers reçoit 403 ; un message appartenant à un autre événement renvoie 404.
- Les pages organisateur sont non indexables et utilisables sans JavaScript.
- Aucun e-mail n'est envoyé : les compteurs de messages en attente suffisent.

## Hors périmètre

- Formulaire public sans jeton ou avec nom libre.
- Réponses aux messages, réactions, photos ou vidéos.
- Notification e-mail aux organisateurs ou aux invités.
- Suppression définitive, signalement par les invités et filtre automatique de contenu.
- Export ou impression du livre d'or.
- Choix par l'organisateur d'activer ou non le livre d'or.

## Critères d'acceptation

- Un message déposé apparaît en attente chez les organisateurs et n'apparaît pas sur l'invitation.
- Une fois approuvé, il est visible sur toutes les invitations ; masqué, il disparaît ; rejeté, il reste invisible.
- Un dépôt avec honeypot rempli ou un sixième dépôt en une minute n'enregistre aucun message.
- Le nom et le message sont échappés (aucune injection HTML).
- Les compteurs d'attente sont exacts sur l'événement et le tableau de bord.
- Les tests Pest couvrent dépôt, validation, anti-spam, transitions de modération, autorisations, échappement et affichage.
