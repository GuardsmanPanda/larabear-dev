<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Integrity\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class BearPhpStanCommand extends Command {
    protected $signature = 'stan';
    protected $description = 'Run PHPStan on the application';

    public function handle(): int {
        $this->info("Running PHPStan on the application");
        exec(command: PHP_BINARY . ' ' . App::basePath(path: 'vendor/bin/') . "phpstan analyse --ansi", output: $res, result_code: $code);
        foreach ($res as $line) {
            $this->output->writeln(messages: $line);
        }
        return $code;
    }
}
