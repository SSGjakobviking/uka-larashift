@extends('layouts.app')

@section('content')
    <form method="post" action="{{ route('register') }}">
        {{ csrf_field() }}
        <div class="form-group">
            <label for="name">Namn</label>
            <input class="form-control" type="text" name="name" id="name">
        </div>

        <div class="form-group">
            <label for="email">E-postadress</label>
            <input class="form-control" type="email" name="email" id="email">
        </div>

{{--         <div class="form-group">
            <select name="role">
                <option>Administratör</option>
            </select>
        </div> --}}

        <div class="form-group">
            <label for="password">Lösenord</label>
            <input class="form-control" type="password" name="password">
        </div>

        <div class="form-group">
            <input class="btn btn-primary" type="submit" name="submit" value="Lägg till användare">
        </div>
    </form>
@stop