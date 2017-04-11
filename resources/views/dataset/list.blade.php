<table class="table">
    <th>ID<th>Filnamn</th><th>Uppladdat av</th><th>Datum</th><th>Status</th><th></th>
    @foreach($datasets as $dataset)
        <tr>
            <td>{{ $dataset->id }}</td>
            <td>{{ $dataset->file }}</td>
            <td>{{ $dataset->user->name }}</td>
            <td>{{ $dataset->created_at->format('Y-m-d H:i:s')  }}</td>
            <td>{{ $dataset->status }}</td>
            <td><a href="{{ url('dataset/' . $dataset->id . '/delete') }}" class="text-danger pull-right">Ta bort</a></td>
        </tr>
    @endforeach
</table>