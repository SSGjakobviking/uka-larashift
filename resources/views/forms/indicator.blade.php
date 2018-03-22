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
                <option value="{{ $indicatorGroup->id }}" {{ (object_get($indicator, 'name') !== null && $indicator->indicator_group === $indicatorGroup->id) ? ' selected' : null }}>{{ $indicatorGroup->name }}</option>
            @endforeach
        </select>
    </div>
    
    <div class="form-group">
        <label for="title_config">Rubrik konfiguration</label>
        <input type="text" id="title_config" name="title_config" class="form-control" value="{{ object_get($indicator, 'title_config') }}">
        
        <p class="indicator-example">Respektive indikator kan filtreras på kön, åldersgrupp, ämnesområde/verksamhetsområde och lärosäte. För att specificera dessa kan du ange:<br><br><i>{gender}  = kvinnor/män<br>{age_group} = de åldersgrupper som är specificerade i datasetten<br>{group_slug} = de ämnesområden/verksamhetsområden med underavdelningar som är specificerade i datasetten<br>{university} = de lärosäten som är specificerade i datasetten<br><br></i></p>
        
        <p class="indicator-example">Exempel #1: Indikator grupperat på kön, åldersgrupp, ämnesområde samt lärosäte.</p>
<pre>Konfiguration:<span><br>Antal registrerade studenter <strong>{gender}</strong> <strong>{age_group}</strong> inom <strong>{group_slug}{university}</strong></span>
            <br>Rubrik:<span><br>Antal registrerade studenter <strong>(kvinnor)</strong> <strong>i åldersgruppen 25-34 år</strong> inom <strong>beteendevetenskap</strong> <strong>vid Blekinge tekniska högskola</strong></span></pre>

        <p class="indicator-example">Exempel #2: Indikator grupperat på verksamhetsområde och lärosäte.</p>
        <pre>Konfiguration:<span><br>Summa intäkter inom <strong>{group_slug}{university}</strong></span>
            <br>Rubrik:<span><br>Summa intäkter inom <strong>annan verksamhet</strong> <strong>vid Blekinge tekniska högskola</strong></span></pre>

        <p class="indicator-example">Exempel #3: Indikator grupperat på lärosäte.</p>
        <pre>Konfiguration:<span><br>Andel med utländsk bakgrund <strong>{university}</strong></span>
            <br>Rubrik:<span><br>Andel med utländsk bakgrund <strong>vid Blekinge tekniska högskola</strong></span></pre>
    </div>
</div>

<input type="submit" class="btn btn-primary" value="{{ $submitButtonText }}">