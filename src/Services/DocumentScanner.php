<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use ServeraCloud\Manual\Data\DocumentDescriptor;
use ServeraCloud\Manual\Data\ManualManifest;
use ServeraCloud\Manual\Data\NavigationNode;
use ServeraCloud\Manual\Exceptions\DuplicateRouteException;
use ServeraCloud\Manual\Exceptions\DuplicateRouteNameException;
use ServeraCloud\Manual\Exceptions\InvalidFrontMatterException;
use ServeraCloud\Manual\Exceptions\ReservedRouteException;
use ServeraCloud\Manual\Exceptions\UnreadableDocumentException;
use Symfony\Component\Finder\SplFileInfo;

final class DocumentScanner {
    public function __construct(
        private readonly Filesystem $files,
        private readonly ConfigRepository $config,
        private readonly ManualPathResolver $pathResolver,
        private readonly FrontMatterParser $frontMatterParser,
        private readonly ContentMetadataExtractor $contentMetadataExtractor,
        private readonly MarkdownHelperResolver $markdownHelperResolver,
        private readonly ManualCache $cache,
    ) {
    }

    public function scan(): ManualManifest {
        $sourcePath = $this->sourcePath();
        $root = new NavigationNode('', '');

        if (! $this->files->isDirectory($sourcePath)) {
            return new ManualManifest(
                sourcePath: $sourcePath,
                signature: sha1($sourcePath . '|empty'),
                documents: [],
                documentsByRoute: [],
                documentsByRouteName: [],
                documentsByRelativePath: [],
                directoryIndexDocuments: [],
                navigationRoot: $root,
                visibleDocuments: [],
                visiblePositions: [],
            );
        }

        $markdownFiles = array_filter(
            $this->files->allFiles($sourcePath),
            fn($file): bool => strtolower($file->getExtension()) === 'md',
        );

        usort($markdownFiles, fn($left, $right): int => strcmp($left->getRelativePathname(), $right->getRelativePathname()));

        $inventorySignature = $this->inventorySignature($sourcePath, $markdownFiles);

        return $this->cache->remember(
            $this->cache->key('manifest', $this->manifestCacheContext(), $sourcePath, $inventorySignature),
            fn(): ManualManifest => $this->buildManifest($sourcePath, $markdownFiles, $root),
        );
    }

