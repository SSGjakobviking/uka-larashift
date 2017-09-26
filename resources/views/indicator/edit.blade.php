@extends('layouts.app')

@section('content')
    <h1>{{ $indicator->name }}</h1>

    @include('forms.indicator')

    <h2>Dataset för förhandsgranskning</h2>
    
    <table class="table">
        <th>ID</th>
        <th>Filnamn</th>
        <th>Uppladdat av</th>
        <th>Datum</th>
        <th></th>

        @foreach($previewData as $preview)
            <tr>
                <td>{{ $preview->id }}</td>
                <td>{{ $preview->file }}</td>
                <td>@isset($preview->user->name) {{ $preview->user->name }} @endisset</td>
                <td>{{ $preview->created_at }}</td>
                <td><a href="{{ url('dataset/' . $preview->id . '/preview/unattach') }}" class="text-danger pull-right">Ta bort</a></td>
            </tr>
        @endforeach
    </table>

    <form method="post" action="{{ url('indicator/' . $indicator->id . '/dataset') }}">
        {{ csrf_field() }}
        <select class="multiselect" name="dataset_preview[]" multiple="multiple">
            @foreach($previewDropdownData as $previewData)
                <option value="{{ $previewData->id }}">{{ $previewData->file }}</option>
            @endforeach
        </select>

        <input type="submit" class="btn btn-primary" name="save_dataset_preview" value="Lägg till i förhandsgranskning">
        @if(! empty($previewUrl))
            <a class="preview-url" href="{{ $previewUrl }}" target="_blank">Förhandsgranska dataset</a>
        @endif
    </form>


    <h2>Dataset i produktionsmiljön</h2>

    <table class="table">
        <th>ID</th>
        <th>Filnamn</th>
        <th>Uppladdat av</th>
        <th>Datum</th>
        <th></th>
        @foreach($publishedData as $published)
            <tr>
                <td>{{ $published->id }}</td>
                <td>{{ $published->file }}</td>
                <td>@isset($published->user->name) {{ $published->user->name }} @endisset</td>
                <td>{{ $published->created_at }}</td>
                <td><a href="{{ url('dataset/' . $published->id . '/published/unattach') }}" class="text-danger pull-right">Ta bort</a></td>
            </tr>
        @endforeach
    </table>

    <form method="post" action="{{ url('indicator/' . $indicator->id . '/dataset') }}">
        {{ csrf_field() }}
        <select class="multiselect" name="dataset_production[]" multiple="multiple">
            @foreach($publishedDropdownData as $publishedData)
                <option value="{{ $publishedData->id }}">{{ $publishedData->file }}</option>
            @endforeach
        </select>

        <input type="submit" class="btn btn-primary" name="save_dataset_published" value="Lägg till i produktionsmiljön">
    </form>
    
@stop