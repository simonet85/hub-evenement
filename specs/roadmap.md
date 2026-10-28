# Roadmap

Les phases sont volontairement petites : chacune est une tranche livrable, testable et révisable seule. Elles suivent les jalons J3 à J7 du `PLAN.md` ; J1 (inspiration, brief) et J2 (Design System, maquettes) sont déjà couverts par `DESIGN-SYSTEM.md` et `MAQUETTES.md`.

---

## Phase 1 — Socle Laravel et jetons du Design System
- Projet Laravel 12, Livewire 3, Tailwind, Pest, MySQL configurés.
- Jetons « Papier & lumière » traduits en configuration Tailwind et variables CSS.
- Authentification organisateur (inscription, connexion, réinitialisation).
- Layout commun, pages d'erreur de base, tests Pest et formatage Pint en local (CI reportée).
- **Fin** : un organisateur se connecte et voit un tableau de bord vide dans la charte validée.

## Phase 2 — Événements, foyers et invités
- Tables `events`, `guests`, `event_roles` + migrations.
- Création et édition d'un événement (type, date, lieu, thème d'accent).
- Foyers et invités : saisie manuelle et **import CSV** avec rapport d'erreurs.
- Plafond de places par foyer, rôles co-organisateur.
- **Fin** : un organisateur crée un événement et importe 50 foyers sans erreur.

## Phase 3 — Invitation publique et RSVP (J3a)
- Jeton d'invitation non devinable, URL `/i/{token}`, révocation.
- Page d'invitation personnalisée : noms, foyer, date, lieu et places.
- Formulaire RSVP : présence, nombre de personnes (plafonné), prénoms, régime, message.
- Page de confirmation, e-mail de reçu au foyer, mise à jour du récapitulatif organisateur.
- **Fin** : un invité répond depuis son lien sur mobile, et l'organisateur voit la réponse.

## Phase 4 — QR code et pointage (J3b)
- QR SVG personnel généré côté serveur, téléchargeable et intégré à l'invitation ; jeton d'entrée distinct du jeton d'invitation.
- Page mobile de scan réservée aux comptes de l'événement (propriétaire et co-organisateurs), avec caméra et saisie manuelle.
- Pointage : présent, déjà pointé, confirmation pour un foyer sans réponse ou décliné, annulation ; compteurs de foyers et couverts pointés.
- **Fin** : un organisateur scanne un QR à l'entrée et le pointage est enregistré, visible et annulable.

## Phase 5 — Programme et carte (J3b)
- Étapes du programme horaires et lieux ; code vestimentaire.
- Carte OpenStreetMap du lieu, boutons Google Maps et Waze.
- **Fin** : la page programme et carte est complète et lisible sur mobile.

## Phase 6 — Relances (J3c)
- E-mails de relance aux foyers sans réponse, par lots via queue database et cron.
- Lien WhatsApp pré-rempli par foyer.
- Suivi des relances envoyées, désinscription, limitation de débit.
- **Fin** : un foyer sans réponse reçoit sa relance et le statut est visible.

## Phase 7 — Livre d'or
- Formulaire de message public, anti-spam (honeypot, limitation de débit).
- File de modération (approuver, rejeter, masquer).
- **Fin** : un message n'apparaît qu'après validation de l'organisateur.

## Phase 8 — Galerie photo modérée
- Uploader par les admins : type de fichier vérifié, taille maximale, stockage sur disque privé.
- Vignettes générées en tâche de fond ; affichage par URLs signées.
- File de modération et grille publique des photos approuvées.
- Les photos approuvées seront visible publiquement sur le front par navigation sur l'onglet Galerie .
- **Fin** : une photo envoyée est modérée, puis visible dans la galerie.

## Phase 9 — Cadeaux, cagnotte et MoneyFusion (J5)
- Liste de cadeaux (article, prix, quantité, réservation, participation partielle).
- Cagnotte avec objectif, montants suggérés, message, anonymat possible.
- Paiement en attente, redirection MoneyFusion, webhook signé et idempotent, confirmation.
- Journal des paiements, échecs, expirations, remboursements et rapprochement manuel.
- **Fin** : un paiement sandbox est confirmé de bout en bout et les totaux sont justes.

## Phase 10 — Tableau de bord et exports traiteur (J6)
- Indicateurs : oui, non, en attente, couverts, régimes, évolution, photos à modérer.
- Exports CSV et Excel des invités et couverts ; PDF pour le traiteur.
- Lien de lecture seule pour le traiteur, avec expiration.
- **Fin** : l'export traiteur est correct et le lien expire.

## Phase 11 — Animations et accessibilité
- Animations au scroll sur invitation et programme, respect de `prefers-reduced-motion`.
- Contraste, navigation clavier, styles de focus, HTML sémantique.
- Performance mobile.
- **Fin** : parcours invité complet au clavier et sur mobile.

## Phase 12 — Durcissement et mise en ligne (J7)
- Limitation de débit, validation stricte des envois, pages 404 et 500, journalisation.
- Sauvegardes, conservation et suppression des données invités après l'événement.
- Déploiement sur mutualisé (git pull, migrations, cron), vérifications HTTPS et SMTP.
- **Fin** : parcours complet sur mobile en production, sans erreur.

---

## Plus tard, non planifié

- Multi-langue (français + anglais).
- Plan de table avancé.
- Application mobile native.
- Marketplace de prestataires.
- Statistiques d'ouverture des invitations.
