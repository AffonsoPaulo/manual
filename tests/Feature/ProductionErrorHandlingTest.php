<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Feature;

use ServeraCloud\Manual\Tests\TestCase;

final class ProductionErrorHandlingTest extends TestCase {
    protected function defineEnvironment($app): void {
        parent::defineEnvironment($app);

        $app['config']->set('app.debug', false);
    }

    public function test_runtime_hides_internal_manual_errors_when_debug_is_disabled(): void {
        $this->writeDoc('index.md', "# Home\n");
        $this->writeDoc('broken.md', "---\ntitle: [foo\n---\n# Broken");

        $this->get('/manual')
            ->assertStatus(500)
            ->assertSee('Erro ao carregar o manual')
            ->assertSee('O manual nao pode ser carregado agora.')
            ->assertDontSee('Invalid YAML front matter')
            ->assertDontSee('broken.md');
    }

    public function test_search_endpoint_hides_internal_errors_when_debug_is_disabled(): void {
        $this->writeDoc('index.md', "# Home\n");
        $this->writeDoc('broken.md', "---\ntitle: [foo\n---\n# Broken");

        $response = $this->getJson('/manual/_manual/search.json')
            ->assertStatus(500);

        $this->assertSame(
            'O indice de busca do manual nao pode ser carregado agora.',
            $response->json('message'),
        );
    }
}
