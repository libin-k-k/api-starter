<?php

namespace Libinkk\ApiStarter\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Foundation\Exceptions\Handler as FoundationExceptionHandler;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Libinkk\ApiStarter\Commands\DoctorCommand;
use Libinkk\ApiStarter\Commands\InstallCommand;
use Libinkk\ApiStarter\Commands\MakeFilterCommand;
use Libinkk\ApiStarter\Commands\MakeSortCommand;
use Libinkk\ApiStarter\Commands\MakeTransformerCommand;
use Libinkk\ApiStarter\Commands\PublishCommand;
use Libinkk\ApiStarter\Contracts\ApiResponderInterface;
use Libinkk\ApiStarter\Exceptions\ExceptionHandler;
use Libinkk\ApiStarter\Http\Middleware\AssignRequestId;
use Libinkk\ApiStarter\Http\Middleware\MeasurePerformance;
use Libinkk\ApiStarter\Http\Middleware\SetApiVersion;
use Libinkk\ApiStarter\Http\Middleware\SetLocale;
use Libinkk\ApiStarter\Services\ApiResponder;
use Throwable;

class ApiStarterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/api-starter.php',
            'api-starter'
        );

        $this->app->singleton(ApiResponderInterface::class, ApiResponder::class);
        $this->app->alias(ApiResponderInterface::class, 'api-starter.responder');

        $this->app->singleton(ExceptionHandler::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'api-starter');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/api-starter.php' => config_path('api-starter.php'),
            ], 'api-starter-config');

            $this->publishes([
                __DIR__.'/../../lang' => $this->app->langPath('vendor/api-starter'),
            ], 'api-starter-lang');

            $this->publishes([
                __DIR__.'/../../stubs' => base_path('stubs/api-starter'),
            ], 'api-starter-stubs');

            $this->commands([
                InstallCommand::class,
                PublishCommand::class,
                DoctorCommand::class,
                MakeFilterCommand::class,
                MakeSortCommand::class,
                MakeTransformerCommand::class,
            ]);
        }

        $this->registerMiddleware();
        $this->registerExceptionHandling();
    }

    protected function registerMiddleware(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('api.request-id', AssignRequestId::class);
        $router->aliasMiddleware('api.locale', SetLocale::class);
        $router->aliasMiddleware('api.version', SetApiVersion::class);
        $router->aliasMiddleware('api.performance', MeasurePerformance::class);
    }

    protected function registerExceptionHandling(): void
    {
        if (! $this->app->bound(ExceptionHandlerContract::class)) {
            return;
        }

        $handler = $this->app->make(ExceptionHandlerContract::class);

        if (! $handler instanceof FoundationExceptionHandler) {
            return;
        }

        $handler->renderable(
            function (Throwable $e, $request) {
                return $this->app->make(ExceptionHandler::class)->handle($e, $request);
            }
        );
    }
}
