<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DatabasePostgresInformation extends DatabaseBaseInformation {
    private string $databaseName;

    public function __construct(string $connectionName) {
        $this->databaseName = Config::get(key: "database.connections.$connectionName.database");
    }

    public function getAllTableNames(): array {
        $res = DB::select(query: "SELECT table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND table_catalog = ?", bindings: [$this->databaseName]);
        return array_column(array: $res, column_key: 'table_name');
    }

    public function getColumnsForTable(string $tableName): array {
        $res = DB::select(query: "SELECT column_name, data_type, is_nullable FROM information_schema.columns WHERE table_catalog = ? AND table_name = ?", bindings: [$this->databaseName, $tableName]);
        $tmp = [];
        foreach ($res as $row) {
            $tmp[] = new InternalEloquentModelColumn(
                columnName: $row->column_name,
                dataType: $this->postgresTypeToPhpType($row->data_type),
                sortOrder: $this->postgresTypeSortOrder($row->column_name),
                isNullable: $row->is_nullable,
                requiredHeader: $this->postgresTypeToPhpHeader($row->data_type),
                eloquentCast: $this->postgresTypeToEloquentCast($row->column_name, $row->data_type)
            );
        }
        return $tmp;
    }


    public function getAllConstraints(): array {
        return DB::select(query: "
            SELECT
                tc.table_name,
                tc.constraint_name,
                tc.constraint_type,
                kcu.column_name,
                ccu.table_name as foreign_table,
                ccu.column_name as foreign_key
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu ON kcu.constraint_name = tc.constraint_name AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name AND ccu.table_schema = tc.table_schema
            WHERE tc.table_catalog = ?
        ", bindings: [$this->databaseName]);
    }


    private function postgresTypeToPhpType(string $postgres_type): string {
        return match ($postgres_type) {
            'date', 'timestamp with time zone' => 'CarbonInterface',
            'text', 'inet', 'cidr', 'uuid' => 'string',
            'integer', 'bigint', 'smallint' => 'int',
            'double precision' => 'float',
            'jsonb' => 'stdClass',
            'boolean' => 'bool',
            default => throw new RuntimeException(message: "Unknown type: $postgres_type")
        };
    }

    private function postgresTypeSortOrder(string $postgres_type): int {
        return match ($postgres_type) {
            'integer', 'bigint', 'smallint' => 0,
            'timestamp with time zone' => 12,
            'date', => 10,
            'text', 'inet', 'cidr', 'uuid' => 6,
            'double precision' => 4,
            'jsonb' => 8,
            'boolean' => 2,
            default => throw new RuntimeException(message: "Unknown type: $postgres_type")
        };
    }

    private function postgresTypeToPhpHeader(string $postgres_type): string {
        return match ($postgres_type) {
            'jsonb' => 'use Infrastructure\Database\Cast\AsJson;' . PHP_EOL . 'use stdClass;',
            'text', 'inet', 'cidr', 'uuid', 'integer', 'bigint', 'smallint', 'double precision', 'boolean' => '',
            'date', 'timestamp with time zone' => 'use Carbon\\CarbonInterface;',
            default => throw new RuntimeException("Unknown type: $postgres_type")
        };
    }

    private function postgresTypeToEloquentCast(string $name, string $postgres_type): ?array {
        if (str_starts_with($name, 'encrypted_')) {
            return [$name, "'encrypted'"];
        }
        return match ($postgres_type) {
            'text', 'inet', 'cidr', 'uuid', 'integer', 'bigint', 'smallint', 'double precision', 'boolean' => null,
            'timestamp with time zone' => [$name, "'immutable_datetime'"],
            'jsonb' => [$name, "AsJson::class"],
            'date' => [$name, "'immutable_date'"],
            default => throw new RuntimeException("Unknown type: $postgres_type")
        };
    }
}
