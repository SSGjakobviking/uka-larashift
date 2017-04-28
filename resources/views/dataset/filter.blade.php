<div class="tag-filter clearfix">
    <form method="get" method="">        
        <label for="tags">Filtrering</label>

        <select id="tags" name="filter" class="form-control">
            <option value="all">Visa alla</option>
            @foreach($allTags as $tag)
                <option value="{{ $tag->id }}"
                {{ isset($_GET['filter']) && $_GET['filter'] == $tag->id ? ' selected' : '' }}
                >{{ $tag->name }}</option>
            @endforeach
        </select>

        <input type="submit" value="Filtrera" class="btn btn-primary pull-right"></input>
    </form>
</div>