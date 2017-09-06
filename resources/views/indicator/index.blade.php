@extends('layouts.app')

@section('content')
    <h1>Indikatorer</h1>

    @foreach($indicatorGroups as $indicatorGroup)
    <h2>{{ $indicatorGroup->name }}</h2>
    <table class="table">
        <th>Namn</th>
        <th></th>
        @foreach($indicatorGroup->indicators as $indicator)
            <tr>
                <td><a href="{{ url('indicator/' . $indicator->id . '/edit') }}">{{ $indicator->name }}</a></td>
                <td></td>
            </tr>
        @endforeach
    </table>
    @endforeach
@stop