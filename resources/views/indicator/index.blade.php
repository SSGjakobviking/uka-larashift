@extends('layouts.app')

@section('content')
    
    <div class="page-header">
        <h1>Indikatorer</h1>
        @if (auth()->user()->isAdmin())
            <ul class="link-list">
                <li><a href="{{ route('indicator.create') }}">Skapa ny indikator</a></li>
                <li><a href="{{ route('indicator-group.create') }}">Skapa ny indikatorgrupp</a></li>
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
    <table class="table">
        <th>Namn</th>
        <th></th>
        <th></th>

        @foreach($indicatorGroup->indicators as $indicator)
            <tr>
                <td style="width: 80%;"><a href="{{ url('indicator/' . $indicator->id . '/edit') }}">{{ $indicator->name }}</a></td>
                @if (auth()->user()->isAdmin())
                    <td><a href="{{ route('indicator-settings.edit', $indicator) }}">Redigera</a></td>
                    @if ($indicator->datasets->count() === 0)
                        <td><a href="{{ url('indicator', [$indicator->id, 'delete']) }}" class="text-danger action-remove">Ta bort</a></td>
                    @else
                        <td></td>
                    @endif
                @endif
            </tr>
        @endforeach
    </table>
    @endforeach
@stop