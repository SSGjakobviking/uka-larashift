@extends('layouts.app')

@section('content')
    <h1>Användare</h1>
    
    <p class="clearfix">
        <a href="{{ route('users.create') }}" class="pull-right">Lägg till ny användare</a>
    </p>

    <table class="table">
        <th>Namn</th>
        <th>E-postadress</th>
        <th>Roll</th>
        <th></th>
        <?php
            $currentUser = auth()->user();
        ?>
        @foreach($users as $user)
            <tr>
                @if($currentUser->role->name === 'admin' or $currentUser->id === $user->id)
                    <td><a href="{{ route('users.edit', $user) }}">{{ $user->name }}</a></td>
                @else
                    <td>{{ $user->name }}</td>
                @endif
                <td>{{ $user->email }}</td>
                <td>{{ $user->role->label }}</td>

                @if(auth()->user()->id != $user->id && auth()->user()->isAdmin())
                    <td><a href="{{ url('users/' . $user->id . '/delete') }}" class="text-danger pull-right">Ta bort</a></td>
                @else   
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>
@stop