    /**
     * @param  list<SplFileInfo>  $markdownFiles
     */
    private function buildManifest(string $sourcePath, array $markdownFiles, NavigationNode $root): ManualManifest {
        $scannedDocuments = [];
        $scannedDocumentsByRoute = [];
        $scannedDocumentsByRouteName = [];
        $scannedDocumentsByRelativePath = [];
        $scannedDirectoryIndexDocuments = [];

        foreach ($markdownFiles as $file) {
            if (! $file->isReadable()) {
                throw new UnreadableDocumentException(sprintf('The document "%s" is not readable.', $file->getRelativePathname()));
            }

            $document = $this->documentDescriptor($sourcePath, $file);

            $this->guardReservedRoute($document->routePath, $document->relativePath);

            if (isset($scannedDocumentsByRoute[$document->routePath])) {
                throw new DuplicateRouteException(sprintf(
                    'Duplicate manual route "%s" for "%s" and "%s".',
                    $document->routePath === '' ? '/' : $document->routePath,
                    $scannedDocumentsByRoute[$document->routePath]->relativePath,
                    $document->relativePath,
                ));
            }

            if ($document->routeName !== null && isset($scannedDocumentsByRouteName[$document->routeName])) {
                throw new DuplicateRouteNameException(sprintf(
                    'Duplicate manual route_name "%s" for "%s" and "%s".',
                    $document->routeName,
                    $scannedDocumentsByRouteName[$document->routeName]->relativePath,
                    $document->relativePath,
                ));
            }

            $scannedDocuments[] = $document;
            $scannedDocumentsByRoute[$document->routePath] = $document;
            $scannedDocumentsByRelativePath[$document->relativePath] = $document;

            if ($document->routeName !== null) {
                $scannedDocumentsByRouteName[$document->routeName] = $document;
            }

            if ($document->isIndex) {
                $scannedDirectoryIndexDocuments[$document->sourceDirectoryPath()] = $document;
            }
        }

        $lookupManifest = new ManualManifest(
            sourcePath: $sourcePath,
            signature: $this->signatureFor($sourcePath, $scannedDocuments),
            documents: $scannedDocuments,
            documentsByRoute: $scannedDocumentsByRoute,
            documentsByRouteName: $scannedDocumentsByRouteName,
            documentsByRelativePath: $scannedDocumentsByRelativePath,
            directoryIndexDocuments: $scannedDirectoryIndexDocuments,
            navigationRoot: new NavigationNode('', ''),
            visibleDocuments: [],
            visiblePositions: [],
        );

        $documents = [];
        $documentsByRoute = [];
        $documentsByRouteName = [];
        $documentsByRelativePath = [];
        $directoryIndexDocuments = [];

        foreach ($scannedDocuments as $document) {
            $resolvedDocument = $this->resolveDocumentDescriptor($document, $lookupManifest);

            $documents[] = $resolvedDocument;
            $documentsByRoute[$resolvedDocument->routePath] = $resolvedDocument;
            $documentsByRelativePath[$resolvedDocument->relativePath] = $resolvedDocument;

            if ($resolvedDocument->routeName !== null) {
                $documentsByRouteName[$resolvedDocument->routeName] = $resolvedDocument;
            }

            if ($resolvedDocument->isIndex) {
                $directoryIndexDocuments[$resolvedDocument->sourceDirectoryPath()] = $resolvedDocument;
            }

            $this->attachToNavigationTree($root, $resolvedDocument);
        }

        $root->sortRecursively();

        $visibleDocuments = $this->flattenVisibleDocuments($root);
        $visiblePositions = [];

        foreach ($visibleDocuments as $position => $document) {
            $visiblePositions[$document->relativePath] = $position;
        }

        return new ManualManifest(
            sourcePath: $sourcePath,
            signature: $this->signatureFor(
                $sourcePath,
                $documents,
                $this->markdownHelperResolver->cacheFingerprint(),
            ),
            documents: $documents,
            documentsByRoute: $documentsByRoute,
            documentsByRouteName: $documentsByRouteName,
            documentsByRelativePath: $documentsByRelativePath,
            directoryIndexDocuments: $directoryIndexDocuments,
            navigationRoot: $root,
            visibleDocuments: $visibleDocuments,
            visiblePositions: $visiblePositions,
        );
    }

    private function documentDescriptor(string $sourcePath, SplFileInfo $file): DocumentDescriptor {
        $relativePath = $this->normalizePath($file->getRelativePathname());

        return $this->cache->remember(
            $this->cache->key(
                'document-meta',
                $sourcePath,
                $relativePath,
                (string) $file->getMTime(),
            ),
            function () use ($file, $relativePath): DocumentDescriptor {
                $contents = $this->files->get($file->getPathname());
                $parsed = $this->frontMatterParser->parse($contents, $relativePath);
                $attributes = $parsed->attributes;

                [$directorySegments, $basename, $isIndex] = $this->sourcePathParts($relativePath);
                $routePath = $this->resolveRoutePath(
                    directorySegments: $directorySegments,
                    basename: $basename,
                    isIndex: $isIndex,
                    attributes: $attributes,
                    relativePath: $relativePath,
                );

                $headings = $this->contentMetadataExtractor->headings($parsed->body);
                $plainText = $this->contentMetadataExtractor->plainText($parsed->body);

                return new DocumentDescriptor(
                    absolutePath: $file->getPathname(),
                    relativePath: $relativePath,
                    directorySegments: $directorySegments,
                    basename: $basename,
                    routePath: $routePath,
                    routeName: $this->resolveRouteName($attributes),
                    isIndex: $isIndex,
                    title: $this->resolveTitle($attributes, $headings, $directorySegments, $basename, $isIndex),
                    description: $this->resolveDescription($attributes),
                    hidden: (bool) ($attributes['hidden'] ?? false),
                    order: $attributes['order'] ?? null,
                    frontMatter: $attributes,
                    headings: $headings,
                    markdown: $parsed->body,
                    plainText: $plainText,
                    fileMtime: (int) $file->getMTime(),
                );
            },
        );
    }

