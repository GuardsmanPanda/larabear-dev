<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal;

use Illuminate\Support\Facades\App;
use Ramsey\Collection\Set;

class EloquentModelInternal {
    private array $primaryKeyColumns = [];
    private string $primaryKeyType;
    private bool $timestamps = false;
    private Set $headers;

    /**
     * @var array<string, EloquentModelColumnInternal>
     */
    private array $columns = [];

    public function __construct(
        private readonly string $connectionName,
        private readonly string $tableName,
        private readonly string $modelClassName,
        private readonly string $modelLocation,
        private readonly string $dateFormat
    ) {
        $this->headers = new Set(setType: 'string');
        $this->headers->add('use Illuminate\Database\Query\Builder;');
        $this->headers->add('use Illuminate\Database\Eloquent\Model;');
        $this->headers->add('use Illuminate\Database\Eloquent\Collection;');
        $this->headers->add('use Closure;');
    }

    public function getTableName(): string {
        return $this->tableName;
    }

    public function getModelClassName(): string {
        return $this->modelClassName;
    }

    public function getModelLocation(): string {
        return $this->modelLocation;
    }

    public function getModelDirectory(): string {
        return App::basePath(path: trim(string: $this->modelLocation, characters: '/'));
    }

    public function getModelPath(): string {
        return App::basePath(path: trim(string: $this->modelLocation, characters: '/') . '/') . $this->modelClassName . '.php';
    }

    public function getNameSpace(): string {
        return str_replace('/', '\\', ucfirst($this->modelLocation));
    }


    public function hasCompositePrimaryKey(): bool {
        return count($this->primaryKeyColumns) > 1;
    }

    public function getPrimaryKeyColumns(): array {
        return $this->primaryKeyColumns;
    }

    public function setPrimaryKeyInformation(string $primaryKeyColumnName, string $primaryKeyType): void {
        $this->primaryKeyColumns[] = $primaryKeyColumnName;
        $this->primaryKeyType = $primaryKeyType;
    }


    public function addHeader(string $header): void {
        $this->headers->add($header);
    }

    public function addColumn(EloquentModelColumnInternal $column): void {
        $this->headers->add($column->requiredHeader);
        if ($column->columnName === 'delete_at') {
            $this->headers->add('use Illuminate\Database\Eloquent\SoftDeletes;');
        }
        if ($column->columnName === 'updated_at') {
            $this->timestamps = true;
        }
        $this->columns[$column->columnName] = $column;
    }

    /**
     * @return array<string, EloquentModelColumnInternal>
     */
    public function getColumns(): array {
        return $this->columns;
    }

    public function getCasts(): array {
        $casts = [];
        foreach ($this->columns as $column) {
            if ($column->eloquentCast !== null) {
                $casts[$column->columnName] = $column->eloquentCast;
            }
        }
        ksort(array: $casts);
        return $casts;
    }


    public function getClassContent(): string {
        $content = $this->getTopOfClass();
        $content .= "class " . $this->getModelClassName() . " extends Model {" . PHP_EOL;
        $content .= "    protected \$connection = '$this->connectionName';" . PHP_EOL;
        $content .= "    protected \$table = '$this->tableName';" . PHP_EOL;


        if (count($this->primaryKeyColumns) === 1) {
            $primaryKeyColumn = $this->primaryKeyColumns[0];
            $content .= "    protected \$primaryKey = '$primaryKeyColumn';" . PHP_EOL;
            if ($this->primaryKeyType !== 'int') {
                $content .= "    protected \$keyType = '$this->primaryKeyType';" . PHP_EOL;
            }
            if ($primaryKeyColumn !== 'id' || $this->primaryKeyType !== 'int') {
                $content .= "    public \$incrementing = false;" . PHP_EOL;
            }
        } else {
            $content .= "    protected \$primaryKey = ['" . implode(separator: "', '", array: $this->primaryKeyColumns) . "'];" . PHP_EOL;
            $content .= "    protected \$keyType = 'array';" . PHP_EOL;
            $content .= "    public \$incrementing = false;" . PHP_EOL;
        }


        $content .= "    protected \$dateFormat = '$this->dateFormat';" . PHP_EOL;
        if ($this->timestamps === false) {
            $content .= "    public \$timestamps = false;" . PHP_EOL;
        }
        $content .= PHP_EOL;


        $casts = $this->getCasts();
        if (count($casts) > 0) {
            $content .= "    protected \$casts = [" . PHP_EOL;
            foreach ($casts as $col_name => $cast_target) {
                $content .= "        '$col_name' => $cast_target," . PHP_EOL;
            }
            $content .= "    ];" . PHP_EOL . PHP_EOL;
        }

        $content .= "    protected \$guarded = ['" . implode(separator: "', '", array: $this->primaryKeyColumns) . "', 'updated_at', 'created_at', 'deleted_at'];" . PHP_EOL;

        if ($this->hasCompositePrimaryKey()) {
            $content .= $this->getCompositeKeyMethods();
        }

        $content .= "}" . PHP_EOL;
        return $content;
    }


