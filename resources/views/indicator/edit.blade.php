@extends('layouts.app')

@section('content')
    <h1>{{ $indicator->name }}</h1>

    @include('forms.indicator')

    <h2>Dataset Preview</h2>
    
    <ul class="list-group">
        @foreach($previewData as $preview)
            <li class="list-group-item">{{ $preview->file }} <a href="{{ url('dataset/' . $preview->id . '/delete') }}" class="text-danger pull-right">Ta bort</a></li>
        @endforeach
    </ul>

    <form method="post" action="{{ url('indicator/' . $indicator->id . '/dataset') }}">
        {{ csrf_field() }}
        <select class="multiselect" name="dataset_preview" multiple="multiple">
            <option>Dataset 1</option>
            <option>Dataset 2</option>
            <option>Dataset 3</option>
        </select>

        <input type="submit" class="btn btn-primary" name="save_dataset_preview" value="Lägg till preview">
    </form>


    <h2>Dataset Produktion</h2>

    <ul class="list-group">
        @foreach($publishedData as $published)
            <li class="list-group-item">{{ $published->file }} <a href="{{ url('dataset/' . $published->id . '/delete') }}" class="text-danger pull-right">Ta bort</a></li>
        @endforeach
    </ul>

    <form method="post" action="{{ url('indicator/' . $indicator->id . '/dataset') }}">
        {{ csrf_field() }}
        <select class="multiselect" name="dataset_production" multiple="multiple">
            <option>Dataset 1</option>
            <option>Dataset 2</option>
            <option>Dataset 3</option>
        </select>

        <input type="submit" class="btn btn-primary" name="save_dataset_published" value="Lägg till produktion">
    </form>
    
@stop