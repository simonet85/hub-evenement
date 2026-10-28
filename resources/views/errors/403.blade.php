@extends('errors.layout')

@section('code', '403')
@section('title', 'Accès refusé')
@section('eyebrow', 'Autorisation')

@section('message')
    <p>Vous n'avez pas les droits nécessaires pour consulter cette page.</p>
    <p>Si vous pensez qu'il s'agit d'une erreur, reconnectez-vous avec le compte qui a reçu le lien.</p>
@endsection
