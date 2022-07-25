<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\DatasetImporter;
use App\Helpers\DatasetHelper;
use App\Indicator;
use App\Jobs\ImportDataset;
use App\Search;
use App\Status;
use App\Tag;
use App\User;
use Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;

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
        $datasets = Dataset::has('statuses', '<', 1)
                            ->orderBy('created_at', 'desc')
                            ->with('user')
                            ->with('tags')
                            ->get();

        $filter = $request->filter;

        // By default, show all untagged datasets
        $tags = [];

        if (empty($filter)) {
            $filter = auth()->user()->id;
        }

        $tags = User::with(['datasets' => function ($query) {
            $query->has('statuses', '<', 1);
        }])
                ->has('datasets')
                ->orderBy('name');

        $tags->where('id', $filter);
        $tags = $tags->get();

        $allTags = User::all(['id', 'name']);

        return view('dataset.index', [
            'datasets' => $datasets,
            'tags' => $tags,
            'allTags' => $allTags,
            'filter' => $filter,
        ]);
    }

    public function create()
    {
        // $dataset = storage_path('app/uploads/hst-per-amnesomrade[2011-2012]-v1.csv');
        //$dataset = storage_path('app/hst-studieform-amne_Lasar2014_-v1.csv');
        // $dataset = storage_path('app/testdata-simple.csv');
        // $dataset = storage_path('app/testdata.csv');

        // $dataset = Dataset::find(1);
        // $dataset->statuses()->attach(3);
        // $import = new DatasetImporter($dataset);
        // $import->make();
        // dd($import);
        return view('dataset.create');
    }

    /**
     * Store the uploaded file.
     *
     * @param  Request  $request
     * @return [type]
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|mimes:csv,txt',
        ]);

        $file = $request->file('file');

        $name = time().'-'.$file->getClientOriginalName();
        // separate file name by underscore to retrieve the dataset year
        $separateNameFormat = explode('_', $name);
        // year will always be in position 1 in the array, also replace - with / before inserting (2008-09 becomes 2008/09 in the db)
        $year = str_replace('-', '/', $separateNameFormat[1]);
        // move file to uploads folder
        $file->move(public_path().'/uploads/', $name);
        // create the dataset row in db
        $dataset = Dataset::create([
            'user_id' => auth()->user()->id,
            'indicator_id' => null,
            'file' => $name,
            'year' => $year,
        ]);

        // set status to "processing"
        $dataset->statuses()->attach(3);

        // start the job (our parsing of csv and import to db)
        dispatch(new ImportDataset($dataset));

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

        unlink(public_path('uploads/'.$file->file));

        Dataset::destroy($id);

        return redirect()->back();
    }

    /**
     * Unattach a dataset from the indicator, also removes/adds index if necessary.
     *
     * @param  [type] $id
     * @return [type]
     */
    public function unAttach($id, $status)
    {
        $dataset = Dataset::find($id);
        $indicator = $dataset->indicator()->first();

        $status = Status::where('name', $status)->first();

        if ($status) {
            $dataset->statuses()->detach($status);
            $foundStatus = $dataset->statuses()->count();

            if (! $foundStatus) {
                Dataset::where('id', $id)->update([
                    'indicator_id' => null,
                ]);

                $this->removeIndex($indicator, $dataset);
            }
        }

        return redirect(request()->headers->get('referer').'#'.$status->name);
    }

    private function removeIndex($indicator, $dataset)
    {
        $client = ClientBuilder::create()->build();
        $search = new Search($client, $indicator, $dataset);

        // make sure index exist before attempting to remove the index
        if ($search->indexExist($indicator->slug)) {

            // fetch one document from elasticsearch to retrieve the dataset id
            $result = $search->one();
            $currentIndexedDataset = $result['_source']['dataset_id'];

            // remove indexed dataset if it matches with the current unattached dataset.
            if ($currentIndexedDataset == $dataset->id) {
                $lastPublishedDataset = DatasetHelper::lastPublishedDataset($indicator);
                $search->remove();
                // index the last published dataset if current indexed dataset
                // is the one we unattach from the indicator
                if ($lastPublishedDataset) {
                    $search = new Search($client, $indicator, $lastPublishedDataset);
                    $search->index();
                }
            }
        }
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
