<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal;

use Illuminate\Support\Str;
use RuntimeException;

class BuildEloquentModelInternal {
    public static function buildAll(string $connectionName, array $tableConfig): array {
        $dbInfo = DatabaseBaseInformation::getInstance(connectionName: $connectionName);
        $table_names = $dbInfo->getAllTableNames();
        self::checkNoDefinedTablesMissingFromDB(tableConfig: $tableConfig, dbTables: $table_names);

        $models = [];
        foreach ($table_names as $table_name) {
            if (!isset($tableConfig[$table_name])) {
                continue; //skip tables not in the config
            }
            $info = $tableConfig[$table_name];
            $dto = new EloquentModelInternal(
                connectionName: $connectionName,
                tableName: $table_name,
                modelClassName: $info['class'] ?? Str::studly($table_name),
                modelLocation: $info['location'],
                dateFormat: $dbInfo->getDateFormat()
            );

            $columns = $dbInfo->getColumnsForTable(tableName: $table_name);
            foreach ($columns as $column) {
                $dto->addColumn($column);
            }
            $models[$table_name] = $dto;
        }

        foreach ($dbInfo->getAllPrimaryKeys() as $constraint) {
            if (!array_key_exists($constraint->table_name, $models)) {
                continue;
            }
            $dto = $models[$constraint->table_name];
            $dto->setPrimaryKeyInformation(primaryKeyColumnName: $constraint->column_name, primaryKeyType: $dto->getColumns()[$constraint->column_name]->phpDataType);
        }
        return $models;
    }

    private static function checkNoDefinedTablesMissingFromDB(array $tableConfig, array $dbTables): void {
        foreach ($tableConfig as $table_name => $table_config) {
            if (!in_array(needle: $table_name, haystack: $dbTables, strict: true)) {
                throw new RuntimeException(message: "Table [$table_name]not found in database, but is defined in config/bear-dev.php => [eloquent-model-generator]");
            }
        }
    }
}
