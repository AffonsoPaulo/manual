<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Feature;

use ServeraCloud\Manual\Tests\TestCase;

final class DocumentationRoutesTest extends TestCase {
    public function test_default_prefix_maps_nested_markdown_routes_and_index_pages(): void {
        $this->writeDoc('index.md', "---\ntitle: Manual\n---\n# Manual\n\nPágina inicial.");
        $this->writeDoc('parceiros/index.md', "# Parceiros\n\nConteúdo.");
        $this->writeDoc('parceiros/instituicao.md', "# Instituição\n\nDetalhes.");

        $this->get('/manual')
            ->assertOk()
            ->assertSee('Manual')
            ->assertSee('Página inicial.');

        $this->get('/manual/parceiros')
            ->assertOk()
            ->assertSee('Parceiros');

        $this->get('/manual/parceiros/instituicao')
            ->assertOk()
            ->assertSee('Instituição');
    }

    public function test_slug_and_route_front_matter_are_respected(): void {
        $this->writeDoc('guide/getting-started.md', "---\nslug: comecar\n---\n# Começar\n");
        $this->writeDoc('guide/deep-dive.md', "---\nurl: parceiros/especial\n---\n# Especial\n");

        $this->get('/manual/guide/comecar')
            ->assertOk()
            ->assertSee('Começar');

        $this->get('/manual/parceiros/especial')
            ->assertOk()
            ->assertSee('Especial');
    }

    public function test_search_endpoint_excludes_hidden_documents(): void {
        $this->writeDoc('index.md', "# Home\n");
        $this->writeDoc('visible.md', "# Visível\n\nItem público.");
        $this->writeDoc('secret.md', "---\nhidden: true\n---\n# Segredo\n\nNão deve aparecer.");

        $this->getJson('/manual/_manual/search.json')
            ->assertOk()
            ->assertJsonPath('documents.0.title', 'Home');

        $payload = $this->getJson('/manual/_manual/search.json')->json();
        $titles = array_map(fn(array $document): string => $document['title'], $payload['documents']);

        $this->assertContains('Visível', $titles);
        $this->assertNotContains('Segredo', $titles);
    }

    public function test_hidden_documents_remain_accessible_by_url_but_do_not_appear_in_navigation(): void {
        $this->writeDoc('index.md', "# Home\n\nPágina inicial.");
        $this->writeDoc('secret.md', "---\nhidden: true\n---\n# Segredo\n\nConteúdo interno.");

        $this->get('/manual')
            ->assertOk()
            ->assertDontSee('>Segredo<', false);

        $this->get('/manual/secret')
            ->assertOk()
            ->assertSee('Segredo')
            ->assertSee('Conteúdo interno.');
    }

    public function test_runtime_cache_is_invalidated_when_markdown_changes(): void {
        $path = 'guide/cache.md';
        $fullPath = $this->docsPath . '/' . $path;

        $this->writeDoc($path, "# Antes\n\nTexto inicial.");

        $this->get('/manual/guide/cache')
            ->assertOk()
            ->assertSee('Texto inicial.');

        $this->files->put($fullPath, "# Depois\n\nTexto atualizado.");
        touch($fullPath, time() + 5);
        clearstatcache(true, $fullPath);

        $this->get('/manual/guide/cache')
            ->assertOk()
            ->assertSee('Texto atualizado.');
    }

    public function test_markdown_rendering_rewrites_internal_links_and_renders_rich_elements(): void {
        $this->writeDoc('guide/index.md', <<<'MD'
# Guia

- Item da lista

| Nome | Valor |
| --- | --- |
| Exemplo | 123 |

```php
echo 'ok';
```

[Instalar](./install.md)
[Avançado](./advanced/)
MD);
        $this->writeDoc('guide/install.md', "# Instalar\n\nPassos.");
        $this->writeDoc('guide/advanced/index.md', "# Avançado\n\nDetalhes.");

        $this->get('/manual/guide')
            ->assertOk()
            ->assertSee('<ul>', false)
            ->assertSee('<table>', false)
            ->assertSee('manual-code-block', false)
            ->assertSee('/manual/guide/install', false)
            ->assertSee('/manual/guide/advanced', false);
    }

    public function test_runtime_returns_explicit_error_when_manual_is_invalid(): void {
        $this->writeDoc('index.md', "# Home\n");
        $this->writeDoc('broken.md', "---\ntitle: [foo\n---\n# Broken");

        $this->get('/manual')
            ->assertStatus(500)
            ->assertSee('Erro ao carregar o manual')
            ->assertSee('Invalid YAML front matter')
            ->assertSee('broken.md');
    }

    public function test_search_endpoint_returns_explicit_json_error_when_manual_is_invalid(): void {
        $this->writeDoc('index.md', "# Home\n");
        $this->writeDoc('broken.md', "---\ntitle: [foo\n---\n# Broken");

        $response = $this->getJson('/manual/_manual/search.json')
            ->assertStatus(500);

        $message = $response->json('message');

        $this->assertIsString($message);
        $this->assertStringContainsString('Invalid YAML front matter', $message);
        $this->assertStringContainsString('broken.md', $message);
    }
}
