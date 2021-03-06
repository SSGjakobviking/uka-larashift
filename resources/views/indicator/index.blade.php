@extends('layouts.app')

@section('content')
    
    <div class="page-header">
        <h1>Indikatorer</h1>
        @if (auth()->user()->isAdmin())
            <ul class="link-list">
            <li><a href="{{ url('dataset/create') }}">Ladda upp dataset</a></li>
            <li><a href="/dataset">Okopplade dataset</li>
                <li><a href="{{ route('indicator.create') }}" style="{{ str_contains(Route::currentRouteName(), "indicator.create") ?  'text-decoration:underline;' : '' }}">Skapa ny indikator</a></li>
                <li><a href="{{ route('indicator-group.create') }}" style="{{ str_contains(Route::currentRouteName(), "indicator-group.create") ?  'text-decoration:underline;' : '' }}">Skapa ny indikatorgrupp</a></li>
                
            </ul>
        @endif
    </div>

    @foreach($indicatorGroups as $indicatorGroup)
    <div class="indicator-group-container">
        <h2>{{ $indicatorGroup->name }}</h2>
        @if (auth()->user()->isAdmin())
        <a href="{{ route('indicator-group.edit', $indicatorGroup) }}">Redigera</a>
        @endif
        
        @if (auth()->user()->isAdmin() && $indicatorGroup->indicators->count() === 0)
            <a href="{{ url('indicator-group', [$indicatorGroup->id, 'delete']) }}" class="text-danger action-remove">Ta bort</a>
        @endif
    </div>
    <table class="indicator-container table tablesorter">

        <th class="indicator-name">Namn</th>
        <td></td>

        @foreach($indicatorGroup->indicators as $indicator)
            <tr>
                <td style="width: 80%;"><a href="{{ url('indicator/' . $indicator->id . '/edit') }}">{{ $indicator->name }}</a></td>
                @if (auth()->user()->isAdmin())
                    
                    @if ($indicator->datasets->count() === 0)
                        <td style="text-align:right;"><a href="{{ url('indicator', [$indicator->id, 'delete']) }}" class="text-danger action-remove">Ta bort (inga dataset)</a></td>
                    @else
                        <td></td>
                    @endif
                @endif
            </tr>
        @endforeach
        
    </table>
    @endforeach
@stop