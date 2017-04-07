<form method="post" action="{{ url('dataset') }}" enctype="multipart/form-data" class="file-upload">
    {{ csrf_field() }}
    <input type="file" name="file">

    <input type="submit" class="btn btn-primary" name="submit" value="Ladda upp">
</form>