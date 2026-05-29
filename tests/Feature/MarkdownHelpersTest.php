<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Feature;

use ServeraCloud\Manual\Tests\TestCase;

final class MarkdownHelpersTest extends TestCase {
    protected function defineRoutes($router): void {
        $router->get('/login', fn() => 'login')->name('login');
        $router->get('/docs/{locale}', fn(string $locale) => $locale)->name('docs');
    }

    public function test_helpers_are_resolved_in_html_and_search_index(): void {
        $this->writeDoc('guide/install.md', <<<'MD'
---
key: guia.instalacao
---
# Instalação

Passos.
MD);
        $this->writeDoc('links.md', <<<'MD'
# Links

Acesse {{ route('login') }}.

[Login]({{ route('login') }})
[Doc Estável]({{ doc('guia.instalacao') }})
[Doc Público]({{ doc_public('guide/install') }})

## Portal {{ route('login') }}
MD);

        $loginUrl = route('login');
        $installUrl = route('manual.document', ['path' => 'guide/install']);

        $this->get('/manual/links')
            ->assertOk()
            ->assertSee($loginUrl, false)
            ->assertSee($installUrl, false)
            ->assertDontSee('{{ route(', false)
            ->assertDontSee('{{ doc(', false)
            ->assertDontSee('{{ doc_public(', false);

        $documents = collect($this->getJson('/manual/_manual/search.json')->json('documents'))
            ->keyBy('title');

        $this->assertSame('Links', $documents['Links']['title']);
        $this->assertStringContainsString($loginUrl, $documents['Links']['content']);
        $this->assertStringNotContainsString('{{ route(', $documents['Links']['content']);
        $this->assertContains('Portal ' . $loginUrl, $documents['Links']['headings']);
    }

    public function test_doc_public_helper_resolves_public_document_path(): void {
        $this->writeDoc('guide/install.md', "# Instalação\n\nPassos.");
        $this->writeDoc('links.md', <<<'MD'
# Links

[Doc Público]({{ doc_public('guide/install') }})
MD);

        $this->get('/manual/links')
            ->assertOk()
            ->assertSee('href="' . route('manual.document', ['path' => 'guide/install']) . '"', false);
    }

    public function test_helpers_inside_code_and_unknown_helpers_remain_literal(): void {
        $this->writeDoc('examples.md', <<<'MD'
# Exemplos

Acesse {{ route('login') }}.

`{{ route('login') }}`

```md
{{ doc('guia.instalacao') }}
```

{{ view('welcome') }}
MD);

        $this->get('/manual/examples')
            ->assertOk()
            ->assertSee(route('login'), false)
            ->assertSee("{{ route('login') }}", false)
            ->assertSee("{{ doc('guia.instalacao') }}", false)
            ->assertSee("{{ view('welcome') }}", false);
    }

    public function test_helpers_inside_multiline_inline_code_remain_literal(): void {
        $this->writeDoc('examples.md', <<<'MD'
# Exemplos

`{{ route('login')
}}`
MD);

        $this->get('/manual/examples')
            ->assertOk()
            ->assertSee("{{ route('login') }}", false)
            ->assertDontSee(route('login'), false);
    }

    public function test_invalid_helper_syntax_returns_explicit_error(): void {
        $this->writeDoc('broken.md', "# Broken\n\n{{ route(login) }}");

        $this->get('/manual/broken')
            ->assertStatus(500)
            ->assertSee('Invalid Markdown helper')
            ->assertSee('broken.md')
            ->assertSee('{{ route(login) }}');
    }

    public function test_unknown_named_route_returns_explicit_error(): void {
        $this->writeDoc('broken.md', "# Broken\n\n{{ route('missing.route') }}");

        $this->get('/manual/broken')
            ->assertStatus(500)
            ->assertSee('missing.route')
            ->assertSee('does not exist')
            ->assertSee('broken.md');
    }

    public function test_unknown_doc_route_name_returns_explicit_error(): void {
        $this->writeDoc('broken.md', "# Broken\n\n{{ doc('missing.doc') }}");

        $this->get('/manual/broken')
            ->assertStatus(500)
            ->assertSee('missing.doc')
            ->assertSee('key')
            ->assertSee('broken.md');
    }

