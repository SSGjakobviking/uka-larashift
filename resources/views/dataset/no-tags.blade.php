<h3>No tags</h3>
<table class="table">
    <th>ID<th>Filnamn</th><th>Uppladdat av</th><th>Datum</th><th>Status</th><th></th>

    <?php $datasets->load('tags'); ?>
    
    @foreach($datasets as $dataset)
        <tr class="dataset-row">
            <td>{{ $dataset->id }}</td>
            <td>
                <p>{{ $dataset->file }}</p>

                <a class="btn btn-primary pull-right" href="{{ url('dataset') }}">Spara</a>
                <form method="post" action="">
                    {{ csrf_field() }}
                    <select class="tags-form form-control select2-hidden-accessible" placeholder="Välj en tagg" style="width: 70%;" data-dataset-id="{{ $dataset->id }}" multiple>
                    
                    @foreach($allTags as $tag)
                        @if(in_array($tag->id, $dataset->tags->pluck('id')->toArray()))
                            <option value="{{ strtolower($tag->name) }}" selected>{{ $tag->name }}</option>
                        @else
                            <option value="{{ strtolower($tag->name) }}">{{ $tag->name }}</option>
                        @endif
                    @endforeach
    
                    </select>
                </form>

            </td>
            <td>@isset($dataset->user) {{ $dataset->user->name }} @endisset</td>
            <td>{{ $dataset->created_at->format('Y-m-d H:i:s')  }}</td>
            <td>{{ $dataset->statuses->pluck('name')->implode(', ') }}</td>
            @if($dataset->statuses->count() === 0) 
                <td><a href="{{ url('dataset', [$dataset->id, 'delete']) }}" class="text-danger">Ta bort</a></td>
            @endif
        </tr>
    @endforeach
</table>