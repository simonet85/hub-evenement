@extends('errors.layout')

@section('code', '500')
@section('title', 'Une erreur est survenue')
@section('eyebrow', 'Incident')

@section('message')
    <p>L'incident a été enregistré. Réessayez dans un instant.</p>
    <p>Si le problème persiste, contactez l'organisateur de l'événement.</p>
@endsection
