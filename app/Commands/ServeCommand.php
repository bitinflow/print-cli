<?php

namespace App\Commands;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use LaravelZero\Framework\Commands\Command;
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
    public function handle()
    {
        $this->info('Starting service...');

        $yaml = $this->getConfiguration();
        $printerIds = array_map(fn($printer) => $printer['id'], $yaml['printers']);

        $response = Http::patch(sprintf('%s/api/printers/register', $yaml['base_url']), [
            'printers' => $yaml['printers'],
            'status' => 'online',
        ]);

        $response->throw();

        $this->info('Service started!');

        do {
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
        if (!empty($job['data']['preview'])) {
            $this->info(sprintf('Job %s is a preview', $job['id']));
            $this->markCompleted($job, $yaml);
            return;
        }

        $this->markCompleted($job, $yaml);

        $this->info(sprintf('Job %s completed', $job['id']));
    }

    /**
     * @throws RequestException
     */
    private function markCompleted(array $job, mixed $yaml): void
    {
        $response = Http::patch(sprintf('%s/api/printers/jobs/%s/complete', $yaml['base_url'], $job['id']));
        $response->throw();
    }

    /**
     * @throws RequestException
     */
    private function markFailed(array $job, mixed $yaml): void
    {
        $response = Http::patch(sprintf('%s/api/printers/jobs/%s/fail', $yaml['base_url'], $job['id']));
        $response->throw();
    }
}
