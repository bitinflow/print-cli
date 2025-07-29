<?php

namespace App\Commands;

use App\Traits\HasConfig;
use Illuminate\Support\Collection;
use LaravelZero\Framework\Commands\Command;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;

class Init extends Command
{
    use HasConfig;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a print-cli configuration file';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $username = getenv('USER') ?: get_current_user();

        $client = new Client();
        $builder = new Builder();
        $responseParser = new ResponseParser();

        $printerManager = new PrinterManager($builder, $client, $responseParser);

        $printers = Collection::make($printerManager->getList());

        if ($printers->isEmpty()) {
            $this->error('We could not find any printers! Setup them first :)');
            return;
        }

        $this->info('Please register the print-server first at:');
        $this->info('https://events.anikeen.com/console/resources/print-servers');

        $id = $this->ask('What is your print-server ID?');

        $password = $this->ask('What is your password?', match ($username) {
            'print-cli' => 'print-cli',
            'orangepi' => 'orangepi',
            default => null,
        });

        $this->setConfig([
            'version' => 2,
            'id' => $id,
            'base_url' => 'https://events.anikeen.com',
            'default_credentials' => [
                'username' => $username,
                'password' => $password,
            ],
        ]);
    }
}
