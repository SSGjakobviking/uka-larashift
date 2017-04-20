<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\DatasetImporter;
use App\Indicator;
use App\Jobs\ImportDataset;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class DatasetController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth', ['except' => [
            'parse',
        ]]);
    }

   public function index()
   {
        $datasets = Dataset::orderBy('created_at', 'desc')->where('indicator_id', null)->get();
        return view('dataset.index', ['datasets' => $datasets]);
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

            $delay = $this->processingDatasetsCount() * 10;
            
            $job = (new ImportDataset($dataset))->delay(Carbon::now()->addMinutes($delay));

            dispatch($job);
    }

    public function processingDatasetsCount()
    {
        return Dataset::where('status', 'processing')->count();
    }

    // public function parse()
    // {
    //     $dataset = Dataset::where('status', 'processing')->first();

    //     $job = (new ImportDataset($dataset))->delay(Carbon::now()->addMinutes(10));

    //     dispatch($job);
    // }

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
}
