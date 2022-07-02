<?php

namespace GuardsmanPanda\LarabearDev\Infrastructure\Database\Command;

use GuardsmanPanda\Larabear\Infrastructure\Console\Service\ConsoleService;
use GuardsmanPanda\LarabearDev\Infrastructure\Database\Internal\BuildEloquentModelInternal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class BearCrudGeneratorCommand extends Command {
    protected $signature = 'bear:crud {table_name} {connection_name?}';
    protected $description = 'Generate Crud Classes';

    public function handle(): void {
        $table_input = $this->argument(key: 'table_name');
        $connection_input = $this->argument(key: 'connection_name');
        ConsoleService::printH1(headline: "Generate Crud Classes For $table_input" . ($connection_input ? " [$connection_input]" : ''));
        ConsoleService::printH2(headline: 'Looking for table..');
        $connections = Config::get(key: 'bear-dev.eloquent-model-generator');
        $connect_use = null;
        $config = null;
        foreach ($connections as $connection_name => $connection_config) {
            if ($connection_input !== null && $connection_input !== $connection_name) {
                continue;
            }
            foreach ($connection_config as $table_name => $table_config) {
                if ($table_input === $table_name) {
                    $connect_use = $connection_name;
                    $config = $table_config;
                    break;
                }
            }
        }
        $models = BuildEloquentModelInternal::buildAll(connectionName: $connect_use, tableConfig: $config);

        ConsoleService::printTestResult(testName: "Table [$table_input] found in config for connection [$connect_use].", errorMessage: $connect_use === null ? "Table: [$table_input] not found in config." : null);
        if ($connect_use === null) {
            return;
        }
    }


    private function classHeader(string $modelClassName, string $location, string $modelClassNamespace): string {
        $content = '<?php' . PHP_EOL . PHP_EOL;
        $content .= 'namespace ' . ucfirst(str_replace('/', '\\', $location)) . ';' . PHP_EOL . PHP_EOL;
        $content .= "use $modelClassNamespace\\$modelClassName; " . PHP_EOL . PHP_EOL;
        return $content;
    }
}
