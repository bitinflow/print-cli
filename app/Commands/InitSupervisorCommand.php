<?php

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

class InitSupervisorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'init:supervisor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a supervisor configuration file';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $username = getenv('USER') ?: get_current_user();
        $home = getenv('HOME');

        $ini = <<<INI
[program:print-cli]
directory = $home
command = /usr/bin/php $home/.config/composer/vendor/bin/print-cli serve
autostart = true
autorestart = true
stderr_logfile = /var/log/print-cli.err.log
stdout_logfile = /var/log/print-cli.out.log
stopwaitsecs = 3600
user = $username
INI;

        echo $ini;
    }
}
