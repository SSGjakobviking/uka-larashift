<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\DatasetImporter;
use App\Indicator;
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
        // try {


            $this->validate($request, [
                'file' => 'required|mimes:csv,txt',
            ]);

            $file = $request->file('file');

            $name = time() . '-' . $file->getClientOriginalName();

            $file->move(public_path() . '/uploads/', $name);

            Dataset::create([
                'user_id'       => auth()->user()->id,
                'indicator_id' => null,
                'file'          => $name
            ]);
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

    public function parse()
    {
        
        $path = storage_path('app/uploads/');
        $file = $path . 'registrerade-studenter-2016-17-v1-ny.csv';

        $indicator = Indicator::firstOrCreate([
            'name'          => 'Antal registrerade studenter',
            'description'   => 'Mäter antalet registrerade studenter per år.',
            'slug'          => 'antal-registrerade-studenter',
            'measurement'   => 'Antal registrerade studenter.',
            'time_unit'     => 'År',
        ]);
        
        $dataset = new DatasetImporter($file, $indicator);
        
        // define group columns
        $dataset->groupColumns([
            'Ämnesområde',
            'Ämnesdelsområde',
            'Ämnesgrupp',
        ]);

        $dataset->totalColumns([
            '-24',
            '25-34',
            '35-',
            'Total',
        ]);

        $dataset->make();
    }
}
