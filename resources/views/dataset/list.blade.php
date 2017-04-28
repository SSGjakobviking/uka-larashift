@if(isset($tag) && $datasets = $tag->datasets)
    <h3>{{ $tag->name }} ({{ count($tag->datasets) }})</h3>
@else
    <h3>Utan taggar</h3>
@endif

<table class="table">
    <tr>
        <th>ID</th>
        <th>Filnamn</th>
        <th>Uppladdat av</th>
        <th>Datum</th>
        <th>Status</th>
        <th></th>
    </tr>
    @foreach($datasets as $dataset)
        <tr class="dataset-row">
            <td>{{ $dataset->id }}</td>
            <td>
                <p>{{ $dataset->file }}</p>
                

                <form method="post" action="">
                    {{ csrf_field() }}
                    <select class="tags-form form-control select2-hidden-accessible pull-left" placeholder="Välj en tagg" style="width: 60%;" data-dataset-id="{{ $dataset->id }}" multiple>

                        @foreach($allTags as $tag)
                            @if(in_array($tag->id, $dataset->tags->pluck('id')->toArray()))
                                <option value="{{ strtolower($tag->name) }}" selected>{{ $tag->name }}</option>
                            @else
                                <option value="{{ strtolower($tag->name) }}">{{ $tag->name }}</option>
                            @endif
                        @endforeach
                    </select>
                    <a class="btn btn-primary pull-left" href="{{ url('dataset') }}">Spara</a>
                </form>
            </td>
            <td>{{ $dataset->user->name }}</td>
            <td>{{ $dataset->created_at->format('Y-m-d H:i:s')  }}</td>
            <td>{{ $dataset->status }}</td>
            <td><a href="{{ url('dataset', [$dataset->id, 'delete']) }}" class="text-danger pull-right">Ta bort</a></td>
        </tr>
    @endforeach
</table>