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
        $this->useManualSourcePath($this->createTempDirectory());
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

    protected function readDoc(string $relativePath): string {
        return $this->files->get($this->docsPath . '/' . ltrim($relativePath, '/'));
    }

    protected function useManualSourcePath(string $configuredPath): void {
        if (isset($this->docsPath) && $this->files->isDirectory($this->docsPath)) {
            $this->files->deleteDirectory($this->docsPath);
        }

        $this->docsPath = $this->resolveConfiguredPath($configuredPath);
        $this->files->ensureDirectoryExists($this->docsPath);

        config()->set('manual.source_path', $configuredPath);
    }

    private function createTempDirectory(): string {
        $path = tempnam(sys_get_temp_dir(), 'manual-docs-');

        if ($path === false) {
            throw new \RuntimeException('Could not allocate a temporary directory for tests.');
        }

        $this->files->delete($path);

        return $path;
    }

    private function resolveConfiguredPath(string $configuredPath): string {
        if ($this->isAbsolutePath($configuredPath)) {
            return rtrim($configuredPath, DIRECTORY_SEPARATOR);
        }

        return rtrim(base_path($configuredPath), DIRECTORY_SEPARATOR);
    }

    private function isAbsolutePath(string $path): bool {
        return str_starts_with($path, DIRECTORY_SEPARATOR) || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
