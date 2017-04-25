@extends('layouts.app')

@section('content')
    
    @if (! auth()->user()->hasRole('uppgiftslamnare'))
        <a href="{{ url('/dataset') }}">Tillbaka till alla dataset</a>
    @endif

    <h1>Uppladdning av dataset</h1>

    <p>Endast filformatet CSV stöds.</p>
    
    @include('forms.dataset')

    @include('errors.error')

    @include('errors.success')
@stop