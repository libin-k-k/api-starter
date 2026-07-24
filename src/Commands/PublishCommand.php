<?php

namespace Libinkk\ApiStarter\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'api-starter:publish
                            {--tag= : Publish a specific tag (api-starter-config|api-starter-lang|api-starter-stubs)}
                            {--force : Overwrite existing files}';

    protected $description = 'Publish API Starter assets (config, lang, stubs)';

    public function handle(): int
    {
        $tag = $this->option('tag');
        $params = ['--provider' => 'Libinkk\\ApiStarter\\Providers\\ApiStarterServiceProvider'];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $tags = $tag
            ? [$tag]
            : ['api-starter-config', 'api-starter-lang', 'api-starter-stubs'];

        foreach ($tags as $publishTag) {
            $this->call('vendor:publish', array_merge($params, ['--tag' => $publishTag]));
        }

        $this->info('API Starter assets published.');

        return self::SUCCESS;
    }
}
