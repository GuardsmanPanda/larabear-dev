<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal;

use Illuminate\Support\Facades\Config;
use RuntimeException;

abstract class DatabaseBaseInformation {
    public static function getInstance(string $connectionName): DatabaseBaseInformation {
        return match (Config::get(key: "database.connections.$connectionName.driver")) {
            'pgsql' => new DatabasePostgresInformation($connectionName),
            default => throw new RuntimeException(message: 'Unsupported database driver for: ' . $connectionName)
        };
    }

    abstract public function getAllTableNames(): array;
}
