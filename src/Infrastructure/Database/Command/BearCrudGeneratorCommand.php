<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Command;

use GuardsmanPanda\Larabear\Infrastructure\App\Service\RegexService;
use GuardsmanPanda\Larabear\Infrastructure\Console\Service\ConsoleService;
use GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal\BuildEloquentModelInternal;
use GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal\EloquentModelInternal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class BearCrudGeneratorCommand extends Command {
    protected $signature = 'bear:crud {table_name} {connection_name?}';
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
        $directory = RegexService::extractFirst(regex: '~(.*?)(/Models?)~', subject: $model->getModelDirectory()) . '/Crud';

        if (!is_dir($directory)) {
            if (!mkdir($directory) && !is_dir($directory)) {
                ConsoleService::printTestResult(testName: '', errorMessage: "Directory: [$directory] was not created.");
                return;
            }
            ConsoleService::printTestResult(testName: "Directory $directory Created");
        } else {
            ConsoleService::printTestResult(testName: "Directory $directory Exists");
        }

        ConsoleService::printH2(headline: 'Generating Crud..');
        $this->generateCreator(model: $model, allModels: $models, directory: $directory);
        $this->generateUpdater(model: $model, allModels: $models, directory: $directory);
        $this->generateDeleter(model: $model, directory: $directory);
    }


    private function generateCreator(EloquentModelInternal $model, array $allModels, string $directory): void {
        $filename = $model->getModelClassName() . 'Creator.php';
        $filepath = $directory . '/' . $filename;
        if (File::exists($filepath)) {
            ConsoleService::printTestResult(testName: "File $filename Exists", warningMessage: "File: [$filename] already exists. [$filepath]");
            return;
        }
        $content = $this->classHeader($model);
        $content .= 'class ' . $model->getModelClassName() . 'Creator {' . PHP_EOL;
        $content .= "    public static function create(" . PHP_EOL;
        $content .= "    ): {$model->getModelClassName()} {" . PHP_EOL;
        $content .= "        BearDBService::mustBeInTransaction();" . PHP_EOL;
        $content .= "        \$model = new {$model->getModelClassName()}();" . PHP_EOL;

        $key_col = $model->getPrimaryKeyColumnName();
        if ($model->getColumns()[$key_col]->nativeDataType === 'uuid') {
            $content .= "        \$model->{$key_col} = Str::uuid()->toString();" . PHP_EOL;
        }

        $content .= "        \$model->save();" . PHP_EOL;
        $content .= "        return \$model;" . PHP_EOL;
        $content .= '    }' . PHP_EOL;
        $content .= '}' . PHP_EOL;
        File::put($filepath, $content);
        ConsoleService::printTestResult(testName: "File [$filename] created.");
    }


    private function generateUpdater(EloquentModelInternal $model, array $allModels, string $directory): void {
        $filename = $model->getModelClassName() . 'Updater.php';
        $filepath = $directory . '/' . $filename;
        if (File::exists($filepath)) {
            ConsoleService::printTestResult(testName: "File $filename Exists", warningMessage: "File: [$filename] already exists. [$filepath]");
            return;
        }
        $content = $this->classHeader($model);
        $content .= 'class ' . $model->getModelClassName() . 'Updater {' . PHP_EOL;
        $content .= "    public function __construct(private readonly {$model->getModelClassName()} \$model) {" . PHP_EOL;
        $content .= "        BearDBService::mustBeInTransaction();" . PHP_EOL;
        $content .= '    }' . PHP_EOL . PHP_EOL;
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
            ConsoleService::printTestResult(testName: '', warningMessage: "File [$filename] already exists.  [$filepath]");
            return;
        }
        $content = $this->classHeader($model);
        $content .= 'class ' . $model->getModelClassName() . 'Deleter {' . PHP_EOL;
        $content .= "    public static function delete({$model->getModelClassName()} \$model): void {" . PHP_EOL;
        $content .= "        BearDBService::mustBeInTransaction();" . PHP_EOL;
        $content .= "        \$model->delete();" . PHP_EOL;
        $content .= '    }' . PHP_EOL;
        $content .= '}' . PHP_EOL;
        File::put($filepath, $content);
        ConsoleService::printTestResult(testName: "File [$filename] created.");
    }


    private function classHeader(EloquentModelInternal $model): string {
        $namespace = RegexService::extractFirst('/(.*)Models?/', $model->getNameSpace()) . 'Crud';
        $content = '<?php' . PHP_EOL . PHP_EOL;
        $content .= 'namespace ' . $namespace . ';' . PHP_EOL . PHP_EOL;
        $content .= "use {$model->getNameSpace()}\\{$model->getModelClassName()}; " . PHP_EOL;
        $content .= "use GuardsmanPanda\Larabear\Infrastructure\Database\Service\BearDBService;" . PHP_EOL . PHP_EOL;
        return $content;
    }
}
