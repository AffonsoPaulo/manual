<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests;

use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase as Orchestra;
use ServeraCloud\Manual\ManualServiceProvider;

abstract class TestCase extends Orchestra {
    protected string $docsPath;

    protected Filesystem $files;

    protected function getPackageProviders($app): array {
        return [ManualServiceProvider::class];
    }

    protected function defineEnvironment($app): void {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.key', 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=');
        $app['config']->set('app.debug', true);
        $app['config']->set('manual.cache_store', 'array');
        $app['config']->set('manual.site_title', 'Manual de Testes');
    }

    protected function setUp(): void {
        parent::setUp();

        $this->files = new Filesystem();
        $this->docsPath = sys_get_temp_dir() . '/manual-docs-' . uniqid();
        $this->files->ensureDirectoryExists($this->docsPath);

        config()->set('manual.source_path', $this->docsPath);
    }

    protected function tearDown(): void {
        $this->files->deleteDirectory($this->docsPath);

        parent::tearDown();
    }

    protected function writeDoc(string $relativePath, string $contents): void {
        $path = $this->docsPath . '/' . ltrim($relativePath, '/');
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents);
    }
}
