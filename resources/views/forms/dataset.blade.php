<form method="post" action="{{ url('dataset/store') }}" enctype="multipart/form-data" class="file-upload">
    {{ csrf_field() }}
    <input type="file" name="file">

    <input type="submit" class="btn btn-primary" value="Ladda upp">
</form>