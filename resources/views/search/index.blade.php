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
        @foreach($results as $result)

            @if($result['_type'] == 'university')
                <li class="list-group-item"><?php echo highlighter($_GET['q'], 'Antal registrerade studenter vid ' . $result['_source']['name'] ); ?></li>
            @elseif($result['_type'] == 'group')
                <li class="list-group-item"><?php echo highlighter($_GET['q'], 'Antal registrerade studenter inom ' . $result['_source']['name']); ?></li>
            @elseif($result['_type'] == 'gender')
                @if(strpos($result['_source']['name'], 'ä') !== false)
                    <li class="list-group-item">Antal manliga registrerade studenter</li>
                @else
                    <li class="list-group-item">Antal kvinnliga registrerade studenter</li>
                @endif
            @elseif($result['_type'] == 'age-group')
                <li class="list-group-item">Antal registrerade studenter</li>
            @endif

        @endforeach
    @endif
    </ul>
@stop