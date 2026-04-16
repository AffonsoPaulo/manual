<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Services;

use Closure;
use Illuminate\Support\Str;
use ServeraCloud\Manual\Data\ManualManifest;

final class SearchIndexer {
    public function build(ManualManifest $manifest, Closure $urlGenerator): array {
        $documents = [];

        foreach ($manifest->visibleDocuments as $document) {
            $documents[] = [
                'title' => $document->title,
                'description' => $document->description,
                'headings' => array_map(fn($heading): string => $heading->text, $document->headings),
                'excerpt' => Str::limit($document->description ?? $document->plainText, 220),
                'content' => $document->plainText,
                'path' => $document->routePath,
                'url' => $urlGenerator($document->routePath),
            ];
        }

        return [
            'generated_at' => now()->toIso8601String(),
            'documents' => $documents,
        ];
    }
}
