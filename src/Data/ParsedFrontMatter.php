<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Data;

final readonly class ParsedFrontMatter {
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public array $attributes,
        public string $body,
    ) {
    }
}
