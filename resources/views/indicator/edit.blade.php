@extends('layouts.app')

@section('content')
    Indikator 
    @if (auth()->user()->isAdmin())
    <a href="{{ route('indicator-settings.edit', $indicator) }}">Redigera</a>
    @endif
    <h1>{{ $indicator->name }}</h1>
   

    <ul class="nav-tabs nav" role="tablist">
    <li class="nav-item active"><a class="nav-link" href="#preview" data-toggle="tab" role="tab" aria-controls="preview">Testmilj√∂ (<?php echo $previewData->count() ?>)</a>
    </li>
    <li class="nav-item">
    <a class="nav-link" href="#published" data-toggle="tab" role="tab" aria-controls="published">Produktionsmilj√∂ (<?php echo $publishedData->count() ?>)</a>
    </li>
       
    </ul>
  
  <div class="tab-content">

    <div id="preview" class="tab-pane fade in active" style="position:relative; padding-top:50px;">
    <h2>Dataset i testmilj√∂</h2>

    <table class="table tablesorter" id="previewtable" style="order:2">
    <thead>
        <th>ID</th>
        <th>Filnamn</th>
        <th>Uppladdat av</th>
        <th>Datum</th>
        <td></td>
        </thead>
<tbody>
        @foreach($previewData as $preview)
            <tr>
                <td>{{ $preview->id }}</td>
                <td>{{ $preview->file }}</td>
                <td>@isset($preview->user->name) {{ $preview->user->name }} @endisset</td>
                <td>{{ $preview->created_at }}</td>
                <td><a href="{{ url('dataset/' . $preview->id . '/preview/unattach') }}" class="text-danger pull-right">Koppla bort</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <div style="position:absolute;top:15px; width:100%;">
    <form method="post" action="{{ url('indicator/' . $indicator->id . '/dataset') }}">
        {{ csrf_field() }}
        <select class="multiselect" name="dataset_preview[]" multiple="multiple">
            @foreach($previewDropdownData as $previewData)
                <option value="{{ $previewData->id }}">{{ $previewData->file }}</option>
            @endforeach
        </select>

        <input type="submit" class="btn btn-primary" name="save_dataset_preview" value="L√§gg till i f√∂rhandsgranskning">
        @if(! empty($previewUrl))
            <a class="preview-url" href="{{ $previewUrl }}" target="_blank">F√∂rhandsgranska dataset</a>
        @endif
    </form>

    </div>
    </div>

    <div id="published" class="tab-pane fade" style="padding-top:50px;position:relative;">
    <h2>Dataset i produktionsmilj√∂n</h2>

    <table class="table tablesorter">
    <thead>
        <th>ID</th>
        <th>Filnamn</th>
        <th>Uppladdat av</th>
        <th>Datum</th>
        <td></td>
        </thead>
        <tbody>
        @foreach($publishedData as $published)
            <tr>
                <td>{{ $published->id }}</td>
                <td>{{ $published->file }}</td>
                <td>@isset($published->user->name) {{ $published->user->name }} @endisset</td>
                <td>{{ $published->created_at }}</td>
                <td><a href="{{ url('dataset/' . $published->id . '/published/unattach') }}" class="text-danger pull-right">Koppla bort</a></td>
            </tr>
        @endforeach
        <tbody>
    </table>
    <div style="position:absolute; top:15px; width:100%;">
    <form method="post" action="{{ url('indicator/' . $indicator->id . '/dataset') }}">
        {{ csrf_field() }}
        <select class="multiselect" name="dataset_published[]" multiple="multiple">
            @foreach($publishedDropdownData as $publishedData)
                <option value="{{ $publishedData->id }}">{{ $publishedData->file }}</option>
            @endforeach
        </select>

        <input type="submit" class="btn btn-primary" name="save_dataset_published" value="L√§gg till i produktionsmilj√∂n">
    </form>
    </div>
</div>
</div>
@stop