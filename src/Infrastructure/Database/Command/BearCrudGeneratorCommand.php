<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Command;

use GuardsmanPanda\Larabear\Infrastructure\App\Service\RegexService;
use GuardsmanPanda\Larabear\Infrastructure\Console\Service\ConsoleService;
use GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal\BuildEloquentModelInternal;
use GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal\EloquentModelInternal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Ramsey\Collection\Set;

class BearCrudGeneratorCommand extends Command {
    protected $signature = 'bear:crud {table_name} {connection_name?} {--service}';
    protected $description = 'Generate Crud Classes';

    public function handle(): void {
        $table_input = $this->argument(key: 'table_name');
        $connection_input = $this->argument(key: 'connection_name');
        ConsoleService::printH1(headline: "Generate Crud Classes For $table_input" . ($connection_input ? " [$connection_input]" : ''));
        ConsoleService::printH2(headline: 'Looking for table..');
        $connections = Config::get(key: 'bear-dev.eloquent-model-generator');

        $connectionUse = null;
        $connectionUseConfig = null;
        foreach ($connections as $connection_name => $connection_config) {
            if ($connection_input !== null && $connection_input !== $connection_name) {
                continue;
            }
            foreach ($connection_config as $table_name => $table_config) {
                if ($table_input === $table_name) {
                    $connectionUse = $connection_name;
                    $connectionUseConfig = $connection_config;
                    break;
                }
            }
        }
        ConsoleService::printTestResult(testName: "Table [$table_input] found in config for connection [$connectionUse].", errorMessage: $connectionUse === null ? "Table: [$table_input] not found in config." : null);
        if ($connectionUse === null) {
            return;
        }

        $models = BuildEloquentModelInternal::buildAll(connectionName: $connectionUse, tableConfig: $connectionUseConfig);
        $model = $models[$table_input];
        $location = RegexService::extractFirst(regex: '~(.*?)/[^/]+$~', subject:$model->getModelLocation()) . '/Crud';
        $directory = App::basePath(path: trim(string: $location, characters: '/'));


        if (!is_dir($directory)) {
            if (!mkdir($directory) && !is_dir($directory)) {
                ConsoleService::printTestResult(testName: '', errorMessage: "Directory: [$directory] was not created.");
                return;
            }
            ConsoleService::printTestResult(testName: "Directory $directory Created");
        } else {
            ConsoleService::printTestResult(testName: "Directory $directory Exists");
        }

        ConsoleService::printH2(headline: 'Generating Domain Crud..');
        $this->generateCreator(model: $model, directory: $directory);
        $this->generateUpdater(model: $model, directory: $directory);
        $this->generateDeleter(model: $model, directory: $directory);

        ConsoleService::printH2(headline: 'Generating Service Crud..');
        if ($this->option(key: 'service')) {
            $this->generateServiceCrud(model: $model);
        } else {
            ConsoleService::printTestResult(testName: '', warningMessage: '--service option not set.');
        }
    }


