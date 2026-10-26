<?php

it('affiche la page d’accueil', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Les grands moments');
});
