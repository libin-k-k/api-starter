<?php

namespace Libinkk\ApiStarter\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'api-starter:install
                            {--force : Overwrite existing published files}';

    protected $description = 'Install API Starter config, language files, and show next steps';

    public function handle(): int
    {
        $params = ['--provider' => 'Libinkk\\ApiStarter\\Providers\\ApiStarterServiceProvider'];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', array_merge($params, ['--tag' => 'api-starter-config']));
        $this->call('vendor:publish', array_merge($params, ['--tag' => 'api-starter-lang']));

        $this->newLine();
        $this->info('API Starter installed.');
        $this->line('Next steps:');
        $this->line('  1. Review config/api-starter.php');
        $this->line('  2. Register middleware: api.performance, api.request-id, api.locale, api.version');
        $this->line('  3. Use Api::success() / ApiQuery::for() in your controllers');
        $this->line('  4. Run php artisan api-starter:doctor');

        return self::SUCCESS;
    }
}
