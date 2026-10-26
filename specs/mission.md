# Mission

Les grands moments se préparent avec joie et finissent trop souvent dans un fichier Excel envoyé par WhatsApp, entre les confirmations approximatives, les régimes alimentaires oubliés et la cagnotte tenue sur un cahier.

La plateforme d'événements privés réunit tout ce qui fait un bel événement au même endroit : une invitation personnalisée que les invités ouvrent depuis leur téléphone, un RSVP qui remplit les listes tout seul, et un tableau de bord qui dit à l'organisateur — et à son traiteur — exactement ce qui se prépare.

## Ce que nous faisons

- **Inviter** : un lien personnel signé par foyer, une page d'invitation à l'image de l'événement (mariage, baptême, anniversaire, fête), un QR code pour l'entrée.
- **Répondre** : RSVP en ligne avec nombre de personnes, prénoms, régime alimentaire et message, dans la limite de places de l'invitation. Pointage par scan du QR le jour J.
- **Informer** : programme horaire, code vestimentaire, carte du lieu avec itinéraires Google Maps et Waze.
- **Partager** : livre d'or modéré et galerie photo alimentée par les invités, publiés après validation.
- **Contribuer** : liste de cadeaux et cagnotte, payées par mobile money via MoneyFusion.
- **Piloter** : tableau de bord des réponses et couverts, exports CSV/Excel/PDF pour le traiteur, lien de lecture seule avec expiration.

## Qui nous servons

- **Les organisateurs** — particuliers en Côte d'Ivoire qui organisent un mariage, un baptême, un anniversaire ou une fête, souvent sur mobile, souvent avec WhatsApp comme canal principal. Un compte peut gérer plusieurs événements.
- **Les co-organisateurs** — conjoint, famille, témoins : mêmes outils que l'organisateur, sans être propriétaires du compte.
- **Les invités** — sans compte, sans installation : un lien personnel, un formulaire court, deux minutes suffisent.
- **Le traiteur** — accès en lecture seule, limité dans le temps : couverts, régimes, allergies, exports.

## Public cible

- **Particuliers en Côte d'Ivoire** organisant un événement privé de 20 à 400 invités : mariage, baptême, anniversaire, fête.
- Un contexte mobile d'abord, une connexion parfois lente, un usage intensif de WhatsApp pour la communication et du Mobile Money pour les contributions.
- Une esthétique soignée attendue : les maquettes « Papier & lumière » et les quatre thèmes d'événement sont la référence visuelle validée.

## Qu'est-ce que la réussite

Un invité qui répond en moins de deux minutes depuis son téléphone. Un organisateur qui connaît son nombre de couverts sans appeler personne. Un traiteur qui reçoit un export juste, régimes et allergies compris. Une cagnotte transparente, confirmée par webhook, jamais décidée par le navigateur.

Et un événement où l'on parle enfin du discours du cousin, pas du fichier Excel.

## Ce que nous ne faisons pas (V1)

- Pas d'annuaire public ni de marketplace de prestataires : les pages d'événement sont privées et non indexées par défaut.
- Pas d'application mobile native : le site est une web app mobile d'abord.
- Pas de messagerie de type chat entre invités.
- Pas de comptabilité ni de facturation fiscale.
- Pas de plan de table avancé : un plan simple est proposé avec les exports du jalon J6.
- Pas de multi-langue : français uniquement, les textes sont dans les fichiers de traduction.
- Pas de paiement en ligne avant le jalon J5 : MoneyFusion est intégré en sandbox puis validé avant mise en production.

## Principes

- **Mobile d'abord** : chaque écran invité est conçu pour un téléphone, testé sur un téléphone.
- **Privé par défaut** : liens non devinables, pages non indexées, photos hors dossier public.
- **Mobile money d'abord** : MoneyFusion, montants décidés côté serveur, webhook signé et idempotent.
- **Sobre et fiable** : hébergement mutualisé, pas de dépendance à un worker permanent, dégradation acceptable en cas de lenteur.
