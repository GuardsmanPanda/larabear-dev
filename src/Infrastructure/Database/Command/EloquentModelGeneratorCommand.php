<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Command;

use GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal\DatabaseBaseInformation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class EloquentModelGeneratorCommand extends Command {
    protected $signature = 'bear:db-generate';
    protected $description = 'Generate Eloquent Models';

    public function handle(): void {
        foreach (Config::get('bear-dev.eloquent-models') as $connection_name => $model_config) {
            $this->info("Generating Eloquent Models for connection: $connection_name");
            $dbInfo = DatabaseBaseInformation::getInstance($connection_name);
            $table_names = $dbInfo->getAllTableNames();
        }
    }
}