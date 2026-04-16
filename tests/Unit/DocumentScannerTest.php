<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Unit;

use ServeraCloud\Manual\Exceptions\DuplicateRouteException;
use ServeraCloud\Manual\Exceptions\DuplicateRouteNameException;
use ServeraCloud\Manual\Exceptions\InvalidFrontMatterException;
use ServeraCloud\Manual\Exceptions\UnreadableDocumentException;
use ServeraCloud\Manual\Services\DocumentScanner;
use ServeraCloud\Manual\Tests\TestCase;

final class DocumentScannerTest extends TestCase {
    public function test_it_throws_for_invalid_yaml_front_matter(): void {
        $this->writeDoc('broken.md', "---\ntitle: [foo\n---\n# Broken");

        $this->expectException(InvalidFrontMatterException::class);

        app(DocumentScanner::class)->scan();
    }

    public function test_it_throws_for_duplicate_resolved_routes(): void {
        $this->writeDoc('guide/intro.md', "# Intro");
        $this->writeDoc('guide/other.md', "---\nroute: guide/intro\n---\n# Outro");

        $this->expectException(DuplicateRouteException::class);

        app(DocumentScanner::class)->scan();
    }

    public function test_it_throws_for_duplicate_route_names(): void {
        $this->writeDoc('guide/intro.md', "---\nroute_name: guia.intro\n---\n# Intro");
        $this->writeDoc('guide/other.md', "---\nroute_name: guia.intro\n---\n# Outro");

        $this->expectException(DuplicateRouteNameException::class);

        app(DocumentScanner::class)->scan();
    }

    public function test_it_validates_route_name_front_matter(): void {
        $this->writeDoc('guide/intro.md', "---\nroute_name:\n  nested: true\n---\n# Intro");

        $this->expectException(InvalidFrontMatterException::class);

        app(DocumentScanner::class)->scan();
    }

    public function test_it_extracts_title_from_first_heading_when_front_matter_is_missing(): void {
        $this->writeDoc('guide/index.md', "# Guia Principal\n\nTexto.");

        $manifest = app(DocumentScanner::class)->scan();
        $document = $manifest->documentForRoute('guide');

        $this->assertNotNull($document);
        $this->assertSame('Guia Principal', $document->title);
    }

    public function test_it_throws_for_unreadable_documents(): void {
        $path = $this->docsPath . '/secret.md';
        $this->writeDoc('secret.md', "# Segredo");

        if (! chmod($path, 0000)) {
            $this->markTestSkipped('Could not change file permissions for unreadable file test.');
        }

        clearstatcache(true, $path);

        if ((new \SplFileInfo($path))->isReadable()) {
            chmod($path, 0644);
            $this->markTestSkipped('Filesystem keeps the file readable for the current user.');
        }

        $this->expectException(UnreadableDocumentException::class);

        try {
            app(DocumentScanner::class)->scan();
        } finally {
            chmod($path, 0644);
        }
    }
}
