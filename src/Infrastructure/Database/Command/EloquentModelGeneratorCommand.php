<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Command;

use GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal\DatabaseBaseInformation;
use GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal\EloquentModelInternal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use RuntimeException;

class EloquentModelGeneratorCommand extends Command {
    protected $signature = 'bear:db-generate';
    protected $description = 'Generate Eloquent Models';

    public function handle(): void {
        $connections = Config::get('bear-dev.eloquent-model-generator');
        if (empty($connections)) {
            throw new RuntimeException(message: 'No database connections defined in config/bear-dev.php => [eloquent-model-generator]');
        }
        foreach ($connections as $connection_name => $connection_config) {
            $this->info(string: "Generating Eloquent Models for connection: $connection_name");
            $dbInfo = DatabaseBaseInformation::getInstance(connectionName: $connection_name);
            $table_names = $dbInfo->getAllTableNames();
            $this->checkNoDefinedTablesMissingFromDB(connection_config: $connection_config, dbTables: $table_names);

            $models = [];
            foreach ($table_names as $table_name) {
                if (!isset($connection_config[$table_name])) {
                    continue; //skip tables not in the config
                }
                $info = $connection_config[$table_name];
                $dto = new EloquentModelInternal(
                    tableName: $table_name,
                    modelClassName: $info['class'] ?? Str::studly($table_name),
                    modelLocation: $info['location']
                );

                $columns = $dbInfo->getColumnsForTable(tableName: $table_name);
                foreach ($columns as $column) {
                    $dto->addColumn($column);
                }
                $models[$table_name] = $dto;
            }

            foreach ($dbInfo->getAllConstraints() as $constraint) {
                if (!array_key_exists($constraint->table_name, $models)) {
                    continue;
                }
                $dto = $models[$constraint->table_name];
                if ($constraint->constraint_type === 'PRIMARY KEY') {
                    $dto->setPrimaryKeyInformation(primaryKeyColumnName: $constraint->column_name, primaryKeyType: $dbInfo->databaseTypeToPhpType($constraint->data_type));
                }
            }

            foreach ($models as $model) {
                $this->info(string: "  Generating Eloquent Model for table: " . $model->getTableName());
                file_put_contents(filename: $model->getModelPath(), data: $model->getClassContent());
            }
        }
    }


    private function checkNoDefinedTablesMissingFromDB(array $connection_config, array $dbTables): void {
        foreach ($connection_config as $table_name => $table_config) {
            if (!in_array(needle: $table_name, haystack: $dbTables, strict: true)) {
                throw new RuntimeException(message: "Table $table_name not found in database, but is defined in config/bear-dev.php => [eloquent-model-generator]");
            }
        }
    }
}
