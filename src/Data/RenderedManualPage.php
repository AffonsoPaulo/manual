<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Data;

final readonly class RenderedManualPage {
    /**
     * @param  list<array<string, mixed>>  $navigation
     * @param  list<BreadcrumbItem>  $breadcrumbs
     * @param  array<string, string>|null  $previous
     * @param  array<string, string>|null  $next
     */
    public function __construct(
        public DocumentDescriptor $document,
        public string $html,
        public array $navigation,
        public array $breadcrumbs,
        public ?array $previous,
        public ?array $next,
        public string $siteTitle,
        public ?string $searchEndpoint,
    ) {
    }
}
