<?php

namespace App\Commands;

use App\Support\PrinterManager;
use LaravelZero\Framework\Commands\Command;

class Printers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'printers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all printers';

    /**
     * Execute the console command.
     */
    public function handle(PrinterManager $printers): int
    {
        $printers = $printers->getList();

        if ($printers->isEmpty()) {
            $this->error('We could not find any printers! Please register them first in CUPS.');
            return 1;
        }

        $this->info('Printer:');

        foreach ($printers as $printer) {
            $this->info($printer->getName());
        }

        return 0;
    }
}
