<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class PostgresDatabaseInformation extends BaseDatabaseInformation {
    private string $databaseName;

    public function __construct(string $connectionName) {
        $this->databaseName = Config::get(key: "database.connections.$connectionName.database");
    }

    public function getAllTableNames(): array {
        $res = DB::select(query: "SELECT table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND table_catalog = ?", bindings: [$this->databaseName]);
        return array_column(array: $res, column_key: 'table_name');
    }
}