    public function test_unknown_doc_public_path_returns_explicit_error(): void {
        $this->writeDoc('broken.md', "# Broken\n\n{{ doc_public('missing/path') }}");

        $this->get('/manual/broken')
            ->assertStatus(500)
            ->assertSee('missing/path')
            ->assertSee('public path')
            ->assertSee('broken.md');
    }

    public function test_page_cache_is_invalidated_when_named_route_url_changes(): void {
        $this->writeDoc('links.md', "# Links\n\n{{ route('login') }}");
        $originalLoginUrl = route('login');

        $this->get('/manual/links')
            ->assertOk()
            ->assertSee($originalLoginUrl, false);

        $loginRoute = $this->app['router']->getRoutes()->getByName('login');
        $loginRoute?->setUri('entrar');

        $this->get('/manual/links')
            ->assertOk()
            ->assertSee(url('/entrar'), false)
            ->assertDontSee($originalLoginUrl, false);
    }

    public function test_page_cache_is_invalidated_when_url_generator_default_parameters_change(): void {
        $this->app['url']->defaults(['locale' => 'en']);
        $this->writeDoc('links.md', "# Links\n\n{{ route('docs') }}");

        $this->get('/manual/links')
            ->assertOk()
            ->assertSee(url('/docs/en'), false);

        $this->app['url']->defaults(['locale' => 'pt']);

        $this->get('/manual/links')
            ->assertOk()
            ->assertSee(url('/docs/pt'), false)
            ->assertDontSee(url('/docs/en'), false);
    }

    public function test_page_cache_is_invalidated_when_url_generator_origin_changes(): void {
        $this->app['url']->useOrigin('http://docs.example.test');
        $this->writeDoc('links.md', "# Links\n\n{{ route('login') }}");

        $this->get('/manual/links')
            ->assertOk()
            ->assertSee('http://docs.example.test/login', false);

        $this->app['url']->useOrigin('http://docs2.example.test');

        $this->get('/manual/links')
            ->assertOk()
            ->assertSee('http://docs2.example.test/login', false)
            ->assertDontSee('http://docs.example.test/login', false);
    }

    public function test_page_cache_is_invalidated_when_target_document_route_changes(): void {
        $path = 'guide/install.md';
        $fullPath = $this->docsPath . '/' . $path;

        $this->writeDoc($path, <<<'MD'
---
key: guia.instalacao
---
# Instalação
MD);
        $this->writeDoc('links.md', "# Links\n\n{{ doc('guia.instalacao') }}");

        $this->get('/manual/links')
            ->assertOk()
            ->assertSee(route('manual.document', ['path' => 'guide/install']), false);

        $this->files->put($fullPath, <<<'MD'
---
key: guia.instalacao
slug: setup
---
# Instalação
MD);
        touch($fullPath, time() + 5);
        clearstatcache(true, $fullPath);

        $this->get('/manual/links')
            ->assertOk()
            ->assertSee(route('manual.document', ['path' => 'guide/setup']), false)
            ->assertDontSee(route('manual.document', ['path' => 'guide/install']), false);
    }

    public function test_page_cache_is_invalidated_when_target_document_route_name_changes(): void {
        $path = 'guide/install.md';
        $fullPath = $this->docsPath . '/' . $path;

        $this->writeDoc($path, <<<'MD'
---
key: guia.instalacao
---
# Instalação
MD);
        $this->writeDoc('links.md', "# Links\n\n{{ doc('guia.instalacao') }}");

        $this->get('/manual/links')
            ->assertOk()
            ->assertSee(route('manual.document', ['path' => 'guide/install']), false);

        $this->files->put($fullPath, <<<'MD'
---
key: guia.nova-instalacao
---
# Instalação
MD);
        touch($fullPath, time() + 5);
        clearstatcache(true, $fullPath);

        $this->get('/manual/links')
            ->assertStatus(500)
            ->assertSee('guia.instalacao')
            ->assertSee('key')
            ->assertSee('links.md');
    }
}