    private function generateCreator(EloquentModelInternal $model, string $directory): void {
        $filename = $model->getModelClassName() . 'Creator.php';
        $filepath = $directory . '/' . $filename;
        if (File::exists($filepath)) {
            ConsoleService::printTestResult(testName: "File $filename Exists", warningMessage: "File: [$filename] already exists. [$filepath]");
            return;
        }

        $headers = new Set(setType: 'string');
        if (!$model->hasCompositePrimaryKey() && $model->getColumns()[$model->getPrimaryKeyColumns()[0]]->nativeDataType === 'uuid') {
            $headers->add("use Illuminate\\Support\\Str;");
        }

        $content = $this->classHeader($model, $headers);
        $content .= 'class ' . $model->getModelClassName() . 'Creator {' . PHP_EOL;
        $content .= "    public static function create(" . PHP_EOL;

        foreach ($this->getModifiableColumnArray(model: $model, forCreator: true) as $column) {
            if ($column->isNullable) {
                $content .= "    $column->phpDataType|null \$$column->columnName = null," . PHP_EOL;
            } else {
                $content .= "    $column->phpDataType \$$column->columnName," . PHP_EOL;
            }
        }
        $content = substr($content, 0, -2);
        $content .= PHP_EOL;
        $content .= "    ): {$model->getModelClassName()} {" . PHP_EOL;
        $content .= "        BearDBService::mustBeInTransaction();" . PHP_EOL;
        $content .= "        if (!Req::isWriteRequest()) {" . PHP_EOL;
        $content .= "            throw new RuntimeException(message: 'Database write operations should not be performed in read-only [GET, HEAD, OPTIONS] requests.');" . PHP_EOL;
        $content .= "        }" . PHP_EOL;
        $content .= "        \$model = new {$model->getModelClassName()}();" . PHP_EOL;

        if (!$model->hasCompositePrimaryKey() && $model->getColumns()[$model->getPrimaryKeyColumns()[0]]->nativeDataType === 'uuid') {
            $content .= "        \$model->{$model->getPrimaryKeyColumns()[0]} = Str::uuid()->toString();" . PHP_EOL;
        }

        $content .= PHP_EOL;
        foreach ($this->getModifiableColumnArray($model) as $column) {
            $content .= "        \$model->$column->columnName = \$$column->columnName;" . PHP_EOL;
        }
        $content .= PHP_EOL;

        $content .= "        \$model->save();" . PHP_EOL;
        $content .= "        return \$model;" . PHP_EOL;
        $content .= '    }' . PHP_EOL;
        $content .= '}' . PHP_EOL;
        File::put($filepath, $content);
        ConsoleService::printTestResult(testName: "File [$filename] created.");
    }


    private function generateUpdater(EloquentModelInternal $model, string $directory): void {
        $filename = $model->getModelClassName() . 'Updater.php';
        $filepath = $directory . '/' . $filename;
        if (File::exists($filepath)) {
            ConsoleService::printTestResult(testName: "File $filename Exists", warningMessage: "File: [$filename] already exists. [$filepath]");
            return;
        }
        $headers = new Set(setType: 'string');
        $content = $this->classHeader($model, headers: $headers);
        $content .= 'class ' . $model->getModelClassName() . 'Updater {' . PHP_EOL;
        $content .= "    public function __construct(private readonly {$model->getModelClassName()} \$model) {" . PHP_EOL;
        $content .= "        BearDBService::mustBeInTransaction();" . PHP_EOL;
        $content .= "        if (!Req::isWriteRequest()) {" . PHP_EOL;
        $content .= "            throw new RuntimeException(message: 'Database write operations should not be performed in read-only [GET, HEAD, OPTIONS] requests.');" . PHP_EOL;
        $content .= "        }" . PHP_EOL;
        $content .= '    }' . PHP_EOL . PHP_EOL;

        foreach ($this->getModifiableColumnArray($model) as $column) {
            $functionName = "set" . Str::studly($column->columnName);
            if ($column->isNullable) {
                $content .= "    public function $functionName($column->phpDataType|null \$$column->columnName): void {" . PHP_EOL;
            } else {
                $content .= "    public function $functionName($column->phpDataType \$$column->columnName): void {" . PHP_EOL;
            }
            $content .= "        \$this->model->$column->columnName = \$$column->columnName;" . PHP_EOL;
            $content .= '    }' . PHP_EOL . PHP_EOL;
        }

        $content .= "    public function save(): {$model->getModelClassName()} {" . PHP_EOL;
        $content .= "        \$this->model->save();" . PHP_EOL;
        $content .= "        return \$this->model;" . PHP_EOL;
        $content .= '    }' . PHP_EOL;
        $content .= '}' . PHP_EOL;
        File::put($filepath, $content);
        ConsoleService::printTestResult(testName: "File [$filename] created.");
    }


