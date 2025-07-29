<?php

namespace App\Commands;

use App\Support\Config;
use App\Traits\HasConfig;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LaravelZero\Framework\Commands\Command;
use Smalot\Cups\Builder\Builder;
use Smalot\Cups\Manager\JobManager;
use Smalot\Cups\Manager\PrinterManager;
use Smalot\Cups\Model\Job;
use Smalot\Cups\Transport\Client;
use Smalot\Cups\Transport\ResponseParser;
use Throwable;

class Serve extends Command
{
    use HasConfig;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'serve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $counter = 0;

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Starting service...');
        $this->monitor();
    }

    /**
     * Monitor the service and handle jobs.
     */
    private function monitor(): void
    {
        while (true) {
            // autowire every 10 iterations
            if ($this->counter % 10 === 0) {
                try {
                    $this->call('autowire', [
                        '--init' => $this->counter === 0, // Initialize only on the first run
                    ]);
                } catch (Throwable $e) {
                    $this->error('Autowire failed: ' . $e->getMessage());
                }
            }

            try {
                $config = $this->getConfig(expectedVersion: 2);
                $this->fetchJobs($config);
                sleep(2);
            } catch (ConnectionException $e) {
                $this->error('Connection error: ' . $e->getMessage());
                sleep(2);
            } catch (RequestException $e) {
                $this->error('Request error: ' . $e->getMessage());
                sleep(2);
            } catch (Throwable $e) {
                $this->error('An unexpected error occurred: ' . $e->getMessage());
                sleep(2);
            }

            $this->counter++;
        }
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    private function fetchJobs(Config $config): void
    {
        $printerIds = $config->getPrinters()->pluck('id')->toArray();
        $response = Http::acceptJson()->get(sprintf('%s/api/printers/jobs', $config->getBaseUrl()), [
            'printer_ids' => $printerIds,
        ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        $jobs = $response->json();

        if (empty($jobs)) {
            return;
        }

        $this->info('Printing jobs...');

        foreach ($jobs as $job) {
            try {
                $this->handleJob($config, $job);
            } catch (Throwable $e) {
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    private function handleJob(Config $config, array $job): void
    {
        $printer = $config->getPrinters()->firstWhere('id', $job['printer_id']);
        [$username, $password] = $this->getConfig()->getPrinterCredentials($printer);

        if (!empty($job['data']['preview'])) {
            $this->info(sprintf('Job %s is a preview', $job['id']));
            $this->markCompleted($config, $job, 0);
            return;
        } elseif (empty($job['file_url'])) {
            $this->info(sprintf('Job %s has no file', $job['id']));
            $this->markFailed($config, $job, 'No file provided');
            return;
        }

        $paperWidth = $job['data']['paper']['width'] ?? 75;
        $paperHeight = $job['data']['paper']['height'] ?? 75;

        $pointWidth = round($paperWidth * 2.83465, 2);
        $pointHeight = round($paperHeight * 2.83465, 2);

        $client = new Client($username, $password);
        $builder = new Builder();
        $responseParser = new ResponseParser();

        $printerManager = new PrinterManager($builder, $client, $responseParser);
        $printer = $printerManager->findByUri($printer['uri']);
        $jobManager = new JobManager($builder, $client, $responseParser);

        $content = file_get_contents($job['file_url']);
        Storage::put($filename = sprintf('pdfs/%s.pdf', Str::random(16)), $content);

        $printerJob = new Job();
        $printerJob->setName(sprintf('job-%s', $job['id']));
        $printerJob->setCopies(1);
        $printerJob->setPageRanges('1');
        $printerJob->addFile(Storage::path($filename));
        $printerJob->addAttribute('media', "Custom.{$pointWidth}x{$pointHeight}");
        $printerJob->addAttribute('fit-to-page', true);

        if (!$jobManager->send($printer, $printerJob)) {
            $this->markFailed($config, $job, 'Failed to print job');
            $this->error(sprintf('Failed to print job %s', $job['id']));
            return;
        }

        $this->markCompleted($config, $job, $printerJob->getId());

        $this->info(sprintf('Job %s completed as %s', $job['id'], $printerJob->getId()));
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    private function markCompleted(Config $config, array $job, int $jobId): void
    {
        $response = Http::acceptJson()
            ->patch(sprintf('%s/api/printers/jobs/%s/complete', $config->getBaseUrl(), $job['id']), [
                'job_id' => $jobId,
            ]);
        $response->throw();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    private function markFailed(Config $config, array $job, string $reason): void
    {
        Http::acceptJson()
            ->patch(sprintf('%s/api/printers/jobs/%s/fail', $config->getBaseUrl(), $job['id']), [
                'reason' => $reason,
            ])
            ->throw();
    }
}
