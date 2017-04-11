@extends('layouts.app')

@section('content')
    <h1>Indikatorer</h1>
    <h2>Utbildning på grundnivå och avancerad nivå</h2>
    <table class="table">
        <th>Namn</th>
        <th></th>
        @foreach($indicators as $indicator)
            <tr>
                <td><a href="{{ url('indicator/' . $indicator->id . '/edit') }}">{{ $indicator->name }}</a></td>
                <td><a href="{{ url('indicator/' . $indicator->id . '/delete') }}" class="text-danger pull-right">Ta bort</a></td>
            </tr>
        @endforeach
    </table>
@stop