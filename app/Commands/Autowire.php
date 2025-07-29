<?php

namespace App\Commands;

use App\Support\PrinterManager;
use App\Traits\HasConfig;
use Exception;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
use Smalot\Cups\Model\Printer;
use Throwable;

class Autowire extends Command
{
    use HasConfig;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autowire {--init : Initialize the configuration when no config file exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically wire up the application';

    /**
     * Execute the console command.
     */
    public function handle(PrinterManager $manager): int
    {
        $this->info('Autowiring the application...');

        $printers = $manager->getList()->map(fn(Printer $printer) => [
            'name' => $printer->getName(),
            'status' => $printer->getStatus(),
            'attributes' => $printer->getAttributes(),
            'uri' => $printer->getUri(),
        ]);

        try {
            if ($this->option('init') && !file_exists($this->getYamlFilename())) {
                $response = Http::acceptJson()->post('https://events.anikeen.com/api/printers/autoinit', [
                    'hostname' => gethostname(),
                ]);

                if ($response->successful()) {
                    $this->setConfig($response->json());
                } else {
                    $this->warn('Unable to auto initialize configuration! Please use `print-cli init` to create a configuration file.');
                }
            }

            $contig = $this->getConfig(expectedVersion: 2);
            $response = Http::acceptJson()->patch(sprintf('%s/api/printers/autowire', $contig->getBaseUrl()), [
                'id' => $contig->getId(),
                'hostname' => gethostname(),
                'printers' => $printers->toArray(),
            ]);

            if ($response->successful()) {
                $newPrinters = $response->json('printers', []);
                if (empty($newPrinters)) {
                    $this->info('No new printers found to autowire.');
                } else {
                    foreach ($newPrinters as $printer) {
                        $this->info(sprintf('Printer: %s', $printer['uri']));
                    }
                }
                $this->setConfig([
                    ...$contig->toArray(),
                    'printers' => $newPrinters,
                ]);
            } else {
                throw new Exception('Failed to autowire printers: ' . $response->body());
            }
        } catch (Throwable $exception) {
            $this->error('Failed to autowire printers: ' . $exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Autowiring completed successfully.');

        return self::SUCCESS;
    }
}
