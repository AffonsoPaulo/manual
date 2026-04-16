<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Data;

use JsonSerializable;

final readonly class BreadcrumbItem implements JsonSerializable {
    public function __construct(
        public string $title,
        public ?string $url,
    ) {
    }

    public function jsonSerialize(): array {
        return [
            'title' => $this->title,
            'url' => $this->url,
        ];
    }
}
