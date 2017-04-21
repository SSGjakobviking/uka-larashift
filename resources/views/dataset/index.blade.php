@extends('layouts.app')

@section('content')
    <h1>Dataset</h1>
    <p class="clearfix"><a href="{{ url('dataset/create') }}" class="pull-right">Ladda upp dataset</a></p>
    {{-- @include('forms.dataset') --}}

    @include('errors.error')

    @include('errors.success')
    
    @include('dataset.list')
@stop