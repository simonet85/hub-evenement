@extends('errors.layout')

@section('code', '404')
@section('title', 'Page introuvable')
@section('eyebrow', 'Lien introuvable')

@section('message')
    <p>Le lien est incomplet, expiré ou ne correspond à aucune page.</p>
    <p>Vérifiez l'adresse reçue de l'organisateur, ou demandez-lui un nouveau lien.</p>
@endsection