    private function resolveDocumentDescriptor(DocumentDescriptor $document, ManualManifest $manifest): DocumentDescriptor {
        $resolvedMarkdown = $this->markdownHelperResolver->resolve(
            markdown: $document->markdown,
            manifest: $manifest,
            documentPath: $document->relativePath,
        );
        $headings = $this->contentMetadataExtractor->headings($resolvedMarkdown);
        $plainText = $this->contentMetadataExtractor->plainText($resolvedMarkdown);

        return new DocumentDescriptor(
            absolutePath: $document->absolutePath,
            relativePath: $document->relativePath,
            directorySegments: $document->directorySegments,
            basename: $document->basename,
            routePath: $document->routePath,
            routeName: $document->routeName,
            isIndex: $document->isIndex,
            title: $this->resolveTitle(
                $document->frontMatter,
                $headings,
                $document->directorySegments,
                $document->basename,
                $document->isIndex,
            ),
            description: $document->description,
            hidden: $document->hidden,
            order: $document->order,
            frontMatter: $document->frontMatter,
            headings: $headings,
            markdown: $resolvedMarkdown,
            plainText: $plainText,
            fileMtime: $document->fileMtime,
        );
    }

    public function sourcePath(): string {
        return $this->pathResolver->sourcePath();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<string>  $directorySegments
     */
    private function resolveRoutePath(
        array $directorySegments,
        string $basename,
        bool $isIndex,
        array $attributes,
        string $relativePath,
    ): string {
        $defaultSegments = $isIndex ? $directorySegments : [...$directorySegments, $basename];

        if (array_key_exists('route', $attributes)) {
            return $this->normalizeRoutePath((string) $attributes['route']);
        }

        if (array_key_exists('slug', $attributes)) {
            $slug = trim((string) $attributes['slug'], '/');

            if ($slug === '') {
                throw new InvalidFrontMatterException(sprintf('The "slug" front matter for "%s" cannot be empty.', $relativePath));
            }

            if ($defaultSegments === []) {
                return $slug;
            }

            $defaultSegments[count($defaultSegments) - 1] = $slug;

            return implode('/', $defaultSegments);
        }

        return implode('/', $defaultSegments);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<\ServeraCloud\Manual\Data\Heading>  $headings
     * @param  list<string>  $directorySegments
     */
    private function resolveTitle(
        array $attributes,
        array $headings,
        array $directorySegments,
        string $basename,
        bool $isIndex,
    ): string {
        $configuredTitle = trim((string) ($attributes['title'] ?? ''));

        if ($configuredTitle !== '') {
            return $configuredTitle;
        }

        foreach ($headings as $heading) {
            if ($heading->level === 1) {
                return $heading->text;
            }
        }

        if ($isIndex && $directorySegments !== []) {
            return Str::headline((string) end($directorySegments));
        }

        if ($isIndex) {
            return 'Home';
        }

        return Str::headline($basename);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolveDescription(array $attributes): ?string {
        if (! array_key_exists('description', $attributes)) {
            return null;
        }

        $description = trim((string) $attributes['description']);

        return $description === '' ? null : $description;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function resolveRouteName(array $attributes): ?string {
        if (! array_key_exists('route_name', $attributes)) {
            return null;
        }

        $routeName = trim((string) $attributes['route_name']);

        return $routeName === '' ? null : $routeName;
    }

    private function attachToNavigationTree(NavigationNode $root, DocumentDescriptor $document): void {
        $node = $root;
        $path = '';

        foreach ($document->directorySegments as $segment) {
            $path = trim($path . '/' . $segment, '/');
            $node = $node->child($segment, $path);
        }

        if ($document->isIndex) {
            $node->indexDocument = $document;

            return;
        }

        $node->documents[] = $document;
    }

    /**
     * @return list<DocumentDescriptor>
     */
    private function flattenVisibleDocuments(NavigationNode $root): array {
        $visible = [];

        $walk = function (NavigationNode $node) use (&$walk, &$visible): void {
            if ($node->indexDocument !== null && ! $node->indexDocument->hidden) {
                $visible[] = $node->indexDocument;
            }

            foreach ($this->orderedEntries($node) as $entry) {
                if ($entry['type'] === 'document') {
                    $visible[] = $entry['document'];

                    continue;
                }

                $walk($entry['node']);
            }
        };

        $walk($root);

        return $visible;
    }

    /**
     * @return list<array{type: 'document', document: DocumentDescriptor}|array{type: 'node', node: NavigationNode}>
     */
    private function orderedEntries(NavigationNode $node): array {
        $entries = [];

        foreach ($node->documents as $document) {
            if ($document->hidden) {
                continue;
            }

            $entries[] = [
                'type' => 'document',
                'document' => $document,
            ];
        }

        foreach ($node->children as $child) {
            if (! $this->nodeHasVisibleContent($child)) {
                continue;
            }

            $entries[] = [
                'type' => 'node',
                'node' => $child,
            ];
        }

        usort($entries, fn(array $left, array $right): int => $this->compareEntries($left, $right));

        return $entries;
    }

    private function nodeHasVisibleContent(NavigationNode $node): bool {
        if ($node->indexDocument !== null && ! $node->indexDocument->hidden) {
            return true;
        }

        foreach ($node->documents as $document) {
            if (! $document->hidden) {
                return true;
            }
        }

        foreach ($node->children as $child) {
            if ($this->nodeHasVisibleContent($child)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array{type: 'document', document: DocumentDescriptor}|array{type: 'node', node: NavigationNode}  $left
     * @param  array{type: 'document', document: DocumentDescriptor}|array{type: 'node', node: NavigationNode}  $right
     */
    private function compareEntries(array $left, array $right): int {
        [$leftOrder, $leftLabel] = $this->entrySortMetadata($left);
        [$rightOrder, $rightLabel] = $this->entrySortMetadata($right);

        $leftWeight = $leftOrder ?? PHP_INT_MAX;
        $rightWeight = $rightOrder ?? PHP_INT_MAX;

        if ($leftWeight !== $rightWeight) {
            return $leftWeight <=> $rightWeight;
        }

        return strcasecmp($leftLabel, $rightLabel);
    }

    /**
     * @param  array{type: 'document', document: DocumentDescriptor}|array{type: 'node', node: NavigationNode}  $entry
     * @return array{0: int|null, 1: string}
     */
    private function entrySortMetadata(array $entry): array {
        if ($entry['type'] === 'document') {
            return [$entry['document']->order, $entry['document']->title];
        }

        $indexDocument = $entry['node']->indexDocument;

        return [
            $indexDocument?->order,
            $indexDocument !== null && ! $indexDocument->hidden
                ? $indexDocument->title
                : Str::headline($entry['node']->name),
        ];
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

    private function signatureFor(string $sourcePath, array $documents, string $helperFingerprint = ''): string {
        $parts = [$sourcePath, $helperFingerprint];

        foreach ($documents as $document) {
            $parts[] = implode('|', [
                $document->relativePath,
                (string) $document->fileMtime,
                $document->routePath,
                $document->routeName ?? '',
                sha1($document->markdown),
            ]);
        }

        return sha1(implode('|', $parts));
    }

    /**
     * @param  list<SplFileInfo>  $markdownFiles
     */
    private function inventorySignature(string $sourcePath, array $markdownFiles): string {
        $parts = [$sourcePath];

        foreach ($markdownFiles as $file) {
            $parts[] = implode('|', [
                $this->normalizePath($file->getRelativePathname()),
                (string) $file->getMTime(),
                $file->isReadable() ? '1' : '0',
            ]);
        }

        return sha1(implode('|', $parts));
    }

    private function manifestCacheContext(): string {
        return sha1(implode('|', [
            trim((string) $this->config->get('manual.route_prefix', 'manual'), '/'),
            (string) ((int) $this->config->get('manual.search.enabled', true)),
            trim((string) $this->config->get('manual.search.endpoint', '_manual/search.json'), '/'),
            $this->markdownHelperResolver->cacheFingerprint(),
        ]));
    }

    private function guardReservedRoute(string $routePath, string $relativePath): void {
        if (! (bool) $this->config->get('manual.search.enabled', true)) {
            return;
        }

        $reservedPath = trim((string) $this->config->get('manual.search.endpoint', '_manual/search.json'), '/');

        if ($reservedPath !== '' && $routePath === $reservedPath) {
            throw new ReservedRouteException(sprintf(
                'The document "%s" resolves to the reserved search route "%s".',
                $relativePath,
                $reservedPath,
            ));
        }
    }

    private function normalizeRoutePath(string $routePath): string {
        $trimmed = trim(str_replace('\\', '/', $routePath), '/');

        if ($trimmed === '') {
            return '';
        }

        $segments = [];

        foreach (explode('/', $trimmed) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function normalizePath(string $path): string {
        return trim(str_replace('\\', '/', $path), '/');
    }
}
