<?php

namespace App\Commands;

use App\Support\PrinterManager;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

class InitWireguard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:wireguard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize WireGuard configuration';

    /**
     * Execute the console command.
     */
    public function handle(PrinterManager $printer)
    {
        //
    }
}
