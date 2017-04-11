@extends('layouts.app')

@section('content')
    <h1>Dataset</h1>

    @include('forms.dataset')

    @include('errors.error')

    @include('errors.success')
    
    @include('dataset.list')
@stop