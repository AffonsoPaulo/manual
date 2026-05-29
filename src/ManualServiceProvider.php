<?php

declare(strict_types=1);

namespace ServeraCloud\Manual;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Router;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use ServeraCloud\Manual\Console\BuildManualCommand;
use ServeraCloud\Manual\Console\ClearManualCommand;
use ServeraCloud\Manual\Http\Controllers\DocumentController;
use ServeraCloud\Manual\Http\Controllers\ImageController;
use ServeraCloud\Manual\Http\Controllers\SearchIndexController;
use ServeraCloud\Manual\Services\ContentMetadataExtractor;
use ServeraCloud\Manual\Services\DocumentScanner;
use ServeraCloud\Manual\Services\FrontMatterParser;
use ServeraCloud\Manual\Services\ManualCache;
use ServeraCloud\Manual\Services\ManualPathResolver;
use ServeraCloud\Manual\Services\ManualRepository;
use ServeraCloud\Manual\Services\MarkdownHelperResolver;
use ServeraCloud\Manual\Services\MarkdownRenderer;
use ServeraCloud\Manual\Services\SearchIndexer;
use ServeraCloud\Manual\Console\InitDocCommand;
use ServeraCloud\Manual\Console\MakeDocCommand;

final class ManualServiceProvider extends ServiceProvider {
    public function register(): void {
        $this->mergeConfigFrom($this->packagePath('config/manual.php'), 'manual');

        $this->app->singleton(FrontMatterParser::class, fn(): FrontMatterParser => new FrontMatterParser());
        $this->app->singleton(ContentMetadataExtractor::class, fn(): ContentMetadataExtractor => new ContentMetadataExtractor());
        $this->app->singleton(ManualPathResolver::class, function (): ManualPathResolver {
            return new ManualPathResolver(
                config: $this->app->make(ConfigRepository::class),
            );
        });

        $this->app->singleton(DocumentScanner::class, function (): DocumentScanner {
            return new DocumentScanner(
                files: $this->app->make(Filesystem::class),
                config: $this->app->make(ConfigRepository::class),
                pathResolver: $this->app->make(ManualPathResolver::class),
                frontMatterParser: $this->app->make(FrontMatterParser::class),
                contentMetadataExtractor: $this->app->make(ContentMetadataExtractor::class),
                markdownHelperResolver: $this->app->make(MarkdownHelperResolver::class),
                cache: $this->app->make(ManualCache::class),
            );
        });

        $this->app->singleton(MarkdownRenderer::class, fn(): MarkdownRenderer => new MarkdownRenderer());

        $this->app->singleton(MarkdownHelperResolver::class, function (): MarkdownHelperResolver {
            return new MarkdownHelperResolver(
                router: $this->app->make(Router::class),
                urlGenerator: $this->app->make(UrlGenerator::class),
            );
        });

        $this->app->singleton(ManualCache::class, function (): ManualCache {
            return new ManualCache(
                cacheFactory: $this->app->make(CacheFactory::class),
                config: $this->app->make(ConfigRepository::class),
            );
        });

        $this->app->singleton(SearchIndexer::class, fn(): SearchIndexer => new SearchIndexer());

        $this->app->singleton(ManualRepository::class, function (): ManualRepository {
            return new ManualRepository(
                scanner: $this->app->make(DocumentScanner::class),
                renderer: $this->app->make(MarkdownRenderer::class),
                cache: $this->app->make(ManualCache::class),
                searchIndexer: $this->app->make(SearchIndexer::class),
                markdownHelperResolver: $this->app->make(MarkdownHelperResolver::class),
                config: $this->app->make(ConfigRepository::class),
            );
        });
    }

    public function boot(): void {
        $this->loadViewsFrom($this->packagePath('resources/views'), 'manual');

        $this->publishes([
            $this->packagePath('config/manual.php') => config_path('manual.php'),
        ], 'manual-config');

        $this->publishes([
            $this->packagePath('resources/views') => resource_path('views/vendor/manual'),
        ], 'manual-views');

        $this->publishes([
            $this->packagePath('resources/dist') => public_path('vendor/manual'),
        ], 'manual-assets');

        if ($this->app->runningInConsole()) {
            $this->commands([
                BuildManualCommand::class,
                ClearManualCommand::class,
                InitDocCommand::class,
                MakeDocCommand::class,
            ]);
        }

        $this->app->booted(function (): void {
            $this->registerRoutes();
        });
    }

    private function registerRoutes(): void {
        $prefix = trim((string) config('manual.route_prefix', 'manual'), '/');
        $searchEnabled = (bool) config('manual.search.enabled', true);
        $searchEndpoint = trim((string) config('manual.search.endpoint', '_manual/search.json'), '/');
        $imagesEnabled = (bool) config('manual.images.enabled', true);
        $imagesPath = trim((string) config('manual.images.path', '_images'), '/\\');
        $imagesExtensions = (array) config('manual.images.extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico']);

        if ($imagesPath === '' || $this->isAbsolutePath($imagesPath)) {
            $imagesPath = '_images';
        }

        $imageConstraint = preg_quote($imagesPath, '#') . '/.*\.(' . implode('|', array_map(fn(string $ext): string => preg_quote($ext, '#'), $imagesExtensions)) . ')';

        Route::middleware((array) config('manual.middleware', ['web']))
            ->as('manual.')
            ->group(function () use ($prefix, $searchEnabled, $searchEndpoint, $imagesEnabled, $imagesPath, $imageConstraint): void {
                $register = function () use ($searchEnabled, $searchEndpoint, $imagesEnabled, $imagesPath, $imageConstraint): void {
                    if ($searchEnabled && $searchEndpoint !== '') {
                        Route::get($searchEndpoint, SearchIndexController::class)->name('search');
                    }

                    if ($imagesEnabled && $imagesPath !== '') {
                        Route::get('/{path}', ImageController::class)
                            ->where('path', $imageConstraint)
                            ->name('image');
                    }

                    Route::get('/{path?}', DocumentController::class)
                        ->where('path', '.*')
                        ->name('document');
                };

                if ($prefix === '') {
                    $register();

                    return;
                }

                Route::prefix($prefix)->group($register);
            });
    }

    private function isAbsolutePath(string $path): bool {
        return str_starts_with($path, DIRECTORY_SEPARATOR) || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }

    private function packagePath(string $path): string {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . $path;
    }
}
