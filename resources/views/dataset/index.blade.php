@extends('layouts.app')

@section('content')
<div class="page-header">
    <h1>Dataset</h1>
    @if (auth()->user()->isAdmin())
            <ul class="link-list">
                <li><a href="/dataset">Okopplade dataset</li>
                <li><a href="{{ route('indicator.create') }}">Skapa ny indikator</a></li>
                <li><a href="{{ route('indicator-group.create') }}">Skapa ny indikatorgrupp</a></li>
                <li><a href="{{ url('dataset/create') }}" class="pull-right">Ladda upp dataset</a></li>
            </ul>
        @endif
        </div>
    @include('errors.error')

    @include('errors.success')
    
    @include('dataset.filter')

    @foreach($tags as $tag)
        @include('dataset.list')
    @endforeach

@stop