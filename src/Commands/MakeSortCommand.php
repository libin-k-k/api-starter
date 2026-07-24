<?php

namespace Libinkk\ApiStarter\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeSortCommand extends GeneratorCommand
{
    protected $name = 'api-starter:make-sort';

    protected $description = 'Create a new API Starter sort class';

    protected $type = 'Sort';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/sort.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Sorts';
    }

    protected function resolveStubPath(string $stub): string
    {
        $custom = $this->laravel->basePath(trim($stub, '/'));

        return file_exists($custom) ? $custom : __DIR__.'/../../'.$stub;
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the sort already exists'],
        ];
    }
}
