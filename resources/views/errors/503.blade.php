@extends('errors.layout')

@section('code', '503')
@section('title', 'Service momentanément indisponible')
@section('eyebrow', 'Maintenance')

@section('message')
    <p>Une maintenance est en cours.</p>
    <p>Revenez dans quelques minutes ; vos données sont conservées.</p>
@endsection
