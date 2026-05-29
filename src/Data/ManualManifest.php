<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Data;

final readonly class ManualManifest {
    /**
     * @param  list<DocumentDescriptor>  $documents
     * @param  array<string, DocumentDescriptor>  $documentsByRoute
     * @param  array<string, DocumentDescriptor>  $documentsByKey
     * @param  array<string, DocumentDescriptor>  $documentsByRelativePath
     * @param  array<string, DocumentDescriptor>  $directoryIndexDocuments
     * @param  list<DocumentDescriptor>  $visibleDocuments
     * @param  array<string, int>  $visiblePositions
     */
    public function __construct(
        public string $sourcePath,
        public string $signature,
        public array $documents,
        public array $documentsByRoute,
        public array $documentsByKey,
        public array $documentsByRelativePath,
        public array $directoryIndexDocuments,
        public NavigationNode $navigationRoot,
        public array $visibleDocuments,
        public array $visiblePositions,
    ) {
    }

    public function documentForRoute(string $routePath): ?DocumentDescriptor {
        return $this->documentsByRoute[$routePath] ?? null;
    }

    public function documentForPublicPath(string $routePath): ?DocumentDescriptor {
        return $this->documentsByRoute[$routePath] ?? null;
    }

    public function documentForKey(string $key): ?DocumentDescriptor {
        return $this->documentsByKey[$key] ?? null;
    }

    public function documentForRelativePath(string $relativePath): ?DocumentDescriptor {
        return $this->documentsByRelativePath[$relativePath] ?? null;
    }

    public function previousVisibleDocument(DocumentDescriptor $document): ?DocumentDescriptor {
        $position = $this->visiblePositions[$document->relativePath] ?? null;

        if ($position === null || $position === 0) {
            return null;
        }

        return $this->visibleDocuments[$position - 1] ?? null;
    }

    public function nextVisibleDocument(DocumentDescriptor $document): ?DocumentDescriptor {
        $position = $this->visiblePositions[$document->relativePath] ?? null;

        if ($position === null) {
            return null;
        }

        return $this->visibleDocuments[$position + 1] ?? null;
    }
}