    private function generateDeleter(EloquentModelInternal $model, string $directory): void {
        $filename = $model->getModelClassName() . 'Deleter.php';
        $filepath = $directory . '/' . $filename;
        if (File::exists($filepath)) {
            ConsoleService::printTestResult(testName: '', warningMessage: "File: [$filename] already exists.  [$filepath]");
            return;
        }
        $headers = new Set(setType: 'string');
        $content = $this->classHeader(model: $model, headers: $headers);
        $content .= 'class ' . $model->getModelClassName() . 'Deleter {' . PHP_EOL;
        $content .= "    public static function delete({$model->getModelClassName()} \$model): void {" . PHP_EOL;
        $content .= "        BearDBService::mustBeInTransaction();" . PHP_EOL;
        $content .= "        if (!Req::isWriteRequest()) {" . PHP_EOL;
        $content .= "            throw new RuntimeException(message: 'Database write operations should not be performed in read-only [GET, HEAD, OPTIONS] requests.');" . PHP_EOL;
        $content .= "        }" . PHP_EOL;
        $content .= "        \$model->delete();" . PHP_EOL;
        $content .= '    }' . PHP_EOL;
        $content .= '}' . PHP_EOL;
        File::put($filepath, $content);
        ConsoleService::printTestResult(testName: "File [$filename] created.");
    }


    private function generateServiceCrud(EloquentModelInternal $model): void {
        $filename = $model->getModelClassName() . 'Crud.php';
        $location = RegexService::extractFirst(regex: '~(.*?)/.+$~', subject:$model->getModelLocation()) . '/Crud';
        $location = preg_replace(pattern: '~'.Config::get(key:'bear-dev.data_access_layer_folder') . '~', replacement: Config::get(key:'bear-dev.application_layer_folder'), subject: $location, limit: 1);
        $filepath = App::basePath(path: trim(string: $location, characters: '/')) . '/' . $filename;
        if (File::exists($filepath)) {
            ConsoleService::printTestResult(testName: '', warningMessage: "File: [$filename] already exists.  [$filepath]");
            return;
        }
        $headers = new Set(setType: 'string');
        $headers->add("use GuardsmanPanda\\Larabear\\Infrastructure\\Http\\Service\\Req;");
        $headers->add("use {$model->getNameSpace()}\\{$model->getModelClassName()};");

        $content = '<?php' . PHP_EOL . PHP_EOL;

        // HEADERS
        $hh = $headers->toArray();
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

        // CREATE FROM REQUEST

        File::put($filepath, $content);
        ConsoleService::printTestResult(testName: "File [$filename] created.");
    }


    private function classHeader(EloquentModelInternal $model, Set $headers): string {
        $headers->add("use GuardsmanPanda\\Larabear\\Infrastructure\\Database\\Service\\BearDBService;");
        $headers->add("use GuardsmanPanda\\Larabear\\Infrastructure\\Http\\Service\\Req;");
        $headers->add("use {$model->getNameSpace()}\\{$model->getModelClassName()};");
        $headers->add("use RuntimeException;");
        $namespace = RegexService::extractFirst('~(.*)/[^/]+$~', $model->getNameSpace()) . '/Crud';
        $content = '<?php' . PHP_EOL . PHP_EOL;
        $content .= 'namespace ' . $namespace . ';' . PHP_EOL . PHP_EOL;
        $hh = $headers->toArray();
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
        return $content . PHP_EOL;
    }


    private function getModifiableColumnArray(EloquentModelInternal $model, bool $forCreator = false): array {
        if ($forCreator) {
            $skipColumns = ['created_at', 'updated_at', 'deleted_at'];
        } else {
            $skipColumns = $model->getPrimaryKeyColumns() + ['created_at', 'updated_at', 'deleted_at'];
        }
        $columns = [];
        foreach ($model->getColumns() as $column) {
            if ($column->isNullable === false && !in_array(needle: $column->columnName, haystack: $skipColumns, strict: true)) {
                $columns[] = $column;
            }

        }
        foreach ($model->getColumns() as $column) {
            if ($column->isNullable === true && !in_array(needle: $column->columnName, haystack: $skipColumns, strict: true)) {
                $columns[] = $column;
            }
        }
        return $columns;
    }
}
