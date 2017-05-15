@extends('layouts.app')

@section('content')
<?php
    function highlighter($keyword, $str)
    {
      return preg_replace("/($keyword)/i",'<strong class="highlight"><u>$1</u></strong>', $str);
    }

?>
<h1>Sök</h1>
    <form action="">
        <input type="text" id="q" name="q" class="text form-control">
        <input type="submit" class="btn btn-primary" value="Sök">
    </form>

    <ul class="list-group">
    @if(! empty($results))
        
        @each('search.child', $results, 'result')
    @endif
    </ul>
@stop