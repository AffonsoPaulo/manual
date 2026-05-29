<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Data;

use Illuminate\Support\Str;

final readonly class ResolvedDocumentPath {
    /**
     * @param  list<string>  $directorySegments
     */
    public function __construct(
        public string $absolutePath,
        public string $relativePath,
        public array $directorySegments,
        public string $basename,
        public bool $isIndex,
    ) {
    }

    public function directoryPath(): string {
        return dirname($this->absolutePath);
    }

    public function suggestedTitle(): string {
        if ($this->isIndex && $this->directorySegments !== []) {
            $segments = $this->directorySegments;

            return Str::headline((string) end($segments));
        }

        if ($this->isIndex) {
            return 'Home';
        }

        return Str::headline($this->basename);
    }
}
