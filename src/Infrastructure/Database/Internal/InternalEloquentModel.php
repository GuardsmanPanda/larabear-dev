<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal;

use Illuminate\Support\Facades\App;
use Ramsey\Collection\Set;

class InternalEloquentModel {
    private string $primaryKeyColumnName;
    private string $primaryKeyType;
    private bool $timestamps = false;
    private Set $headers;

    /**
     * @var array<string, InternalEloquentModelColumn>
     */
    private array $columns = [];

    public function __construct(
        private readonly string $tableName,
        private readonly string $modelClassName,
        private readonly string $modelLocation
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

    public function getModelPath(): string {
        return App::basePath(path: trim(string: $this->modelLocation, characters: '/') . '/') . $this->modelClassName . '.php';
    }

    public function getNameSpace(): string {
        return str_replace('/', '\\', ucfirst($this->modelLocation));
    }

    public function getPrimaryKeyColumnName(): string {
        return $this->primaryKeyColumnName;
    }


    public function setPrimaryKeyInformation(string $primaryKeyColumnName, string $primaryKeyType): void {
        $this->primaryKeyColumnName = $primaryKeyColumnName;
        $this->primaryKeyType = $primaryKeyType;
    }

    public function addHeader(string $header): void {
        $this->headers->add($header);
    }

    public function addColumn(InternalEloquentModelColumn $column): void {
        $this->headers->add($column->requiredHeader);
        if ($column->columnName === 'delete_at') {
            $this->headers->add('use Illuminate\Database\Eloquent\SoftDeletes;');
        }
        if ($column->columnName === 'updated_at') {
            $this->timestamps = true;
        }
        $this->columns[$column->columnName] = $column;
    }

    public function getCasts(): array {
        $casts = [];
        foreach ($this->columns as $column) {
            if ($column->eloquentCast !== null) {
                $casts[$column->columnName] = $column->eloquentCast;
            }
        }
        sort(array: $casts);
        return $casts;
    }


    public function getClassContent(): string {
        $content = $this->getTopOfClass();
        $content .= "class " . $this->getModelClassName() . " extends Model {" . PHP_EOL;

        $content .= "    protected \$table = '$this->tableName';" . PHP_EOL;
        if ($this->timestamps === false) {
            $content .= "    public \$timestamps = false;" . PHP_EOL;
        }
        $casts = $this->getCasts();
        if (count($casts) > 0) {
            $content .= "    protected \$casts = [" . PHP_EOL;
            foreach ($casts as $col_name => $cast_target) {
                $content .= "        '$col_name' => '$cast_target'," . PHP_EOL;
            }
            $content .= "    ];" . PHP_EOL . PHP_EOL;
        }

        $content .= "    protected \$guarded = ['id','updated_at','created_at','deleted_at'];" . PHP_EOL;
        $content .= "}" . PHP_EOL;
        return $content;
    }


    private function getTopOfClass(): string {
        $content = "<?php" . PHP_EOL . PHP_EOL . 'namespace ' . $this->getNameSpace() . ';' . PHP_EOL . PHP_EOL;

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
        $content .= " * @method static $this->modelClassName |null find($this->primaryKeyType \$id, array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static $this->modelClassName findOrFail($this->primaryKeyType \$id, array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static $this->modelClassName findOrNew($this->primaryKeyType \$id, array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static $this->modelClassName sole(array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static $this->modelClassName|null first(array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static $this->modelClassName firstOrFail(array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static $this->modelClassName firstOrCreate(array \$filter, array \$values)" . PHP_EOL;
        $content .= " * @method static $this->modelClassName firstOrNew(array \$filter, array \$values)" . PHP_EOL;
        $content .= " * @method static $this->modelClassName|null firstWhere(string \$column, string \$operator = null, string \$value = null, string \$boolean = 'and')" . PHP_EOL;
        $content .= " * @method static Collection|$this->modelClassName all(array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static Collection|$this->modelClassName fromQuery(string \$query, array \$bindings = [])" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName lockForUpdate()" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName select(array \$columns = ['*'])" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName with(array  \$relations)" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName leftJoin(string \$table, string \$first, string \$operator = null, string \$second = null)" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName where(string \$column, string \$operator = null, string \$value = null, string \$boolean = 'and')" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereIn(string \$column, array \$values, string \$boolean = 'and', bool \$not = false)" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereHas(string \$relation, Closure \$callback, string \$operator = '>=', int \$count = 1)" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereNull(string|array \$columns, string \$boolean = 'and')" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereNotNull(string|array \$columns, string \$boolean = 'and')" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName whereRaw(string \$sql, array \$bindings, string \$boolean = 'and')" . PHP_EOL;
        $content .= " * @method static Builder|$this->modelClassName orderBy(string \$column, string \$direction = 'asc')" . PHP_EOL;
        $content .= " *" . PHP_EOL;

        usort(array: $this->columns, callback: static function ($a, $b) {
            if ($a->sortOrder === $b->sortOrder) {
                return strlen(string: $a->columnName) - strlen(string: $b->columnName) === 0 ? strcmp(string1: $a->columnName, string2: $b->columnName) : strlen(string: $a->columnName) - strlen(string: $b->columnName);
            }
            return $a->sortOrder - $b->sortOrder;
        });
        foreach ($this->columns as $column) {
            $content .= " * @property " . $column->dataType . " $" . $column->columnName . PHP_EOL;
        }

        $content .= " *" . PHP_EOL;
        $content .= " * AUTO GENERATED FILE DO NOT MODIFY" . PHP_EOL;
        $content .= " */" . PHP_EOL;
        return $content;
    }
}
