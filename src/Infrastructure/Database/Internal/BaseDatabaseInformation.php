<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal;

use Illuminate\Support\Facades\Config;
use RuntimeException;

abstract class BaseDatabaseInformation {
    public static function getInstance(string $connectionName): BaseDatabaseInformation {
        return match (Config::get(key: "database.connections.$connectionName.driver")) {
            'pgsql' => new PostgresDatabaseInformation($connectionName),
            default => throw new RuntimeException(message: 'Unsupported database driver for: ' . $connectionName)
        };
    }

    abstract public function getAllTableNames(): array;
}
