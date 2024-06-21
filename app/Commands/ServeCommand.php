<?php

namespace App\Commands;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
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
use Symfony\Component\Yaml\Yaml;
use Throwable;

class ServeCommand extends Command
{
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

    /**
     * Execute the console command.
     * @throws RequestException
     */
    public function handle(): void
    {
        $this->info('Starting service...');

        $yaml = $this->getConfiguration();
        $printerIds = array_map(fn($printer) => $printer['id'], $yaml['printers']);

        $response = Http::patch(sprintf('%s/api/printers/register', $yaml['base_url']), [
            'printers' => $yaml['printers'],
        ]);

        $response->throw();

        $this->info('Service started!');

        do {
            try {
                $response = Http::get(sprintf('%s/api/printers/jobs', $yaml['base_url']), [
                    'printer_ids' => $printerIds,
                ]);

                if ($response->failed()) {
                    $this->error('Failed to fetch jobs, error: ' . $response->status());
                    sleep(2);
                    continue;
                }

                $jobs = $response->json();

                if (empty($jobs)) {
                    continue;
                }

                $this->info('Printing jobs...');

                foreach ($jobs as $job) {
                    try {
                        $this->handleJob($job, $yaml);
                    } catch (Throwable $e) {
                        $this->error($e->getMessage());
                    }
                }

                sleep(2);
            } catch (Throwable $e) {
                $this->error($e->getMessage());
                sleep(2);
            }

        } while (true);
    }

    private function getConfiguration(): array
    {
        $this->info('Reading configuration...');
        $yaml = file_get_contents(base_path('print-cli.yml'));
        return Yaml::parse($yaml);
    }

    /**
     * @throws RequestException
     */
    private function handleJob(array $job, mixed $yaml): void
    {
        $printer = Collection::make($yaml['printers'])->firstWhere('id', $job['printer_id']);
        if (!empty($job['data']['preview'])) {
            $this->info(sprintf('Job %s is a preview', $job['id']));
            $this->markCompleted($job, $yaml, 0);
            return;
        } elseif (empty($job['file_url'])) {
            $this->info(sprintf('Job %s has no file', $job['id']));
            $this->markFailed($job, $yaml, 'No file provided');
            return;
        }

        $paperWidth = $job['data']['paper']['width'] ?? 75;
        $paperHeight = $job['data']['paper']['height'] ?? 75;

        $pointWidth = round($paperWidth * 2.83465, 2);
        $pointHeight = round($paperHeight * 2.83465, 2);

        $client = new Client($printer['username'], $printer['password']);
        $builder = new Builder();
        $responseParser = new ResponseParser();

        $printerManager = new PrinterManager($builder, $client, $responseParser);
        $printer = $printerManager->findByUri($printer['address']);
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
            $this->markFailed($job, $yaml, 'Failed to print job');
            $this->error(sprintf('Failed to print job %s', $job['id']));
            return;
        }

        $this->markCompleted($job, $yaml, $printerJob->getId());

        $this->info(sprintf('Job %s completed as %s', $job['id'], $printerJob->getId()));
    }

    /**
     * @throws RequestException
     */
    private function markCompleted(array $job, mixed $yaml, int $jobId): void
    {
        $response = Http::patch(sprintf('%s/api/printers/jobs/%s/complete', $yaml['base_url'], $job['id']), [
            'job_id' => $jobId,
        ]);
        $response->throw();
    }

    /**
     * @throws RequestException
     */
    private function markFailed(array $job, mixed $yaml, string $reason): void
    {
        $response = Http::patch(sprintf('%s/api/printers/jobs/%s/fail', $yaml['base_url'], $job['id']), [
            'reason' => $reason,
        ]);
        $response->throw();
    }
}
