<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\Helpers\DatasetHelper;
use App\Indicator;
use App\IndicatorGroup;
use App\Search;
use App\Status;
use App\Total;
use Elasticsearch\ClientBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class IndicatorController extends Controller
{

    public function __construct()
    {
        $this->middleware(['auth', 'admin'], ['except' => 'all']);
        $this->cacheConfig = config('cache');
    }

    /**
     * Retrieves all indicator groups with their indicators.
     *
     * @return [type]
     */
    public function all(Request $request)
    {
        $allIndicators = Indicator::all();

        if (! $allIndicators) {
            return response()->json(['error' => 'No indicators found.']);
        }

        $status = $this->isPreview($request);

        // loop through all indicators
        $indicators = $allIndicators->map(function($item) use ($status) {
            // retrieve last published dataset (which we are showing by default in the GUI)
            $lastPublishedDataset = DatasetHelper::lastPublishedDataset($item, $status);

            if (! $lastPublishedDataset) {
                return false;
            }

            $url = route('totals', $item->id) . '/?year=' . trim($lastPublishedDataset->year);

            if ($status === 'preview') {
                $url.= '&status=preview';
            }

            return [
                'id' => $item->id,
                'name' => $item->name,
                'most_recent_url' => $url,
                'indicator_group' => $item->indicatorGroup->name,
            ];
        })->groupBy('indicator_group');

        // remove indicators with no datasets
        if ($indicators->has('')) {
            $indicators->forget('');
        }

        return response()->json($indicators);
    }

    /**
     * Returns preview string if status=preview in request.
     * If not return 'published'.
     *
     * @param  [type] $request
     * @return [type]
     */
    private function isPreview($request)
    {
        if ($request->has('status') && $request->get('status') === 'preview') {
            return 'preview';
        }

        return 'published';
    }

    public function index()
    {
        $indicatorGroups = IndicatorGroup::with(['indicators' => function($query) {
                                $query->orderBy('name');
                            }])
                            ->orderBy('name')
                            ->get();

        return view('indicator.index', ['indicatorGroups' => $indicatorGroups]);
    }

    public function update($indicator, Request $request)
    {
        $indicator->update($request->all());

        return redirect()->back();
    }

    public function edit($indicator)
    {
        $noStatusDatasets = Dataset::doesntHave('statuses')->get();

        // retrieve datasets with no status or where status is not preview
        $previewDropdownData = $indicator->datasets()
                        ->whereDoesntHave('statuses', function($query) {
                            $query->preview();
                            $query->orProcessing();
                        })
                        ->orDoesntHave('statuses')
                        ->get();

        // retrieve datasets with no status or where status is not published
        $publishedDropdownData = $indicator->datasets()
                        ->whereDoesntHave('statuses', function($query) {
                            $query->published();
                            $query->orProcessing();
                        })
                        ->orDoesntHave('statuses')
                        ->get();

        $previewData = $indicator->datasets()
                        ->whereHas('statuses', function($query) {
                            $query->preview();
                        })
                        ->with('user')
                        ->orderByDesc('year')
                        ->get();

        $publishedData = $indicator->datasets()
                        ->whereHas('statuses', function($query) {
                            $query->published();
                        })
                        ->with('user')
                        ->orderByDesc('year')
                        ->get();


        $lastPreviewDataset = DatasetHelper::lastPublishedDataset($indicator, 'preview');
        $previewUrl = '';

        if (! is_null($lastPreviewDataset)) {
            $previewUrl = env('APP_PREVIEW_URL') . '?statq=' . urlencode(route('totals', $indicator) . '?year=' . $lastPreviewDataset->year . '&status=preview');
        }

        return view('indicator.edit', [
            'indicator' => $indicator,
            'previewData' => $previewData,
            'publishedData' => $publishedData,
            'previewDropdownData' => $previewDropdownData,
            'publishedDropdownData' => $publishedDropdownData,
            'previewUrl' => $previewUrl,
        ]);
    }

    public function create()
    {
        $indicator = new \stdClass;
        $indicatorGroups = IndicatorGroup::orderBy('name')->get();

        return view('indicator.create', compact('indicator', 'indicatorGroups'));
    }

    public function store(\App\Http\Requests\Indicator $request)
    {
        // create slug out of indicator name
        $request->request->add(['slug' => kebab_case($request->input('name'))]);
        Indicator::create($request->all());
        return redirect('indicator');
    }

    public function destroy($id)
    {
        $indicatorCount = Dataset::where('indicator_id', $id)->count();

        if ($indicatorCount === 0) {
            Indicator::destroy($id);
        }

        return redirect('indicator');
    }

    /**
     * Saves the dataset with the right status depending on the form being used (preview|production).
     *
     * @param  [type]  $id
     * @param  Request $request
     * @return [type]
     */
    public function saveDataset($id, Request $request)
    {
        $input = $this->detectStatus($request);
        $this->updateStatus($id, $request);
       
        if (isset($input['status']) && $input['status'] === 'published') {
            return redirect(request()->headers->get('referer') . '#published');
        }
       
        return redirect()->back();
    }

    private function indexDataset($indicatorId)
    {
        $indicator = Indicator::find($indicatorId);

        $lastPublishedDataset = DatasetHelper::lastPublishedDataset($indicator);

        $client = ClientBuilder::create()->build();

        $search = new Search($client, $indicator, $lastPublishedDataset);

        if ($search->indexExist($indicator->slug)) {

            // fetch one document from elasticsearch to retrieve the dataset id
            $result = $search->one();
            $currentIndexedDataset = $result['_source']['dataset_id'];

            // remove indexed dataset if it matches with the current unattached dataset.
            if ($currentIndexedDataset != $lastPublishedDataset->id) {
                $search->remove();

                if ($lastPublishedDataset) {
                    $search->index();
                }
            }
        } else {
            $search->index();
        }

    }

    /**
     * Issues a request to purge the export app cache for the indicator.
     *
     * @param  [type] $indicatorId
     */
    private function clearExportCache($indicatorId)
    {
        $client = new Client();
        $url = $this->cacheConfig['export_app_cache_purge_url'];
        $client->request('GET', $url . $indicatorId);
    }

    /**
     * Checks if the input is added via preview or production form.
     *
     * @param  [type] $request
     * @return [type]
     */
    private function detectStatus($request)
    {
        $datasets = $request->input('dataset_preview');
        $status = 'preview';

        if (! is_null($request->input('dataset_published'))) {
            $datasets = $request->input('dataset_published');
            $status = 'published';
        }

        return [
            'datasets' => $datasets,
            'status'    => $status,
        ];
    }

    /**
     * Loops through all of the selected datasets and updates their statuses.
     *
     * @param  [type] $id
     * @param  [type] $request
     * @return [type]
     */
    public function updateStatus($id, $request)
    {

        // Retrieve dataset status, preview or production?
        $input = $this->detectStatus($request);

        if (! empty($input['datasets'])) {
            foreach ($input['datasets'] as $datasetId) {
                $dataset = Dataset::where('id', $datasetId)->update([
                    'indicator_id' => $id
                ]);

                $status = Status::where('name', $input['status'])->first(['id']);

                $dataset = Dataset::find($datasetId);

                if (! $dataset->statuses->contains($status)) {
                    $dataset->statuses()->attach($status->id);
                }
            }
        }

        // Index only production dataset in elasticsearch
        if (isset($input['status']) && $input['status'] === 'published') {
            $this->indexDataset($id);
            $this->clearExportCache($id);
        }
    }
}
