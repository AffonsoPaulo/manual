<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use ServeraCloud\Manual\Data\ResolvedDocumentPath;
use ServeraCloud\Manual\Exceptions\DocumentAlreadyExistsException;
use ServeraCloud\Manual\Exceptions\DocumentStubNotFoundException;
use ServeraCloud\Manual\Exceptions\ManualException;
use ServeraCloud\Manual\Exceptions\UnwritableDocumentException;
use ServeraCloud\Manual\Services\ManualPathResolver;
use Symfony\Component\Yaml\Yaml;
use Throwable;

final class MakeDocCommand extends Command {
    protected $aliases = ['make:manual'];

    protected $signature = 'manual:make
        {name : Relative document path inside manual.source_path}
        {--title= : The page title written to front matter and the H1 heading}
        {--slug= : The slug front matter value}
        {--url= : The url front matter value}
        {--order= : The order front matter value}
        {--description= : The description front matter value}
        {--key= : The key front matter value}
        {--hidden : Mark the generated document as hidden}
        {--force : Overwrite the document if it already exists}';

    protected $description = 'Create a new Markdown document inside the manual source directory.';

    public function handle(Filesystem $files, ManualPathResolver $paths): int {
        $target = null;

        try {
            $target = $paths->resolveDocumentPath((string) $this->argument('name'));
            $existingFile = $files->exists($target->absolutePath);

            if (is_dir($target->absolutePath)) {
                throw new UnwritableDocumentException(sprintf(
                    'The target manual path "%s" is a directory.',
                    $target->absolutePath,
                ));
            }

            if ($existingFile && ! $this->option('force')) {
                throw new DocumentAlreadyExistsException(sprintf(
                    'The manual document "%s" already exists. Use --force to overwrite it.',
                    $target->relativePath,
                ));
            }

            $contents = $this->buildDocumentContents($files, $target);
            $this->writeFile($files, $target->absolutePath, $contents);
        } catch (ManualException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Manual document %s: %s',
            $existingFile ? 'overwritten' : 'created',
            $target->absolutePath,
        ));

        return self::SUCCESS;
    }

    private function buildDocumentContents(Filesystem $files, ResolvedDocumentPath $target): string {
        $stubPath = __DIR__ . DIRECTORY_SEPARATOR . 'stubs' . DIRECTORY_SEPARATOR . 'make-doc.md';

        if (! $files->exists($stubPath)) {
            throw new DocumentStubNotFoundException('The manual document stub "make-doc.md" could not be found.');
        }

        $title = $this->resolvedTitle($target);
        $frontMatter = [
            'title' => $title,
        ];

        $commentedExamples = [];

        if (($slug = $this->optionalStringOption('slug')) !== null) {
            $frontMatter['slug'] = $slug;
        } else {
            $commentedExamples[] = '# slug: my-slug';
        }

        if (($url = $this->optionalStringOption('url')) !== null) {
            $frontMatter['url'] = $url;
        } else {
            $commentedExamples[] = '# url: custom/path';
        }

        if (($order = $this->resolvedOrder()) !== null) {
            $frontMatter['order'] = $order;
        } else {
            $commentedExamples[] = '# order: 1';
        }

        if (($description = $this->optionalStringOption('description')) !== null) {
            $frontMatter['description'] = $description;
        } else {
            $commentedExamples[] = '# description: Short description shown in search results.';
        }

        if (($docKey = $this->optionalStringOption('key')) !== null) {
            $frontMatter['key'] = $docKey;
        } else {
            $commentedExamples[] = '# key: my.doc.key';
        }

        if ((bool) $this->option('hidden')) {
            $frontMatter['hidden'] = true;
        } else {
            $commentedExamples[] = '# hidden: false';
        }

        $frontMatterYaml = trim(Yaml::dump($frontMatter, 10, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK));
        $frontMatterBlock = implode("\n", array_filter([$frontMatterYaml, ...$commentedExamples]));

        return str_replace(
            ['{{ front_matter }}', '{{ title }}'],
            [$frontMatterBlock, $this->headingTitle($title)],
            $files->get($stubPath),
        );
    }

    private function resolvedTitle(ResolvedDocumentPath $target): string {
        $title = $this->optionalStringOption('title');

        if ($title !== null) {
            return $title;
        }

        return $target->suggestedTitle();
    }

    private function headingTitle(string $title): string {
        return trim((string) preg_replace('/\s+/u', ' ', $title)) ?: 'Untitled';
    }

    private function optionalStringOption(string $name): ?string {
        $value = $this->option($name);

        if (! is_string($value)) {
            return null;
        }

        if (str_contains($value, "\0")) {
            throw new UnwritableDocumentException(sprintf(
                'The "--%s" option cannot contain null bytes.',
                $name,
            ));
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function resolvedOrder(): ?int {
        $order = $this->option('order');

        if ($order === null || $order === '') {
            return null;
        }

        if (! is_string($order) || ! preg_match('/^-?\d+$/', $order)) {
            throw new UnwritableDocumentException('The "--order" option must be an integer.');
        }

        return (int) $order;
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
