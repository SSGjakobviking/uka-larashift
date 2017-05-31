<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\DatasetImporter;
use App\Indicator;
use App\Jobs\ImportDataset;
use App\Tag;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Log;

class DatasetController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth', ['except' => [
            'parse',
        ]]);

        $this->middleware('admin', ['except' => [
            'create',
        ]]);
    }

   public function index(Request $request)
   {
        $datasets = Dataset::has('tags', '<', 1)
                            ->orderBy('created_at', 'desc')
                            ->where('status', null)
                            ->orWhere('status', 'processing')
                            ->get();

        $filter = $request->filter;

        $tags = Tag::with('datasets')
                ->has('datasets')
                ->orderBy('name');

        if ($filter == 'all') {
            return redirect('dataset');
        }

        if (! empty($filter)) {
            $tags->where('id', $filter);
        }

        $allTags = Tag::all();
        
        return view('dataset.index', [
            'datasets' => $datasets,
            'tags' => $tags->get(),
            'allTags' => $allTags,
        ]);
   }

   public function create()
   {
       return view('dataset.create');
   }

    /**
     * Store the uploaded file.
     * 
     * @param  Request $request
     * @return [type]
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,txt',
        ]);

        $file = $request->file('file');

        $name = time() . '-' . $file->getClientOriginalName();

        $file->move(public_path() . '/uploads/', $name);

        $dataset = Dataset::create([
            'user_id'       => auth()->user()->id,
            'indicator_id' => null,
            'status'        => 'processing',
            'file'          => $name,
        ]);

        $job = new ImportDataset($dataset);

        dispatch($job);

        return response()->json(['success' => true]);
    }

    /**
     * Deletes a file in the DB And on the server.
     * 
     * @param  [type] $id
     * @return [type]
     */
    public function destroy($id)
    {
        $file = Dataset::find($id);
        
        unlink(public_path('uploads/' . $file->file));

        Dataset::destroy($id);

        return redirect()->back();
    }

    public function unAttach($id)
    {
        Dataset::where('id', $id)->update([
            'indicator_id' => null,
            'status'       => null,
        ]);

        return redirect()->back();
    }

    public function addTag(Request $request)
    {
        $tag = Tag::firstOrCreate(['name' => $request->tag['name']]);
        $dataset = Dataset::find($request->datasetId);

        if (! $dataset->tags->contains($tag->id)) {
            $dataset->tags()->attach($tag->id);
        }

        return $tag;
    }

    public function deleteTag(Request $request)
    {
        $dataset = Dataset::find($request->datasetId);
        $tag = Tag::where('name', $request->tag['name'])->first();

        if ($tag->datasets->count() == 1) {
            $tag->delete();
        }

        $dataset->tags()->detach($tag->id);

        return $tag->name;
    }
}
