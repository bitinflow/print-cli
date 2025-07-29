<?php

namespace App\Commands;

use App\Traits\HasConfig;
use LaravelZero\Framework\Commands\Command;

class Logs extends Command
{
    use HasConfig;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display the print server logs in real-time';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $logFile = '/var/log/print-cli.log';

        if (!file_exists($logFile)) {
            $this->error('Log file not found. Please ensure the print server is running.');
            return;
        }
        $cmd = "tail -f $logFile";
        $descriptorspec = [
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];
        $process = proc_open($cmd, $descriptorspec, $pipes);

        if (is_resource($process)) {
            stream_set_blocking($pipes[1], false);
            stream_set_blocking($pipes[2], false);

            while (true) {
                $out = stream_get_contents($pipes[1]);
                $err = stream_get_contents($pipes[2]);

                if ($out) $this->info(trim($out));
                if ($err) $this->error(trim($err));

                usleep(200000); // 200ms delay to avoid CPU overuse
            }

            // Never reached, but if you need to stop manually:
            // fclose($pipes[1]);
            // fclose($pipes[2]);
            // proc_close($process);
        }
    }
}
