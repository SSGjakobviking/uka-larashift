<li class="list-group-item">
    <a href="{{ $result->url() }}">{{ $result->titleExclude('year') }}</a>
    @if($children = $result->children())
        <ul>
            @each('search.child', $children, 'result')
        </ul>
    @endif
</li>