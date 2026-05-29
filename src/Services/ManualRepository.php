<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Services;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Route;
use ServeraCloud\Manual\Data\BreadcrumbItem;
use ServeraCloud\Manual\Data\DocumentDescriptor;
use ServeraCloud\Manual\Data\ManualManifest;
use ServeraCloud\Manual\Data\NavigationNode;
use ServeraCloud\Manual\Data\RenderedManualPage;
use ServeraCloud\Manual\Exceptions\DocumentNotFoundException;

final class ManualRepository {
    public function __construct(
        private readonly DocumentScanner $scanner,
        private readonly MarkdownRenderer $renderer,
        private readonly ManualCache $cache,
        private readonly SearchIndexer $searchIndexer,
        private readonly MarkdownHelperResolver $markdownHelperResolver,
        private readonly ConfigRepository $config,
    ) {
    }

    public function page(string $routePath): RenderedManualPage {
        $manifest = $this->scanner->scan();
        $normalizedRoute = trim($routePath, '/');
        $document = $manifest->documentForRoute($normalizedRoute);

        if ($document === null) {
            throw new DocumentNotFoundException(sprintf(
                'Manual document not found for route "%s".',
                $normalizedRoute === '' ? '/' : $normalizedRoute,
            ));
        }

        $payload = $this->cache->remember(
            $this->cache->key(
                'page',
                $this->cacheContext(),
                $manifest->signature,
                $document->relativePath,
                (string) $document->fileMtime,
            ),
            fn(): array => [
                'html' => $this->renderer->render($document, $manifest, fn(string $path): string => $this->documentUrl($path), $this->imageUrlGenerator()),
            ],
        );

        return new RenderedManualPage(
            document: $document,
            html: $payload['html'],
            navigation: $this->navigation($manifest, $document),
            breadcrumbs: $this->breadcrumbs($manifest, $document),
            previous: $this->linkPayload($manifest->previousVisibleDocument($document)),
            next: $this->linkPayload($manifest->nextVisibleDocument($document)),
            siteTitle: (string) $this->config->get('manual.site_title', 'Documentation'),
            searchEndpoint: $this->searchEndpointUrl(),
        );
    }

    public function searchIndex(): array {
        return $this->searchIndexForManifest($this->scanner->scan());
    }

    /**
     * @return array{documents: int, visible_documents: int, cached_pages: int, search_documents: int}
     */
    public function build(): array {
        $manifest = $this->scanner->scan();
        $cachedPages = 0;

        foreach ($manifest->documents as $document) {
            $this->cache->remember(
                $this->cache->key(
                    'page',
                    $this->cacheContext(),
                    $manifest->signature,
                    $document->relativePath,
                    (string) $document->fileMtime,
                ),
                fn(): array => [
                    'html' => $this->renderer->render($document, $manifest, fn(string $path): string => $this->documentUrl($path)),
                ],
            );

            $cachedPages++;
        }

        $searchDocuments = 0;

        if ((bool) $this->config->get('manual.search.enabled', true)) {
            $searchDocuments = count($this->searchIndexForManifest($manifest)['documents']);
        }

        return [
            'documents' => count($manifest->documents),
            'visible_documents' => count($manifest->visibleDocuments),
            'cached_pages' => $cachedPages,
            'search_documents' => $searchDocuments,
        ];
    }

