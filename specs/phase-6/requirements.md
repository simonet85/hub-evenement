# Phase 6 — Relances

## Objectif

L'organisateur relance par e-mail les foyers qui n'ont pas encore répondu, en un envoi groupé limité en débit, et dispose d'un lien WhatsApp pré-rempli par foyer. Chaque e-mail contient un lien de désinscription propre à l'événement, et l'historique des relances est visible.

## User stories

- En tant qu'organisateur, je vois les foyers sans réponse et je sélectionne ceux à relancer.
- En tant qu'organisateur, je lance un envoi groupé ; les e-mails partent en file d'attente sans bloquer la page.
- En tant qu'organisateur, je vois pour chaque foyer le nombre de relances restantes, la dernière relance et une éventuelle désinscription.
- En tant qu'organisateur, j'ouvre ou je copie un message WhatsApp pré-rempli pour un foyer.
- En tant qu'invité, je reçois un e-mail de relance avec le lien de mon invitation.
- En tant qu'invité, je peux refuser les relances de cet événement sans perdre l'accès à mon invitation.
- En tant que co-organisateur, je gère les relances comme les foyers.

## Règles

- L'envoi est manuel et groupé : l'organisateur coche des foyers sur la page Relances puis confirme. Aucune relance automatique planifiée.
- Seuls les foyers sans réponse, non désinscrits et disposant d'un e-mail peuvent recevoir une relance.
- Un foyer reçoit au plus **2 relances** par e-mail, espacées d'au moins **24 heures**. Les relances en file comptent dans la limite.
- Les envois d'un lot passent par la file `database` : un seul worker les traite l'un après l'autre, sans délai artificiel (un délai par job serait traité en vrac au passage suivant d'un cron `queue:work --stop-when-empty`). La page de confirmation indique le nombre mis en file et le nombre ignoré.
- Le message WhatsApp est un lien `wa.me` pré-rempli (nom, événement, date, lieu, lien d'invitation). Il est proposé quand le foyer a un téléphone. Il n'est pas compté comme une relance e-mail.
- Le lien de désinscription est signé et propre à l'événement : `GET` affiche une confirmation, `POST` enregistre le refus. La désinscription est idempotente.
- Un foyer désinscrit garde l'accès à son invitation, au programme et au RSVP ; il est seulement exclu des relances.
- Le propriétaire et les co-organisateurs accèdent aux relances. Un tiers reçoit 403 ; un foyer d'un autre événement renvoie 404.
- Le téléphone est normalisé pour `wa.me` : chiffres uniquement, `00` initial retiré, indicatif `225` ajouté aux numéros locaux (10 chiffres ou moins).
- Les pages organisateur restent utilisables sans JavaScript ; la sélection multiple fonctionne avec des cases à cocher.
- La page Relances est non indexable.

## Hors périmètre

- Envoi automatique planifié (cron de relance) et règles de date relatives à l'événement.
- Envoi de SMS ou intégration à l'API WhatsApp Business.
- Personnalisation du texte de relance par l'organisateur.
- Historique détaillé par lot (ouverture, clic, bounce) ou webhooks fournisseur.
- Relance des foyers ayant répondu « absent ».
- Désinscription globale à la plateforme.

## Critères d'acceptation

- Un envoi groupé crée une relance par foyer éligible et met les e-mails en file, traités séquentiellement par le worker.
- Un foyer sans e-mail, déjà désinscrit ou ayant répondu est ignoré ; le message de retour distingue envoyés et ignorés.
- La troisième relance est refusée, et une seconde relance moins de 24 heures après la première est refusée.
- L'e-mail contient l'invitation personnelle et un lien de désinscription signé.
- Après désinscription, le foyer disparaît des relances mais conserve l'accès à son invitation.
- Le lien WhatsApp contient le numéro normalisé et le message encodé avec le lien d'invitation.
- Les tests Pest couvrent autorisations, éligibilité, limites, désinscription signée et contenu de l'e-mail.
