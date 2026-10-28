# Phase 8 — Galerie photo modérée

## Objectif

Les organisateurs déposent des photos de l'événement. Chaque photo passe par une file de modération ; seules les photos publiées apparaissent dans la galerie accessible aux invités munis de leur lien personnel. Les fichiers restent sur un disque privé et ne sont servis que par des URLs signées.

## Décisions de cadrage

- **Dépôt réservé aux organisateurs** (propriétaire et co-organisateurs). Cette décision remplace la mention « Envoi par les invités » de la roadmap : les invités consultent la galerie, ils n'ajoutent pas de photos.
- Les photos publiées sont accessibles depuis le lien personnel de chaque foyer (`/i/{jeton}/galerie`), comme le programme. La galerie n'est pas indexable et aucun lien public depuis la page d'accueil n'est créé.
- Formats acceptés : JPEG et PNG. Le WebP est exclu, car l'extension GD du serveur ne sait pas le décoder ; accepter un fichier impossible à traiter produirait une photo Cassée.
- Le rejet supprime définitivement le fichier d'origine et ses dérivés, et retire la photo de la base. Une photo rejetée ne peut donc pas être republiée.

## User stories

- En tant qu'organisateur, je dépose jusqu'à 10 photos à la fois depuis la page galerie de l'événement.
- En tant qu'organisateur, je vois les miniatures des photos en attente, je les publie, les masque ou les supprime.
- En tant qu'organisateur, j'ajoute ou modifie une légende facultative sur chaque photo.
- En tant que co-organisateur, je gère la galerie comme le propriétaire.
- En tant qu'invité, j'ouvre la galerie depuis mon invitation et je vois uniquement les photos publiées.
- En tant qu'organisateur, je vois le nombre de photos à modérer sur l'événement et le tableau de bord.

## Règles

### Dépôt

- Route `POST events/{event}/galerie`, réservée au propriétaire et aux co-organisateurs (`manageGallery`).
- Un envoi contient de 1 à 10 fichiers. Chaque fichier est vérifié : image réellement décodable, JPEG ou PNG, 10 Mo maximum.
- Un nom de fichier aléatoire (UUID) est attribué ; le nom d'origine est conservé en base pour l'affichage.
- Les fichiers sont écrits sur le disque `local` (racine privée `storage/app/private`), sous `events/{event_id}/photos/`.
- Chaque photo est créée à l'état **en attente**, rattachée à l'événement et à l'organisateur qui l'a déposée.
- Les dérivés (vignette et version web) sont générés en tâche de fond ; la page de gestion indique qu'un traitement est en cours tant qu'ils n'existent pas.
- La légende est facultative (120 caractères maximum) et se modifie photo par photo après l'envoi : un envoi groupé ne permet pas d'associer une légende à chaque fichier.

### Dérivés

- Vignette : plus grand côté limité à 480 px. Version web : plus grand côté limité à 1 600 px.
- L'orientation EXIF est appliquée. Le fichier ré-encodé ne conserve aucune métadonnée (dont la position GPS).
- Format de sortie identique à la source (JPEG ou PNG) pour préserver la transparence.
- L'original n'est jamais servi publiquement ; seuls la vignette et la version web le sont.

### Diffusion et modération

- États : `pending` → `approved` ↔ `hidden`. Le rejet (`reject`) supprime la photo.
- Seules les photos **publiées** apparaissent dans la galerie des invités. Masquée, une photo disparaît immédiatement, même si une URL signée a déjà été émise.
- Les URLs des dérivés sont signées et expirent après 30 minutes. Le contrôle d'accès est refait à chaque requête : une photo non publiée renvoie 404.
- La prévisualisation d'une photo en attente passe par une route authentifiée réservée aux membres de l'événement, jamais par une URL signée publique.
- Publier et masquer enregistrent l'auteur et la date de la modération. Rejeter supprime aussi les fichiers et ne laisse aucune trace de la photo.
- La galerie des invités est paginée par 12, de la plus récente à la plus ancienne, et affiche la légende si elle existe.
- Les pages organisateur sont non indexables et utilisables sans JavaScript.

### Compteurs

- Le détail de l'événement affiche « Galerie photo (N) » avec le nombre de photos en attente.
- Le tableau de bord affiche « N photo(s) à modérer » par événement.

## Hors périmètre

- Envoi de photos par les invités.
- Galerie publique sur la page d'accueil ou partage social.
- Vidéos, albums, réordonnancement, commentaires ou likes.
- Recadrage manuel, filtres, téléchargement de l'original.
- Notification e-mail lors d'un dépôt ou d'une publication.
- Conversion WebP, stockage cloud ou CDN.
- Choix par l'organisateur d'activer ou non la galerie.

## Critères d'acceptation

- Un envoi valide crée les photos en attente sur le disque privé et déclenche la génération des dérivés.
- Un fichier non image, un GIF, un WebP, un fichier de plus de 10 Mo ou un envoi de plus de 10 fichiers est refusé.
- Aucune photo n'apparaît dans la galerie invitée avant publication.
- Après génération, la vignette et la version web existent ; l'original n'est jamais accessible par une URL publique.
- Une URL signée d'une photo masquée renvoie 404.
- Rejeter supprime la ligne en base et tous les fichiers associés.
- Propriétaire et co-organisateur gèrent la galerie ; un tiers reçoit 403 ; une photo d'un autre événement renvoie 404.
- Les légendes sont échappées (aucune injection HTML).
- Les compteurs d'attente sont exacts sur l'événement et le tableau de bord.
- Les tests Pest couvrent envoi, validation, traitement, URLs signées, visibilité, modération, suppression, autorisations, échappement et compteurs.
