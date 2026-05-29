<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use ServeraCloud\Manual\Data\ResolvedDocumentPath;
use ServeraCloud\Manual\Exceptions\InvalidDocumentPathException;

final class ManualPathResolver {
    public function __construct(
        private readonly ConfigRepository $config,
    ) {
    }

    public function sourcePath(): string {
        $configured = (string) $this->config->get('manual.source_path', 'docs/manual');

        if ($this->isAbsolutePath($configured)) {
            return rtrim($configured, DIRECTORY_SEPARATOR);
        }

        return rtrim(base_path($configured), DIRECTORY_SEPARATOR);
    }

    public function imagesPath(): string {
        $configured = trim((string) $this->config->get('manual.images.path', '_images'), '/\\');

        if ($configured === '' || $this->isAbsolutePath($configured)) {
            $configured = '_images';
        }

        return $this->sourcePath() . DIRECTORY_SEPARATOR . $configured;
    }

    public function resolveDocumentPath(string $path): ResolvedDocumentPath {
        $normalized = $this->normalizeDocumentPath($path);
        $absolutePath = $this->joinPaths($this->sourcePath(), $normalized);

        $this->guardExistingAncestors($absolutePath);

        [$directorySegments, $basename, $isIndex] = $this->sourcePathParts($normalized);

        return new ResolvedDocumentPath(
            absolutePath: $absolutePath,
            relativePath: $normalized,
            directorySegments: $directorySegments,
            basename: $basename,
            isIndex: $isIndex,
        );
    }

    private function normalizeDocumentPath(string $path): string {
        if (str_contains($path, "\0")) {
            throw new InvalidDocumentPathException('Manual document paths cannot contain null bytes.');
        }

        $trimmed = trim($path);

        if ($trimmed === '') {
            throw new InvalidDocumentPathException('Manual document paths cannot be empty.');
        }

        if ($this->isAbsolutePath($trimmed)) {
            throw new InvalidDocumentPathException(sprintf(
                'The manual document path "%s" must be relative to manual.source_path.',
                $path,
            ));
        }

        $normalized = trim(str_replace('\\', '/', $trimmed), '/');

        if ($normalized === '') {
            throw new InvalidDocumentPathException('Manual document paths cannot be empty.');
        }

        $segments = explode('/', $normalized);

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new InvalidDocumentPathException(sprintf(
                    'The manual document path "%s" contains an invalid path segment.',
                    $path,
                ));
            }
        }

        $lastSegmentIndex = count($segments) - 1;

        if (! preg_match('/\.md$/i', $segments[$lastSegmentIndex])) {
            $segments[$lastSegmentIndex] .= '.md';
        }

        return implode('/', $segments);
    }

    private function guardExistingAncestors(string $absolutePath): void {
        $sourcePath = $this->sourcePath();
        $resolvedSourcePath = realpath($sourcePath);
        $resolvedTargetAncestor = $this->resolvedExistingPath(dirname($absolutePath));

        if ($resolvedTargetAncestor === null) {
            return;
        }

        if ($resolvedSourcePath !== false) {
            if (! $this->pathIsWithin($resolvedTargetAncestor, $resolvedSourcePath)) {
                throw new InvalidDocumentPathException(sprintf(
                    'The manual document path "%s" resolves outside of manual.source_path.',
                    $absolutePath,
                ));
            }

            $resolvedTargetPath = $this->resolvedExistingPath($absolutePath);

            if ($resolvedTargetPath !== null && ! $this->pathIsWithin($resolvedTargetPath, $resolvedSourcePath)) {
                throw new InvalidDocumentPathException(sprintf(
                    'The manual document path "%s" resolves outside of manual.source_path.',
                    $absolutePath,
                ));
            }

            return;
        }

        $resolvedSourceAncestor = $this->resolvedExistingPath($sourcePath);

        if ($resolvedSourceAncestor !== null && ! $this->pathIsWithin($resolvedTargetAncestor, $resolvedSourceAncestor)) {
            throw new InvalidDocumentPathException(sprintf(
                'The manual document path "%s" resolves outside of manual.source_path.',
                $absolutePath,
            ));
        }
    }

    private function resolvedExistingPath(string $path): ?string {
        $existing = $this->nearestExistingPath($path);

        if ($existing === null) {
            return null;
        }

        $resolved = realpath($existing);

        return $resolved === false ? null : rtrim($resolved, DIRECTORY_SEPARATOR);
    }

    private function nearestExistingPath(string $path): ?string {
        $current = $path;

        while ($current !== '' && ! file_exists($current)) {
            $parent = dirname($current);

            if ($parent === $current) {
                break;
            }

            $current = $parent;
        }

        return file_exists($current) ? $current : null;
    }

    private function pathIsWithin(string $path, string $basePath): bool {
        $normalizedPath = $this->normalizeComparisonPath($path);
        $normalizedBasePath = $this->normalizeComparisonPath($basePath);

        return $normalizedPath === $normalizedBasePath
            || str_starts_with($normalizedPath, $normalizedBasePath . DIRECTORY_SEPARATOR);
    }

    private function normalizeComparisonPath(string $path): string {
        $normalized = rtrim($path, DIRECTORY_SEPARATOR);

        if (DIRECTORY_SEPARATOR === '\\') {
            return strtolower($normalized);
        }

        return $normalized;
    }

    private function joinPaths(string $basePath, string $relativePath): string {
        return rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * @return array{0: list<string>, 1: string, 2: bool}
     */
    private function sourcePathParts(string $relativePath): array {
        $parts = explode('/', $relativePath);
        $filename = array_pop($parts);
        $basename = pathinfo((string) $filename, PATHINFO_FILENAME);
        $isIndex = strtolower($basename) === 'index';

        return [$parts, $basename, $isIndex];
    }

    private function isAbsolutePath(string $path): bool {
        return str_starts_with($path, DIRECTORY_SEPARATOR) || (bool) preg_match('/^[A-Za-z]:[\\\\\\/]/', $path);
    }
}
