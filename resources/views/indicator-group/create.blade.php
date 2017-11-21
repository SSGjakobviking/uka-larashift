@extends('layouts.app')

@section('content')
<h1>Skapa ny indikatorgrupp</h1>

<form method="post" class="form-medium" action="{{ route('indicator-group.store') }}">
        {{ csrf_field() }}
        
        @include('forms.indicator-group', ['submitButtonText' => 'Skapa'])

        @include('errors.error')
    </form>
@stop