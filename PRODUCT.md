# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

- **Organisateurs** : particuliers en Côte d'Ivoire qui préparent un mariage, un baptême, un anniversaire ou une fête de 20 à 400 invités. Souvent sur mobile, avec WhatsApp comme canal principal et le Mobile Money pour les contributions. Un compte peut gérer plusieurs événements.
- **Co-organisateurs** : conjoint, famille ou témoins qui disposent des mêmes outils de gestion sans être propriétaires du compte.
- **Invités** : sans compte ni installation, ils ouvrent un lien personnel, répondent en quelques minutes et retrouvent le programme, la carte, la galerie, le livre d'or et les cadeaux.
- **Traiteur** : accès en lecture seule, limité dans le temps, aux couverts, régimes, allergies et exports.

Quand les besoins de ces publics entrent en conflit, l'expérience de l'**organisateur** prime : justesse des comptes, contrôle et fiabilité opérationnelle avant l'expression visuelle.

## Product Purpose

Hub Événement réunit en un seul endroit ce qu'un événement privé exige : invitation personnalisée, RSVP, QR code d'entrée, programme et carte, livre d'or et galerie modérés, cadeaux et cagnotte, pilotage et exports traiteur.

Le produit remplace le fichier Excel envoyé par WhatsApp, les confirmations approximatives, les régimes oubliés et la cagnotte tenue sur un cahier. La réussite se mesure à un invité qui répond en moins de deux minutes, un organisateur qui connaît ses couverts sans appeler personne, un traiteur qui reçoit un export juste et une cagnotte confirmée côté serveur.

**Posture de lancement** : les premiers utilisateurs réels sont l'objectif immédiat ; la fiabilité, la confiance et l'accompagnement opérationnel priment. Le produit est **gratuit au lancement** ; la monétisation est une décision ouverte, volontairement différée.

## Positioning

La plateforme d'événements privés pensée pour la Côte d'Ivoire : un lien personnel par foyer, une expérience mobile d'abord, WhatsApp comme canal de diffusion et le Mobile Money comme moyen de contribution.

Ce qu'un tableur ou une messagerie ne peuvent pas copier : des comptes de couverts et de contributions qui font autorité, des exports prêts pour le traiteur, des pages privées non indexées et des montants toujours vérifiés côté serveur.

## Operating Context

- Les organisateurs préparent et pilotent depuis un téléphone, parfois avec une connexion lente.
- WhatsApp sert à diffuser les liens personnels et à relancer les foyers ; la plateforme ne remplace pas la conversation, elle fournit les liens et les chiffres.
- Les invitations sont des liens non devinables, non indexés, révocables ; les invités n'ont pas de compte.
- Le jour J, le pointage se fait au scan du QR code ou par saisie manuelle du code, sur mobile.
- Le traiteur reçoit un lien signé en lecture seule, avec une expiration choisie.
- Les contributions passent par MoneyFusion ; le statut et le montant sont revérifiés côté serveur avant crédit.
- L'exploitation cible un hébergement mutualisé PHP/MySQL : cron, file en base, pas de worker permanent, pas de Redis.

## Capabilities and Constraints

**Capacités livrées** : authentification organisateur, événements mariage/baptême/anniversaire/fête, foyers et import CSV, co-organisateurs, invitations personnelles et QR, RSVP avec places, prénoms, régimes et messages, relances WhatsApp/e-mail, programme et carte, code vestimentaire, livre d'or et galerie modérés, cadeaux et cagnotte, paiements MoneyFusion avec journal et remboursements, pilotage, exports CSV/Excel/PDF et portail traiteur signé.

**Contraintes durables** :

- Français uniquement ; les textes passent par les fichiers de traduction.
- Web app mobile d'abord, sans application native.
- Pages d'événement privées et non indexées par défaut.
- Un compte, plusieurs événements ; rôles propriétaire, co-organisateur et traiteur en lecture seule.
- MoneyFusion pour les paiements ; aucun montant décidé par le navigateur.
- Hébergement mutualisé : PHP 8.3+ / MySQL, cron et file en base.
- Hors périmètre V1 : annuaire ou marketplace public, chat entre invités, comptabilité ou facturation fiscale, plan de table avancé, API publique, multi-langue.

**Décision ouverte** : modèle économique après la gratuité de lancement (prix par événement, commission sur contributions ou autre).

## Brand Commitments

- Nom : **Hub Événement**.
- Interface et contenus en français.
- Direction visuelle validée : Design System « Papier & lumière », décliné en quatre thèmes d'événement. Les jetons de `design-system/tokens.json` sont la source de vérité.

## Evidence on Hand

- Documents de référence : `specs/mission.md`, `specs/tech-stack.md`, `specs/roadmap.md`.
- Design System : `DESIGN-SYSTEM.md`, `design-system/tokens.json`.
- Application fonctionnelle et événement de démonstration « Mariage QR 2026 » en base de développement.
- Aucun témoignage client, étude de cas, métrique de production, couverture presse ni tarif public n'existe à ce jour. Ces éléments ne doivent pas être inventés.

## Product Principles

1. **L'organisateur repart avec des certitudes.** Comptes de foyers, places, couverts et contributions doivent être justes et consultables sans appeler personne.
2. **Deux minutes pour l'invité.** Un lien, un formulaire court, aucune installation ; l'expérience téléphone est la référence.
3. **Privé par défaut.** Jetons non devinables, pages non indexées, photos hors dossier public, accès traiteur signé et limité dans le temps.
4. **L'argent ne se décide jamais dans le navigateur.** Montant, statut et crédit sont recalculés et revérifiés côté serveur.
5. **Sobre et exploitable en mutualisé.** Pas de dépendance à un worker permanent ; dégradation acceptable sous une connexion lente.

## Accessibility & Inclusion

- Priorité au mobile et aux connexions lentes : parcours invité testé sur téléphone, images différées, interactions dégradables sans JavaScript.
- Le parcours invité prend en charge le clavier, un focus visible, le mouvement réduit, les libellés ARIA et des cibles tactiles de 44 px minimum.
- Aucun niveau formel de conformité WCAG n'est revendiqué à ce jour ; les audits axe-core servent de garde-fou.
