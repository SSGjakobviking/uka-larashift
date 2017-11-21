@extends('layouts.app')

@section('content')
<h1>Redigera {{ $indicatorGroup->name }}</h1>

<form method="post" class="form-medium" action="{{ route('indicator-group.update', $indicatorGroup) }}">
        {{ csrf_field() }}
        <input type="hidden" name="_method" value="PUT">
        @include('forms.indicator-group', ['submitButtonText' => 'Uppdatera'])

        @include('errors.error')
    </form>
@stop