<div class="form-group">
    <div class="form-group">
        <label for="name">Namn</label>
        <input type="text" id="name" name="name" value="{{ object_get($indicator, 'name') }}" class="form-control">
    </div>
    
    <div class="form-group">
        <label for="description">Beskrivning</label>
        <textarea type="text" class="form-control" id="description" name="description">{{ object_get($indicator, 'description') }}</textarea>
    </div>
    
    <div class="form-group">
        <label for="indicator_group">Indikatorgrupp</label>
        <select name="indicator_group" id="indicator_group" class="form-control">
            @foreach($indicatorGroups as $indicatorGroup)
                <option value="{{ $indicatorGroup->id }}" {{ (object_get($indicator, 'name') !== null) ? ' selected' : null }}>{{ $indicatorGroup->name }}</option>
            @endforeach
        </select>
    </div>
    
    <div class="form-group">
        <label for="title_config">Rubrik konfiguration</label>
        <p>Exempel:</p>
        <pre>Antal {gender} registrerade studenter {age_group} inom {group_slug}{university}</pre>
        <input type="text" id="title_config" name="title_config" class="form-control" value="{{ object_get($indicator, 'title_config') }}">
    </div>
</div>

<input type="submit" class="btn btn-primary" value="{{ $submitButtonText }}">