@extends('layouts.app')

@section('content')
    <h1>Dataset</h1>
    <a href="{{ url('dataset/create') }}" class="pull-right">Ladda upp dataset</a>
    {{-- @include('forms.dataset') --}}

    @include('errors.error')

    @include('errors.success')
    
    @include('dataset.list')
@stop