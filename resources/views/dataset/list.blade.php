@if(isset($tag) && $datasets = $tag->datasets)
    <h3>Okopplade dataset {{ $tag->name }} ({{ count($tag->datasets) }})</h3>
@else
    <h3>Okopplade dataset</h3>
@endif

<table class="table tablesorter">
<thead>
    <tr>
        <th>ID</th>
        <th>Filnamn</th>
        <!-- <th>Taggar</th> -->
        <th>Uppladdat av</th>
        <th>Datum</th>
        
        <td></td>
    </tr>
</thead>
    <?php $datasets->load('tags'); ?>

    @foreach($datasets as $dataset)
    <tbody>
        <tr class="dataset-row">
            <td>{{ $dataset->id }}</td>
            <td>
                <p><a href="{{ url('uploads', $dataset->file) }}">{{ $dataset->file }}</a></p>
            </td>
            <!-- <td>
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
            </td> -->
            <td>@isset($dataset->user) {{ $dataset->user->name }} @endisset</td>
            <td>{{ $dataset->created_at->format('Y-m-d H:i:s')  }}</td>
            
            @if($dataset->statuses->count() === 0) 
                <td><a href="{{ url('dataset', [$dataset->id, 'delete']) }}" class="text-danger action-remove">Ta bort</a></td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table>