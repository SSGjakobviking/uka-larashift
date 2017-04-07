<form method="post" action="{{ url('indicator/store') }}" enctype="multipart/form-data" class="file-upload">
    {{ csrf_field() }}
    <div class="form-group">
        <label for="description">Beskrivning</label>
        <input type="text" class="form-control" id="description" name="description" value="{{ $indicator->description }}">   
    </div>

    <div class="form-group">
        <label for="measurement">Mätenhet</label>
        <input type="text" class="form-control" id="measurement" name="measurement" value="{{ $indicator->measurement }}">   
    </div>

    <input type="submit" class="btn btn-primary" value="Spara">
</form>