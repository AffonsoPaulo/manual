<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SearchScriptSecurityTest extends TestCase {
    public function test_search_results_are_rendered_without_html_interpolation(): void {
        $script = file_get_contents(dirname(__DIR__, 2) . '/resources/dist/manual.js');

        $this->assertIsString($script);
        $this->assertStringContainsString("title.textContent = item.title || '';", $script);
        $this->assertStringContainsString("excerpt.textContent = item.excerpt || '';", $script);
        $this->assertStringNotContainsString("'<a href=\"' + item.url + '\"><strong>' + item.title + '</strong>", $script);
    }
}