    private function getTopOfClass(): string {
        $content = "<?php" . PHP_EOL . PHP_EOL . 'namespace ' . $this->getNameSpace() . ';' . PHP_EOL . PHP_EOL;
        if ($this->hasCompositePrimaryKey()) {
            $this->headers->add('use Illuminate\Database\Eloquent\ModelNotFoundException;');
            $this->headers->add('use Illuminate\Database\Eloquent\Builder as EloquentBuilder;');
            $this->headers->add('use RuntimeException;');
        }

        $hh = $this->headers->toArray();
        sort($hh);
        $hh = array_unique(array_map(static function ($ele) {
            return trim($ele);
        }, $hh));
        foreach ($hh as $header) {
            if ($header === '') {
                continue;
            }
            $content .= $header . PHP_EOL;
        }

        $content .= PHP_EOL . "/**" . PHP_EOL;
        $content .= " * AUTO GENERATED FILE DO NOT MODIFY" . PHP_EOL;
        $content .= " *" . PHP_EOL;
        if ($this->hasCompositePrimaryKey()) {
            $content .= " * @method static $this->modelClassName findOrNew(array \$ids, array \$columns = ['*'])" . PHP_EOL;
        } else {
            $content .= " * @method static $this->modelClassName|null find($this->primaryKeyType \$id, array \$columns = ['*'])" . PHP_EOL;
            $content .= " * @method static $this->modelClassName findOrFail($this->primaryKeyType \$id, array \$columns = ['*'])" . PHP_EOL;
            $content .= " * @method static $this->modelClassName findOrNew($this->primaryKeyType \$id, array \$columns = ['*'])" . PHP_EOL;
        }
        $content .= " * @method static $this->modelClassName sole(array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static $this->modelClassName|null first(array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static $this->modelClassName firstOrFail(array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static $this->modelClassName firstOrCreate(array \$filter, array \$values)" . PHP_EOL;
        $content .= " * @method static $this->modelClassName firstOrNew(array \$filter, array \$values)" . PHP_EOL;
        $content .= " * @method static $this->modelClassName firstWhere(string \$column, string \$operator = null, string \$value = null, string \$boolean = 'and')" . PHP_EOL;
        $content .= " * @method static Collection|$this->modelClassName all(array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static Collection|$this->modelClassName fromQuery(string \$query, array \$bindings = [])" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName lockForUpdate()" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName select(array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName with(array  \$relations)" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName leftJoin(string \$table, string \$first, string \$operator = null, string \$second = null)" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName where(string \$column, string \$operator = null, string \$value = null, string \$boolean = 'and')" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereExists(Closure \$callback, string \$boolean = 'and', bool \$not = false)" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereNotExists(Closure \$callback, string \$boolean = 'and')" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereHas(string \$relation, Closure \$callback, string \$operator = '>=', int \$count = 1)" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereIn(string \$column, array \$values, string \$boolean = 'and', bool \$not = false)" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereNull(string|array \$columns, string \$boolean = 'and')" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereNotNull(string|array \$columns, string \$boolean = 'and')" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereRaw(string \$sql, array \$bindings = [], string \$boolean = 'and')" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName orderBy(string \$column, string \$direction = 'asc')" . PHP_EOL;
        $content .= " *" . PHP_EOL;

        usort(array: $this->columns, callback: static function ($a, $b) {
            if ($a->sortOrder === $b->sortOrder) {
                return strlen(string: $a->columnName) - strlen(string: $b->columnName) === 0 ? strcmp(string1: $a->columnName, string2: $b->columnName) : strlen(string: $a->columnName) - strlen(string: $b->columnName);
            }
            return $a->sortOrder - $b->sortOrder;
        });
        foreach ($this->columns as $column) {
            $content .= " * @property " . $column->phpDataType;
            if ($column->isNullable) {
                $content .= "|null";
            }
            $content .= " $" . $column->columnName . PHP_EOL;
        }

        $content .= " *" . PHP_EOL;
        $content .= " * AUTO GENERATED FILE DO NOT MODIFY" . PHP_EOL;
        $content .= " */" . PHP_EOL;
        return $content;
    }


