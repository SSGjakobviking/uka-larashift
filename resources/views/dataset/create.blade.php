@extends('layouts.app')

@section('content')

    <a href="{{ url('/dataset') }}">Tillbaka till alla dataset</a>

    <h1>Uppladdning av dataset</h1>

    <p>Endast filformatet CSV stöds.</p>
    
    @include('forms.dataset')

    @include('errors.error')

    @include('errors.success')
@stop