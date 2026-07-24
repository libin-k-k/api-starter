<?php

namespace Libinkk\ApiStarter\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Libinkk\ApiStarter\Support\ErrorCode;
use Libinkk\ApiStarter\Versioning\ApiVersion;

class DoctorCommand extends Command
{
    protected $signature = 'api-starter:doctor';

    protected $description = 'Diagnose API Starter installation and configuration';

    public function handle(): int
    {
        $this->info('API Starter Doctor');
        $this->newLine();

        $checks = [
            'PHP >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'Config file published' => File::exists(config_path('api-starter.php')),
            'Response feature enabled' => (bool) config('api-starter.features.response', false),
            'Exceptions feature enabled' => (bool) config('api-starter.features.exceptions', false),
            'Filtering feature enabled' => (bool) config('api-starter.features.filtering', false),
            'Sorting feature enabled' => (bool) config('api-starter.features.sorting', false),
            'Search feature enabled' => (bool) config('api-starter.features.search', false),
            'Pagination feature enabled' => (bool) config('api-starter.features.pagination', false),
            'Includes feature enabled' => (bool) config('api-starter.features.includes', false),
            'Fields feature enabled' => (bool) config('api-starter.features.fields', false),
            'Request ID feature enabled' => (bool) config('api-starter.features.request_id', false),
            'Error codes feature enabled' => (bool) config('api-starter.features.error_codes', false),
            'Localization feature enabled' => (bool) config('api-starter.features.localization', false),
            'Versioning feature enabled' => (bool) config('api-starter.features.versioning', false),
            'Performance feature enabled' => (bool) config('api-starter.features.performance', false),
        ];

        $failed = 0;

        foreach ($checks as $label => $ok) {
            if ($ok) {
                $this->line("<fg=green>PASS</>  {$label}");
            } else {
                $this->line("<fg=yellow>WARN</>  {$label}");
                $failed++;
            }
        }

        $this->newLine();
        $this->line('Package version: '.\Libinkk\ApiStarter\Version::VERSION);
        $this->line('Default API version: '.ApiVersion::current());
        $this->line('Supported versions: '.implode(', ', ApiVersion::supported()));
        $this->line('Locales: '.implode(', ', (array) config('api-starter.localization.supported', [])));
        $this->line('Built-in error codes: '.count(ErrorCode::all()));

        $this->newLine();

        if ($failed > 0) {
            $this->warn("Doctor finished with {$failed} warning(s).");
        } else {
            $this->info('Doctor finished. Everything looks good.');
        }

        return self::SUCCESS;
    }
}
