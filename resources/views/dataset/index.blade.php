@extends('layouts.app')

@section('content')
    <h1>Dataset</h1>
    <!-- <p class="clearfix"><a href="{{ url('dataset/create') }}" class="pull-right">Ladda upp dataset</a></p> -->

    @include('errors.error')

    @include('errors.success')
    
    @include('dataset.filter')
    
    @if(empty($_GET['filter']) && $datasets->count() != 0)
        @include('dataset.list')
    @endif

    @foreach($tags as $tag)
        @include('dataset.list')
    @endforeach

@stop