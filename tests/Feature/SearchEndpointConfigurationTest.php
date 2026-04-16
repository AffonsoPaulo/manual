<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Feature;

use ServeraCloud\Manual\Tests\TestCase;

final class SearchEndpointConfigurationTest extends TestCase {
    protected function defineEnvironment($app): void {
        parent::defineEnvironment($app);

        $app['config']->set('manual.search.endpoint', '');
    }

    public function test_page_renders_even_when_search_endpoint_is_empty(): void {
        $this->writeDoc('index.md', "# Home\n\nBem-vindo.");

        $this->get('/manual')
            ->assertOk()
            ->assertSee('Home')
            ->assertDontSee('data-manual-search', false);
    }
}
