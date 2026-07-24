<?php

namespace Libinkk\ApiStarter\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeFilterCommand extends GeneratorCommand
{
    protected $name = 'api-starter:make-filter';

    protected $description = 'Create a new API Starter filter class';

    protected $type = 'Filter';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/filter.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Filters';
    }

    protected function resolveStubPath(string $stub): string
    {
        $custom = $this->laravel->basePath(trim($stub, '/'));

        return file_exists($custom) ? $custom : __DIR__.'/../../'.$stub;
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the filter already exists'],
        ];
    }
}
