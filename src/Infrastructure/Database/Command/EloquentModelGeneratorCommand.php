<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Command;

use GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal\BuildEloquentModelInternal;
use GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal\DatabaseBaseInformation;
use GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal\EloquentModelInternal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use RuntimeException;

class EloquentModelGeneratorCommand extends Command {
    protected $signature = 'bear:db';
    protected $description = 'Generate Eloquent Models';

    public function handle(): void {
        $connections = Config::get('bear-dev.eloquent-model-generator');
        if (empty($connections)) {
            throw new RuntimeException(message: 'No database connections defined in config/bear-dev.php => [eloquent-model-generator]');
        }
        foreach ($connections as $connection_name => $table_config) {
            $this->info(string: "Generating Eloquent Models for connection: $connection_name");
            $models = BuildEloquentModelInternal::buildAll(connectionName: $connection_name, tableConfig: $table_config);
            foreach ($models as $model) {
                $this->info(string: "  Generating Eloquent Model for table: " . $model->getTableName());
                $dir = $model->getModelDirectory();
                if (!is_dir($dir)) {
                    $this->info(string: "    Creating directory: $dir");
                    if (!mkdir($dir) && !is_dir($dir)) {
                        throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
                    }
                }
                file_put_contents(filename: $model->getModelPath(), data: $model->getClassContent());
            }
        }
    }
}
