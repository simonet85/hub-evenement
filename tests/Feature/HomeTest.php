<?php

it('affiche la page d’accueil', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Les grands moments')
        ->assertSee('Voir une invitation')
        ->assertSee('Le registre tient tout seul')
        ->assertSee('Suivi à jour')
        ->assertSee('À exporter')
        ->assertSee('Aperçu — données d’exemple')
        ->assertSee('2 personnes')
        ->assertSee('Du lien WhatsApp à l’assiette du traiteur')
        ->assertSee('Chaque franc confirmé')
        ->assertSee('Un QR à l’entrée')
        ->assertSee('Import CSV des foyers')
        ->assertSee('accueil-reception.jpg')
        ->assertSee('Image d’illustration')
        ->assertSee('Avant de confier vos invités')
        ->assertSee('Privé par défaut')
        ->assertSee('Aucun tarif n’est fixé à ce jour')
        ->assertSee('Créer mon événement')
        ->assertDontSee('Créer un compte')
        ->assertSee('og:image')
        ->assertSee('images/og-home.png')
        ->assertDontSee('class="eyebrow"', false);
});

it('pointe vers la démonstration quand un jeton est configuré', function () {
    config(['hub.demo_invitation_token' => str_repeat('a', 48)]);

    $this->get('/')
        ->assertOk()
        ->assertSee(route('demo.invitation'))
        ->assertDontSee('href="#apercu"', false);
});

it('replie l’aperçu quand aucune démonstration n’est configurée', function () {
    config(['hub.demo_invitation_token' => null]);

    $this->get('/')
        ->assertOk()
        ->assertSee('href="#apercu"', false)
        ->assertDontSee(route('demo.invitation'));
});
