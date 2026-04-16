<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Data;

use Illuminate\Support\Str;
use JsonSerializable;

final readonly class DocumentDescriptor implements JsonSerializable {
    /**
     * @param  list<string>  $directorySegments
     * @param  array<string, mixed>  $frontMatter
     * @param  list<Heading>  $headings
     */
    public function __construct(
        public string $absolutePath,
        public string $relativePath,
        public array $directorySegments,
        public string $basename,
        public string $routePath,
        public ?string $routeName,
        public bool $isIndex,
        public string $title,
        public ?string $description,
        public bool $hidden,
        public ?int $order,
        public array $frontMatter,
        public array $headings,
        public string $markdown,
        public string $plainText,
        public int $fileMtime,
    ) {
    }

    public function sourceDirectoryPath(): string {
        return implode('/', $this->directorySegments);
    }

    public function sourcePathSegments(): array {
        if ($this->isIndex) {
            return $this->directorySegments;
        }

        return [...$this->directorySegments, $this->basename];
    }

    public function fallbackLabel(): string {
        if ($this->isIndex && $this->directorySegments !== []) {
            return Str::headline((string) end($this->directorySegments));
        }

        if ($this->isIndex) {
            return 'Home';
        }

        return Str::headline($this->basename);
    }

    public function isHomePage(): bool {
        return $this->routePath === '';
    }

    public function jsonSerialize(): array {
        return [
            'relative_path' => $this->relativePath,
            'route_path' => $this->routePath,
            'route_name' => $this->routeName,
            'title' => $this->title,
            'description' => $this->description,
            'hidden' => $this->hidden,
            'order' => $this->order,
            'headings' => $this->headings,
        ];
    }
}
