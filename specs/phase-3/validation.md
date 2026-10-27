# Validation — Phase 3

## Tests automatisés (Pest)

### Jeton et accès
- Chaque foyer créé reçoit un jeton unique.
- L'invitation est accessible sans authentification avec le bon jeton.
- La page est non indexable et n'envoie pas le jeton dans le `Referer` (`no-referrer`).
- Un jeton inconnu renvoie 404.
- La régénération révoque l'ancien lien et le nouveau fonctionne.
- Un co-organisateur peut regénérer ; un tiers reçoit 403 ; un foyer d'un autre événement renvoie 404.

### RSVP
- Présence oui : places, prénoms, régime et message enregistrés, horodatage rempli.
- Présence non : places et prénoms remis à zéro.
- Places au-delà de `allowed_seats` refusées ; prénoms plus nombreux que les places refusés.
- Modification possible avant la date limite.
- Après la date limite : première réponse acceptée et marquée hors délai ; réponse déjà enregistrée verrouillée (403).
- Sans date limite : modification toujours possible.
- Reçu e-mail mis en file si adresse connue, aucun e-mail sans adresse.
- L'organisateur voit les réponses et les couverts sur la page de l'événement.

### Import CSV
- Les foyers importés reçoivent un jeton.

## Vérifications manuelles

```bash
docker compose exec app php artisan migrate
docker compose exec app ./vendor/bin/pest
docker compose exec app ./vendor/bin/pint --test
npm run build
docker compose exec app php artisan queue:work --once
```

- Ouvrir un lien d'invitation sur un viewport mobile.
- Répondre, vérifier la confirmation et le reçu dans Mailpit.
- Modifier la réponse avant la date limite, puis regénérer le lien et vérifier le 404.
- Vérifier les compteurs et le détail de la réponse dans l'espace organisateur.

## Résultats — 27 octobre 2026

- Migration `2026_10_27_110000_add_invitation_and_rsvp_to_guests_table` appliquée ; les foyers existants ont reçu un jeton.
- Pest : **50 tests, 188 assertions — OK**, dont `InvitationTest` (15 tests), les métadonnées `noindex` / `no-referrer` et l'assertion de jetons sur l'import CSV.
- Pint : **59 fichiers — OK**.
- `npm run build` : **OK**.
- Parcours navigateur Playwright, invitation en 390 px et espace organisateur en 1280 px — **OK** : affichage public non indexable, réponse présente, modification en absence puis de nouveau présente, verrouillage après date limite, première réponse tardive acceptée et signalée, copie du lien, régénération avec ancien lien en 404, compteurs foyers/places/réponses/couverts à 2/5/2/4, aucun débordement horizontal ni erreur JavaScript.
- Reçu e-mail : **4 messages** de l'événement validé traités depuis la file et reçus dans Mailpit (trois réponses de la famille Koné, une réponse tardive de la famille Bamba) ; le contenu du reçu tardif mentionne bien le dépassement de la date limite.
- Captures : `%TEMP%\hub-e2e\05-invitation-mobile.png`, `06-invitation-reponse.png`, `07-invitation-hors-delai.png`, `08-evenement-reponses.png`.