    private function getCompositeKeyMethods(): string {
        $content = PHP_EOL . PHP_EOL;

        $content .= "    public function getKey(): array {" . PHP_EOL;
        $content .= "        \$attributes = [];" . PHP_EOL;
        $content .= "        foreach (\$this->primaryKey as \$key) {" . PHP_EOL;
        $content .= "            \$attributes[\$key] = \$this->getAttribute(\$key);" . PHP_EOL;
        $content .= "        }" . PHP_EOL;
        $content .= "        return \$attributes;" . PHP_EOL;
        $content .= "    }" . PHP_EOL  . PHP_EOL;


        $content .= "    /**" . PHP_EOL;
        $content .= "     * @param array<string, string> \$ids # Ids in the form ['key1' => 'value1', 'key2' => 'value2']" . PHP_EOL;
        $content .= "     * @param array<string> \$columns" . PHP_EOL;
        $content .= "     * @return $this->modelClassName|null" . PHP_EOL;
        $content .= "     */" . PHP_EOL;
        $content .= "    public static function find(array \$ids, array \$columns = ['*']): $this->modelClassName|null {" . PHP_EOL;
        $content .= "        \$me = new self;" . PHP_EOL;
        $content .= "        \$query = \$me->newQuery();" . PHP_EOL;
        $content .= "        foreach (\$me->primaryKey as \$key) {" . PHP_EOL;
        $content .= "            \$query->where(column: \$key, operator: '=', value: \$ids[\$key]);" . PHP_EOL;
        $content .= "        }" . PHP_EOL;
        $content .= "        \$result = \$query->first(\$columns);" . PHP_EOL;
        $content .= "        return \$result instanceof self ? \$result : null;" . PHP_EOL;
        $content .= "    }" . PHP_EOL . PHP_EOL;


        $content .= "    /**" . PHP_EOL;
        $content .= "     * @param array<string, string> \$ids # Ids in the form ['key1' => 'value1', 'key2' => 'value2']" . PHP_EOL;
        $content .= "     * @param array<string> \$columns" . PHP_EOL;
        $content .= "     * @return $this->modelClassName" . PHP_EOL;
        $content .= "     */" . PHP_EOL;
        $content .= "    public static function findOrFail(array \$ids, array \$columns = ['*']): $this->modelClassName {" . PHP_EOL;
        $content .= "        \$result = self::find(ids: \$ids, columns: \$columns);" . PHP_EOL;
        $content .= "        return \$result ?? throw (new ModelNotFoundException())->setModel(model: __CLASS__, ids: \$ids);" . PHP_EOL;
        $content .= "    }" . PHP_EOL . PHP_EOL;


        $content .= "    protected function setKeysForSaveQuery(\$query): EloquentBuilder { " . PHP_EOL;
        $content .= "        foreach (\$this->primaryKey as \$key) {" . PHP_EOL;
        $content .= "            if (isset(\$this->\$key)) {" . PHP_EOL;
        $content .= "                \$query->where(column: \$key, operator: '=', value: \$this->\$key);" . PHP_EOL;
        $content .= "            } else {" . PHP_EOL;
        $content .= "                throw RuntimeException::create(message: 'Missing primary key value for \$key');" . PHP_EOL;
        $content .= "            }" . PHP_EOL;
        $content .= "        }" . PHP_EOL;
        $content .= "        return \$query;" . PHP_EOL;
        $content .= "    }" . PHP_EOL;

        return $content;
    }
}
