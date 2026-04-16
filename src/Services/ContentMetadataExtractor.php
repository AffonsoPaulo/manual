<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Services;

use Illuminate\Support\Str;
use ServeraCloud\Manual\Data\Heading;

final class ContentMetadataExtractor {
    /**
     * @return list<Heading>
     */
    public function headings(string $markdown): array {
        $headings = [];
        $anchorCounts = [];

        preg_match_all('/^(#{1,6})[ \t]+(.+?)\s*#*\s*$/m', $this->withoutCodeBlocks($markdown), $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $text = $this->inlineText($match[2]);

            if ($text === '') {
                continue;
            }

            $baseAnchor = Str::slug($text) ?: 'section';
            $anchorCounts[$baseAnchor] = ($anchorCounts[$baseAnchor] ?? 0) + 1;
            $anchor = $anchorCounts[$baseAnchor] === 1
                ? $baseAnchor
                : $baseAnchor . '-' . $anchorCounts[$baseAnchor];

            $headings[] = new Heading(
                level: strlen($match[1]),
                text: $text,
                anchor: $anchor,
            );
        }

        return $headings;
    }

    public function firstHeading(string $markdown): ?string {
        foreach ($this->headings($markdown) as $heading) {
            if ($heading->level === 1) {
                return $heading->text;
            }
        }

        return null;
    }

    public function plainText(string $markdown): string {
        $text = $this->withoutCodeBlocks($markdown);
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '$1', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text) ?? $text;
        $text = preg_replace('/<[^>]+>/', ' ', $text) ?? $text;
        $text = preg_replace('/^[>\-\*\+\d\.\|\s`#]+/m', '', $text) ?? $text;
        $text = str_replace(['*', '_', '`', '|'], ' ', $text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function withoutCodeBlocks(string $markdown): string {
        $text = preg_replace('/```.*?```/s', ' ', $markdown) ?? $markdown;

        return preg_replace('/~~~.*?~~~/s', ' ', $text) ?? $text;
    }

    private function inlineText(string $text): string {
        $text = preg_replace('/!\[([^\]]*)\]\([^)]+\)/', '$1', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text) ?? $text;
        $text = preg_replace('/[*_`~]+/', '', $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }
}
