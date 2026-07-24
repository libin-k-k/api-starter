<?php

namespace Libinkk\ApiStarter\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

class MakeTransformerCommand extends GeneratorCommand
{
    protected $name = 'api-starter:make-transformer';

    protected $description = 'Create a new API Starter transformer class';

    protected $type = 'Transformer';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/transformer.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Transformers';
    }

    protected function resolveStubPath(string $stub): string
    {
        $custom = $this->laravel->basePath(trim($stub, '/'));

        return file_exists($custom) ? $custom : __DIR__.'/../../'.$stub;
    }

    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the transformer already exists'],
        ];
    }
}
