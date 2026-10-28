@extends('errors.layout')

@section('code', '429')
@section('title', 'Trop de tentatives')
@section('eyebrow', 'Patience')

@section('message')
    <p>Trop de requêtes ont été envoyées en peu de temps.</p>
    <p>Patientez une minute, puis réessayez.</p>
@endsection