    public function clear(): int {
        return $this->cache->clear();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function navigation(ManualManifest $manifest, DocumentDescriptor $current): array {
        $items = [];
        $root = $manifest->navigationRoot;

        if ($root->indexDocument !== null && ! $root->indexDocument->hidden) {
            $items[] = $this->documentNavigationItem($root->indexDocument, $current);
        }

        return [...$items, ...$this->sectionChildren($root, $current)];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sectionChildren(NavigationNode $node, DocumentDescriptor $current): array {
        $items = [];

        foreach ($this->orderedEntries($node) as $entry) {
            if ($entry['type'] === 'document') {
                $items[] = $this->documentNavigationItem($entry['document'], $current);

                continue;
            }

            $children = $this->sectionChildren($entry['node'], $current);
            $expanded = $this->nodeContains($entry['node'], $current);

            if ($entry['node']->indexDocument !== null && ! $entry['node']->indexDocument->hidden) {
                $items[] = $this->documentNavigationItem(
                    $entry['node']->indexDocument,
                    $current,
                    $children,
                    $expanded,
                );

                continue;
            }

            $items[] = [
                'label' => Str::headline($entry['node']->name),
                'url' => null,
                'active' => false,
                'expanded' => $expanded,
                'children' => $children,
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function documentNavigationItem(
        DocumentDescriptor $document,
        DocumentDescriptor $current,
        array $children = [],
        bool $expanded = false,
    ): array {
        return [
            'label' => $document->title,
            'url' => $this->documentUrl($document->routePath),
            'active' => $document->relativePath === $current->relativePath,
            'expanded' => $expanded,
            'children' => $children,
        ];
    }

    /**
     * @return list<BreadcrumbItem>
     */
    private function breadcrumbs(ManualManifest $manifest, DocumentDescriptor $document): array {
        $breadcrumbs = [];
        $rootIndex = $manifest->directoryIndexDocuments[''] ?? null;

        if ($rootIndex !== null) {
            if ($document->relativePath === $rootIndex->relativePath) {
                return [new BreadcrumbItem($rootIndex->title, $this->documentUrl($rootIndex->routePath))];
            }

            if (! $rootIndex->hidden) {
                $breadcrumbs[] = new BreadcrumbItem($rootIndex->title, $this->documentUrl($rootIndex->routePath));
            }
        }

        $ancestorSegments = $document->isIndex
            ? array_slice($document->directorySegments, 0, -1)
            : $document->directorySegments;

        foreach ($ancestorSegments as $index => $segment) {
            $directoryPath = implode('/', array_slice($document->directorySegments, 0, $index + 1));
            $indexDocument = $manifest->directoryIndexDocuments[$directoryPath] ?? null;

            if ($indexDocument !== null && ! $indexDocument->hidden) {
                $breadcrumbs[] = new BreadcrumbItem($indexDocument->title, $this->documentUrl($indexDocument->routePath));

                continue;
            }

            $breadcrumbs[] = new BreadcrumbItem(Str::headline($segment), null);
        }

        $breadcrumbs[] = new BreadcrumbItem($document->title, $this->documentUrl($document->routePath));

        return $breadcrumbs;
    }

    /**
     * @return array<string, string>|null
     */
    private function linkPayload(?DocumentDescriptor $document): ?array {
        if ($document === null) {
            return null;
        }

        return [
            'title' => $document->title,
            'url' => $this->documentUrl($document->routePath),
        ];
    }

    private function nodeContains(NavigationNode $node, DocumentDescriptor $current): bool {
        if ($node->indexDocument?->relativePath === $current->relativePath) {
            return true;
        }

        foreach ($node->documents as $document) {
            if ($document->relativePath === $current->relativePath) {
                return true;
            }
        }

        foreach ($node->children as $child) {
            if ($this->nodeContains($child, $current)) {
                return true;
            }
        }

        return false;
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

    private function imageUrlGenerator(): ?Closure {
        if (! (bool) $this->config->get('manual.images.enabled', true)) {
            return null;
        }

        if (! Route::has('manual.image')) {
            return null;
        }

        $imagesPathSegment = trim((string) $this->config->get('manual.images.path', '_images'), '/\\');

        return function(string $path) use ($imagesPathSegment): string {
            foreach (['@image/', '@images/'] as $alias) {
                if (str_starts_with($path, $alias)) {
                    $remainder = ltrim(substr($path, strlen($alias)), '/');
                    $path = $remainder !== '' ? $imagesPathSegment . '/' . $remainder : $imagesPathSegment;
                    break;
                }
            }
            return route('manual.image', ['path' => $path]);
        };
    }

    private function documentUrl(string $routePath): string {
        return route('manual.document', [
            'path' => $routePath === '' ? null : $routePath,
        ]);
    }

    private function searchIndexForManifest(ManualManifest $manifest): array {
        return $this->cache->remember(
            $this->cache->key('search', $this->cacheContext(), $manifest->signature),
            fn(): array => $this->searchIndexer->build($manifest, fn(string $path): string => $this->documentUrl($path)),
        );
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

    private function cacheContext(): string {
        return sha1(implode('|', [
            (string) $this->config->get('manual.route_prefix', 'manual'),
            (string) $this->config->get('manual.search.endpoint', '_manual/search.json'),
            (string) ((int) $this->config->get('manual.search.enabled', true)),
            (string) ((int) $this->config->get('manual.images.enabled', true)),
            (string) $this->config->get('manual.images.path', '_images'),
            $this->markdownHelperResolver->cacheFingerprint(),
        ]));
    }

    private function searchEndpointUrl(): ?string {
        if (! (bool) $this->config->get('manual.search.enabled', true)) {
            return null;
        }

        if (trim((string) $this->config->get('manual.search.endpoint', '_manual/search.json'), '/') === '') {
            return null;
        }

        if (! Route::has('manual.search')) {
            return null;
        }

        return route('manual.search');
    }
}
