@extends('layouts.app')

@section('content')
    <h1>Användare</h1>

    <a href="/users/create">Lägg till ny användare</a>

    <table class="table">
        <th>Namn</th>
        <th>E-postadress</th>
        <th>Roll</th>
        <th></th>
        @foreach($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->role->label }}</td>
                <td><a href="{{ url('users/' . $user->id . '/delete') }}" class="text-danger pull-right">Ta bort</a></td>
            </tr>
        @endforeach
    </table>
@stop