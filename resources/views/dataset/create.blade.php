@extends('layouts.app')

@section('content')
    
    @if (! auth()->user()->hasRole('uppgiftslamnare'))
        <a href="{{ url('/dataset') }}">Tillbaka till alla dataset</a>
    @endif

    <h1>Uppladdning av dataset</h1>
    <div class="row">
        <p class="col-sm-6">Ladda upp dataset (CSV-filer) genom att dra och släppa dem i rutan för filuppladdning. Du kan ladda upp flera filer samtidigt genom att dra och släppa dem i rutan. De två första filerna laddas upp direkt, övriga ställs i kö och laddas upp allteftersom tidigare filer blir klara. Observera att du måste ha webbläsarens fönster öppet till samtliga filer har laddats upp och markerats som klara i rutan för filuppladdning. Filerna måste vara i filformatet CSV för att importeras korrekt.</p>
        <div style="clear: both;"></div>

        <p class="alert dropzone-msg"></p>
    </div>
    
    @include('forms.dataset')

    @include('errors.error')

    @include('errors.success')
@stop