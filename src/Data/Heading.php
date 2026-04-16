<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Data;

use JsonSerializable;

final readonly class Heading implements JsonSerializable {
    public function __construct(
        public int $level,
        public string $text,
        public string $anchor,
    ) {
    }

    public function jsonSerialize(): array {
        return [
            'level' => $this->level,
            'text' => $this->text,
            'anchor' => $this->anchor,
        ];
    }
}
