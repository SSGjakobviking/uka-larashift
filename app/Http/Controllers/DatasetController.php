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
        // $file = $path . 'registrerade-studenter-2007-08-v1.csv';
        $file = $path . 'registrerade-studenter-2008-09-v1.csv';

        $indicator = Indicator::firstOrCreate([
            'name'          => 'Antal registrerade studenter',
            'description'   => 'Mäter antalet registrerade studenter per år.',
            'slug'          => 'antal-registrerade-studenter'
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
            'Antal',
            '-21 år',
            '22-24 år',
            '25-29 år',
            '30-34 år',
            '35- år',
        ]);

        $dataset->make();
    }
}
