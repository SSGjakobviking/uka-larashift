<form method="post" action="{{ url('indicator/' . $indicator->id) }}" enctype="multipart/form-data" class="file-upload">
    {{ csrf_field() }}
    <input type="hidden" name="_method" value="PUT">
    <div class="form-group">
        <label for="description">Beskrivning</label>
        <textarea type="text" class="form-control" id="description" name="description">{{ $indicator->description }}</textarea>
    </div>

    <input type="submit" class="btn btn-primary" value="Spara">
</form>