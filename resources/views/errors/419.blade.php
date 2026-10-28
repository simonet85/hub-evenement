@extends('errors.layout')

@section('code', '419')
@section('title', 'Session expirée')
@section('eyebrow', 'Sécurité')

@section('message')
    <p>Votre session a expiré par sécurité.</p>
    <p>Revenez à la page précédente, rechargez-la, puis recommencez l'envoi.</p>
@endsection
