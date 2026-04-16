<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Feature;

use ServeraCloud\Manual\Tests\TestCase;

final class EmptyPrefixRoutePrecedenceTest extends TestCase {
    protected function defineEnvironment($app): void {
        parent::defineEnvironment($app);

        $app['config']->set('manual.route_prefix', '');
    }

    protected function defineRoutes($router): void {
        $router->get('/parceiros/instituicao', fn() => 'rota-app');
    }

    public function test_application_routes_keep_precedence_when_prefix_is_empty(): void {
        $this->writeDoc('parceiros/instituicao.md', "# Instituição\n\nManual.");

        $this->get('/parceiros/instituicao')
            ->assertOk()
            ->assertSee('rota-app')
            ->assertDontSee('Manual.');
    }
}
