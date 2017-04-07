@extends('layouts.app')

@section('content')
    <h1>Dataset</h1>
    
    <p>Ladda upp ditt dataset (CSV) nedan:</p>
    @include('forms.dataset')

    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session()->has('success'))
        <p class="alert alert-success">{{ session()->get('success') }}</p>
    @endif
    
    @include('dataset.list')
@stop