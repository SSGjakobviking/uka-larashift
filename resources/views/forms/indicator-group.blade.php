<div class="form-group">
    <label for="name">Namn</label>
    <input type="text" id="name" name="name" value="@isset($indicatorGroup->name){{ $indicatorGroup->name }}@endisset" class="form-control">
</div>

<input type="submit" class="btn btn-primary" value="{{ $submitButtonText }}">