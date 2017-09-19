@extends('layouts.app')

@section('content')
    <form method="post" action="{{ route('users.update', ['id' => $user->id]) }}">
     <input type="hidden" name="_method" value="PUT" />
        {{ csrf_field() }}
        <div class="form-group">
            <label for="name">Namn</label>
            <input class="form-control" type="text" name="name" id="name" value="{{ $user->name }}" required autofocus>
        </div>

        <div class="form-group">
            <label for="email">E-postadress</label>
            <input class="form-control" type="email" name="email" id="email" value="{{ $user->email }}">
        </div>

        <div class="form-group">
            <label for="role">Roll</label>
            <select name="role" id="role" class="form-control"{{ auth()->user()->role->name !== 'admin' ? ' disabled' : null }}>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ $user->role->id === $role->id ? 'selected' : null }}>{{ $role->label }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="password">Lösenord</label>
            <input class="form-control" type="password" name="password">
        </div>

         @include('errors.error')

        <div class="form-group">
            <input class="btn btn-primary" type="submit" name="submit" value="Uppdatera">
        </div>
    </form>
@stop