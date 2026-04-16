<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Data;

use Illuminate\Support\Str;

final class NavigationNode {
    public ?DocumentDescriptor $indexDocument = null;

    /** @var array<string, self> */
    public array $children = [];

    /** @var list<DocumentDescriptor> */
    public array $documents = [];

    public function __construct(
        public string $name,
        public string $path,
    ) {
    }

    public function child(string $name, string $path): self {
        return $this->children[$name] ??= new self($name, $path);
    }

    public function sortRecursively(): void {
        usort($this->documents, function (DocumentDescriptor $left, DocumentDescriptor $right): int {
            return $this->compare($left->order, $left->title, $right->order, $right->title);
        });

        uasort($this->children, function (self $left, self $right): int {
            $leftTitle = $left->indexDocument?->title ?? Str::headline($left->name);
            $rightTitle = $right->indexDocument?->title ?? Str::headline($right->name);

            return $this->compare(
                $left->indexDocument?->order,
                $leftTitle,
                $right->indexDocument?->order,
                $rightTitle,
            );
        });

        foreach ($this->children as $child) {
            $child->sortRecursively();
        }
    }

    private function compare(?int $leftOrder, string $leftTitle, ?int $rightOrder, string $rightTitle): int {
        $leftWeight = $leftOrder ?? PHP_INT_MAX;
        $rightWeight = $rightOrder ?? PHP_INT_MAX;

        if ($leftWeight !== $rightWeight) {
            return $leftWeight <=> $rightWeight;
        }

        return strcasecmp($leftTitle, $rightTitle);
    }
}
