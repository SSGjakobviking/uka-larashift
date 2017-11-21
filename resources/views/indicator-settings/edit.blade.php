@extends('layouts.app')

@section('content')
    <h1>Redigera indikator</h1>
    <p><a href="{{ url('indicator') }}">Tillbaka till alla indikatorer</a></p>
    
    <form method="post" class="form-medium" action="{{ route('indicator-settings.update', $indicator->id) }}">
        {{ csrf_field() }}

        <input type="hidden" name="_method" value="PUT">
        
        @include('forms.indicator', ['submitButtonText' => 'Uppdatera'])

        @include('errors.error')
    </form>
@stop