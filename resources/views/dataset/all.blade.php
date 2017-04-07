<table class="table">
    <th>Filnamn</th><th>Uppladdningsdatum</th><th>Användare</th><th></th>
    @foreach($datasets as $dataset)
        <tr>
            <td>{{ $dataset->file }}</td>
            <td>{{ $dataset->created_at->format('j F, Y')  }} kl {{ $dataset->created_at->format('H:i') }}</td>
            <td>{{ $dataset->user->name }}</td>
            <td><a href="{{ url('dataset/' . $dataset->id . '/delete') }}" class="text-danger pull-right">Ta bort</a></td>
        </tr>
    @endforeach
</table>