<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Services;

use Closure;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Highlight\Highlighter;
use Illuminate\Support\Str;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use ServeraCloud\Manual\Data\DocumentDescriptor;
use ServeraCloud\Manual\Data\ManualManifest;
use Throwable;

final class MarkdownRenderer {
    private readonly GithubFlavoredMarkdownConverter $converter;

    private readonly Highlighter $highlighter;

    public function __construct() {
        $this->converter = new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $this->highlighter = new Highlighter();
    }

    public function render(DocumentDescriptor $document, ManualManifest $manifest, Closure $urlGenerator): string {
        $html = (string) $this->converter->convert($document->markdown);

        return $this->postProcessHtml($html, $document, $manifest, $urlGenerator);
    }

    private function postProcessHtml(string $html, DocumentDescriptor $document, ManualManifest $manifest, Closure $urlGenerator): string {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $dom->loadHTML(
            '<?xml encoding="utf-8" ?><div id="manual-root">' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        /** @var DOMElement|null $container */
        $container = $dom->getElementsByTagName('div')->item(0);

        if ($container === null) {
            return $html;
        }

        $this->addHeadingIds($dom);
        $this->highlightCodeBlocks($dom);
        $this->rewriteInternalLinks($dom, $document, $manifest, $urlGenerator);

        return $this->innerHtml($container);
    }

    private function addHeadingIds(DOMDocument $dom): void {
        $counts = [];

        foreach (['h1', 'h2', 'h3', 'h4', 'h5', 'h6'] as $tagName) {
            /** @var DOMElement $heading */
            foreach ($dom->getElementsByTagName($tagName) as $heading) {
                if ($heading->hasAttribute('id')) {
                    continue;
                }

                $baseId = Str::slug(trim($heading->textContent)) ?: 'section';
                $counts[$baseId] = ($counts[$baseId] ?? 0) + 1;
                $id = $counts[$baseId] === 1 ? $baseId : $baseId . '-' . $counts[$baseId];
                $heading->setAttribute('id', $id);
            }
        }
    }

    private function highlightCodeBlocks(DOMDocument $dom): void {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//pre/code');

        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $code = html_entity_decode($node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $language = $this->languageFromClass($node->getAttribute('class'));

            try {
                $result = $language !== null
                    ? $this->highlighter->highlight($language, $code)
                    : $this->highlighter->highlightAuto($code);

                while ($node->firstChild !== null) {
                    $node->removeChild($node->firstChild);
                }

                $fragment = $dom->createDocumentFragment();
                $fragment->appendXML($result->value);
                $node->appendChild($fragment);

                $classes = array_filter([
                    $node->getAttribute('class'),
                    'hljs',
                    $result->language !== null ? 'language-' . $result->language : null,
                ]);

                $node->setAttribute('class', implode(' ', array_unique($classes)));
            } catch (Throwable) {
                $classes = array_filter([$node->getAttribute('class'), 'hljs']);
                $node->setAttribute('class', implode(' ', array_unique($classes)));
            }

            if ($node->parentNode instanceof DOMElement) {
                $classes = array_filter([$node->parentNode->getAttribute('class'), 'manual-code-block']);
                $node->parentNode->setAttribute('class', implode(' ', array_unique($classes)));
            }
        }
    }

    private function rewriteInternalLinks(DOMDocument $dom, DocumentDescriptor $document, ManualManifest $manifest, Closure $urlGenerator): void {
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//a[@href]');

        if ($nodes === false) {
            return;
        }

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $href = trim($node->getAttribute('href'));

            if ($href === '' || str_starts_with($href, '#') || preg_match('/^[a-z][a-z0-9+\-.]*:/i', $href)) {
                continue;
            }

            $parts = parse_url($href);

            if ($parts === false) {
                continue;
            }

            $targetPath = $parts['path'] ?? '';

            if ($targetPath === '') {
                continue;
            }

            $targetDocument = $this->resolveLinkedDocument($manifest, $document, $targetPath, $href);

            if ($targetDocument === null) {
                continue;
            }

            $newHref = $urlGenerator($targetDocument->routePath);

            if (isset($parts['query']) && $parts['query'] !== '') {
                $newHref .= '?' . $parts['query'];
            }

            if (isset($parts['fragment']) && $parts['fragment'] !== '') {
                $newHref .= '#' . $parts['fragment'];
            }

            $node->setAttribute('href', $newHref);
        }
    }

    private function resolveLinkedDocument(ManualManifest $manifest, DocumentDescriptor $document, string $targetPath, string $href): ?DocumentDescriptor {
        if (str_ends_with(strtolower($targetPath), '.md')) {
            $relativePath = $this->resolveRelativeMarkdownPath($document->relativePath, $targetPath);

            return $relativePath === null ? null : $manifest->documentForRelativePath($relativePath);
        }

        if (str_starts_with($href, '/')) {
            return null;
        }

        if ($this->hasNonMarkdownExtension($targetPath)) {
            return null;
        }

        $sourceTarget = $this->resolveRelativeSourceTarget($document->relativePath, $targetPath);

        if ($sourceTarget === null) {
            return $manifest->directoryIndexDocuments[''] ?? null;
        }

        return $manifest->documentForRelativePath($sourceTarget . '.md')
            ?? ($manifest->directoryIndexDocuments[$sourceTarget] ?? null);
    }

    private function resolveRelativeMarkdownPath(string $currentRelativePath, string $targetPath): ?string {
        $baseDirectory = dirname($currentRelativePath);
        $segments = [];

        if (! str_starts_with($targetPath, '/')) {
            $prefix = $baseDirectory === '.' ? [] : explode('/', str_replace('\\', '/', $baseDirectory));
            $segments = array_values(array_filter($prefix, fn(string $segment): bool => $segment !== ''));
        }

        foreach (explode('/', trim(str_replace('\\', '/', $targetPath), '/')) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        $normalized = implode('/', $segments);

        return $normalized === '' ? null : $normalized;
    }

    private function resolveRelativeSourceTarget(string $currentRelativePath, string $targetPath): ?string {
        $baseDirectory = dirname($currentRelativePath);
        $segments = $baseDirectory === '.'
            ? []
            : explode('/', str_replace('\\', '/', $baseDirectory));

        foreach (explode('/', trim(str_replace('\\', '/', $targetPath), '/')) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($segments);

                continue;
            }

            $segments[] = $segment;
        }

        $normalized = trim(implode('/', array_values(array_filter($segments, fn(string $segment): bool => $segment !== ''))), '/');

        return $normalized === '' ? null : $normalized;
    }

    private function languageFromClass(string $classList): ?string {
        foreach (preg_split('/\s+/', trim($classList)) ?: [] as $className) {
            if (str_starts_with($className, 'language-')) {
                return substr($className, 9);
            }

            if (str_starts_with($className, 'lang-')) {
                return substr($className, 5);
            }
        }

        return null;
    }

    private function hasNonMarkdownExtension(string $targetPath): bool {
        $extension = pathinfo($targetPath, PATHINFO_EXTENSION);

        return $extension !== '' && strtolower($extension) !== 'md';
    }

    private function innerHtml(DOMNode $node): string {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument?->saveHTML($child) ?? '';
        }

        return $html;
    }
}
