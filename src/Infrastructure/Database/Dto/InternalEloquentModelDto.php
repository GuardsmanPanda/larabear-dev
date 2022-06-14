<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Dto;

use Ramsey\Collection\Set;

class InternalEloquentModelDto {
    private string $primaryKeyColumnName;
    private string $primaryKeyType;
    private array $headers;

    public function __construct(
        private readonly string $connectionDriver,
        private readonly string $tableName,
        private readonly string $modelClassName,
        private readonly string $modelLocation
    ) {}

    public function getTableName(): string {
        return $this->tableName;
    }

    public function getModelClassName(): string {
        return $this->modelClassName;
    }

    public function getModelLocation(): string {
        return $this->modelLocation;
    }

    public function getNameSpace(): string {
        return $this->modelLocation;
    }

    public function setPrimaryKeyInformation(string $primaryKeyColumnName, string $primaryKeyType): void {
        $this->primaryKeyColumnName = $primaryKeyColumnName;
        $this->primaryKeyType = $primaryKeyType;
    }

    public function setHeaders(Set $allHeaders): void {
        $this->headers = $allHeaders->toArray();
        sort($this->headers);
        $this->headers = array_unique(array_map(static function ($ele) { return trim($ele); }, $this->headers));
    }

    public function getTopOfClass(): string {
        $content = "<?php" . PHP_EOL . PHP_EOL . 'namespace ' . $this->getNameSpace() . ';' . PHP_EOL . PHP_EOL;

        foreach ($this->headers as $header) {
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
        return $content;
    }

}