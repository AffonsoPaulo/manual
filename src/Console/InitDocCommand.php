<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use ServeraCloud\Manual\Exceptions\DocumentStubNotFoundException;
use ServeraCloud\Manual\Exceptions\ManualException;
use ServeraCloud\Manual\Exceptions\UnwritableDocumentException;
use ServeraCloud\Manual\Services\ManualPathResolver;
use Throwable;

final class InitDocCommand extends Command {
    private const STUBS = [
        'index.md',
        'getting-started/index.md',
        'getting-started/installation.md',
        'getting-started/configuration.md',
        'guides/index.md',
        'guides/front-matter.md',
        'guides/routing.md',
        'guides/navigation.md',
        'guides/linking.md',
        'advanced/index.md',
        'advanced/caching.md',
        'advanced/search.md',
        'advanced/customization.md',
    ];

    protected $signature = 'manual:doc {--force : Overwrite scaffold files that already exist}';

    protected $description = 'Create the default manual documentation scaffold.';

    public function handle(Filesystem $files, ManualPathResolver $paths): int {
        $created = 0;
        $overwritten = 0;
        $skipped = 0;

        foreach (self::STUBS as $relativePath) {
            try {
                $target = $paths->resolveDocumentPath($relativePath);
                $stubPath = $this->stubPath($relativePath);

                if (! $files->exists($stubPath)) {
                    throw new DocumentStubNotFoundException(sprintf(
                        'The manual scaffold stub "%s" could not be found.',
                        $relativePath,
                    ));
                }

                if (is_dir($target->absolutePath)) {
                    throw new UnwritableDocumentException(sprintf(
                        'The target manual path "%s" is a directory.',
                        $target->absolutePath,
                    ));
                }

                if ($files->exists($target->absolutePath) && ! $this->option('force')) {
                    $this->line(sprintf('<comment>skipped</comment> %s', $target->relativePath));
                    $skipped++;

                    continue;
                }

                $wasExistingFile = $files->exists($target->absolutePath);
                $this->writeFile($files, $target->absolutePath, $files->get($stubPath));

                if ($wasExistingFile) {
                    $this->line(sprintf('<info>overwritten</info> %s', $target->relativePath));
                    $overwritten++;

                    continue;
                }

                $this->line(sprintf('<info>created</info> %s', $target->relativePath));
                $created++;
            } catch (ManualException $exception) {
                $this->line(sprintf('<error>failed</error> %s', $relativePath));
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
        }

        $this->info(sprintf(
            'Manual scaffold complete: %d created, %d overwritten, %d skipped. Run "php artisan manual:build" next.',
            $created,
            $overwritten,
            $skipped,
        ));

        return self::SUCCESS;
    }

    private function stubPath(string $relativePath): string {
        return __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    private function writeFile(Filesystem $files, string $path, string $contents): void {
        if (is_dir($path)) {
            throw new UnwritableDocumentException(sprintf(
                'The target manual path "%s" is a directory.',
                $path,
            ));
        }

        try {
            $files->ensureDirectoryExists(dirname($path));
        } catch (Throwable $exception) {
            throw new UnwritableDocumentException(sprintf(
                'The directory "%s" could not be created.',
                dirname($path),
            ), previous: $exception);
        }

        if (! is_dir(dirname($path)) || ! is_writable(dirname($path))) {
            throw new UnwritableDocumentException(sprintf(
                'The directory "%s" is not writable.',
                dirname($path),
            ));
        }

        try {
            $written = $files->put($path, $contents);
        } catch (Throwable $exception) {
            throw new UnwritableDocumentException(sprintf(
                'The manual document "%s" could not be written.',
                $path,
            ), previous: $exception);
        }

        if ($written === false) {
            throw new UnwritableDocumentException(sprintf(
                'The manual document "%s" could not be written.',
                $path,
            ));
        }
    }
}
