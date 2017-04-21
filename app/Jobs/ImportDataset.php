<?php

namespace App\Jobs;

use App\DatasetImporter;
use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportDataset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $dataset;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($dataset)
    {
        $this->dataset = $dataset;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // dd($this->user);
        $path = public_path('uploads/');

        Log::info('Started importing dataset: ' . $this->dataset->file);

        $dataset = new DatasetImporter($this->dataset);
        
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

        Log::info('Import complete!');
    }
}
