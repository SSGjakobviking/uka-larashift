<?php

namespace App\Http\Controllers;

use App\Dataset;
use App\DatasetImporter;
use App\Indicator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;

class DatasetController extends Controller
{

   public function index()
   {
        $datasets = Dataset::orderBy('created_at', 'desc')->get();
        return view('dataset.index', ['datasets' => $datasets]);
   }

    /**
     * Store the uploaded file.
     * 
     * @param  Request $request
     * @return [type]
     */
    public function store(Request $request)
    {
        try {
            $file = $request->file('file');

            if (! $file) {
                throw new \Exception('Du måste välja en fil att ladda upp.');
            }

            $name = $file->getClientOriginalName();

            $file->move(public_path() . '/uploads/', time() . '-' . $name);

            Dataset::create([
                'user_id'       => auth()->user()->id,
                'indicator_id' => null,
                'file'          => $name
            ]);
        } catch(\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->back()->with('success', 'Din fil har laddats upp!');
    }

    public function destroy($id)
    {
        Dataset::destroy($id);

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
