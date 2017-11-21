@extends('layouts.app')

@section('content')
    <h1>Skapa indikator</h1>
    
    <form method="post" class="form-medium" action="{{ route('indicator.store') }}">
        {{ csrf_field() }}
        
        @include('forms.indicator', ['submitButtonText' => 'Skapa'])

        @include('errors.error')
    </form>
@stop