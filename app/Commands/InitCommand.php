<?php

namespace App\Commands;

use Illuminate\Support\Collection;
use LaravelZero\Framework\Commands\Command;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Model\Printer;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;
use Symfony\Component\Yaml\Yaml;

class InitCommand extends Command
{
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
        $home = getenv('HOME');

        $client = new Client();
        $builder = new Builder();
        $responseParser = new ResponseParser();

        $printerManager = new PrinterManager($builder, $client, $responseParser);

        $printers = Collection::make($printerManager->getList());

        if ($printers->isEmpty()) {
            $this->error('We could not find any printers! Setup them first :)');
            return;
        }

        $this->info('Please register printers first at:');
        $this->info('https://events.anikeen.com/console/resources/printers');

        $password = $this->ask('What is your password?', match ($username) {
            'print-cli' => 'print-cli',
            'orangepi' => 'orangepi',
            default => null,
        });

        $filename = $home . '/print-cli.yml';

        $yaml = Yaml::dump(
            input: [
                'base_url' => 'https://events.anikeen.com',
                'printers' => $printers->map(function (Printer $printer) use ($username, $password) {
                    $attributes = $printer->getAttributes();

                    $id = $this->ask(sprintf('What is your ID for %s?', $printer->getName()));

                    return [
                        'id' => $id,
                        'name' => $printer->getName(),
                        'driver' => 'cups',
                        'address' => $attributes['printer-uri-supported'][0],
                        'username' => $username,
                        'password' => $password,
                    ];
                })->toArray(),
            ],
            inline: 100,
            indent: 2,
            flags: Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK
        );

        file_put_contents(
            filename: $filename,
            data: preg_replace('/-\n\s+/', '- ', $yaml)
        );

        $this->info(sprintf('Created configuration at %s', $filename));
    }
}
