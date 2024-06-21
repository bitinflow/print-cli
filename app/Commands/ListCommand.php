<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

class ListCommand extends Command
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
     *
     * @return int
     */
    public function handle(): int
    {
        $client = new Client();
        $builder = new Builder();
        $responseParser = new ResponseParser();

        $printerManager = new PrinterManager($builder, $client, $responseParser);

        $printers = $printerManager->getList();

        $this->info('Printers:');

        foreach ($printers as $printer) {
            $this->info($printer->getName());
        }

        return 0;
    }
}
