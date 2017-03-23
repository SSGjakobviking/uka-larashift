<?php

namespace App\Http\Controllers;

use App\Indicator;
use App\DatasetImporter;
use Illuminate\Http\Request;

class DatasetController extends Controller
{

    public function parse()
    {
        
        $path = storage_path('app/uploads/');
        $file = $path . 'registrerade-studenter-2015-16-v1-ny.csv';

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
