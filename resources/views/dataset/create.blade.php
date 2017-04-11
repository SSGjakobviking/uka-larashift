@extends('layouts.app')

@section('content')
    <h1>Uppladdning av dataset</h1>
    
    @include('forms.dataset')

    @include('errors.error')

    @include('errors.success')
@stop