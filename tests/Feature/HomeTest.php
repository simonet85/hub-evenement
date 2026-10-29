<?php

it('affiche la page d’accueil', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Les grands moments')
        ->assertSee('Voir une invitation')
        ->assertSee('Le registre tient tout seul')
        ->assertSee('Données d’exemple')
        ->assertSee('Créer mon événement')
        ->assertDontSee('Créer un compte')
        ->assertSee('og:image')
        ->assertSee('images/og-home.png')
        ->assertDontSee('class="eyebrow"', false);
});
