<?php

declare(strict_types=1);

namespace ServeraCloud\Manual\Tests\Feature;

use ServeraCloud\Manual\Tests\TestCase;

final class ImageServingTest extends TestCase {
    private static string $minimalPng;

    protected function setUp(): void {
        parent::setUp();

        self::$minimalPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    }

    private function writeImage(string $relativePath, string $contents = ''): void {
        $path = $this->docsPath . '/_images/' . ltrim($relativePath, '/');
        $this->files->ensureDirectoryExists(dirname($path));
        $this->files->put($path, $contents ?: self::$minimalPng);
    }

    public function test_it_serves_a_valid_image(): void {
        $this->writeImage('screenshot.png');

        $this->get('/manual/_images/screenshot.png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_it_serves_an_image_in_a_subdirectory(): void {
        $this->writeImage('icons/arrow.png');

        $this->get('/manual/_images/icons/arrow.png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_it_returns_404_for_missing_image(): void {
        $this->get('/manual/_images/missing.png')->assertNotFound();
    }

    public function test_it_returns_404_for_path_with_dotdot_segment(): void {
        $this->writeImage('photo.png');

        $this->get('/manual/_images/../_images/photo.png')->assertNotFound();
    }

    public function test_it_returns_404_for_path_with_null_byte(): void {
        $this->get('/manual/_images/photo' . "\0" . '.png')->assertNotFound();
    }

    public function test_it_returns_404_for_extension_not_in_whitelist(): void {
        $this->get('/manual/_images/script.php')->assertNotFound();
    }

    public function test_it_returns_404_when_images_are_disabled(): void {
        $this->app['config']->set('manual.images.enabled', false);
        $this->writeImage('photo.png');

        $this->get('/manual/_images/photo.png')->assertNotFound();
    }

    public function test_image_route_uses_the_same_middleware_as_documents(): void {
        $this->writeImage('photo.png');

        $this->get('/manual/_images/photo.png')->assertOk();
    }

    public function test_controller_uses_custom_images_path_when_file_is_in_correct_location(): void {
        // The route URL prefix is always '_images' (registered at boot).
        // Changing images.path at runtime affects where the controller looks for files.
        // This verifies the controller resolves the path from config, not hardcoded.
        $this->app['config']->set('manual.images.path', '_images/brand');

        $customDir = $this->docsPath . '/_images/brand';
        $this->files->ensureDirectoryExists($customDir);
        $this->files->put($customDir . '/logo.png', self::$minimalPng);

        $this->get('/manual/_images/brand/logo.png')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_relative_image_src_is_rewritten_in_rendered_html(): void {
        $this->writeImage('screenshot.png');
        $this->writeDoc('index.md', "# Home\n\n![A screenshot](_images/screenshot.png)");

        $this->get('/manual')
            ->assertOk()
            ->assertSee('src="' . route('manual.image', ['path' => '_images/screenshot.png']) . '"', false);
    }

    public function test_relative_image_src_from_subdirectory_is_rewritten_correctly(): void {
        $this->writeImage('screenshot.png');
        $this->writeDoc('guide/page.md', "# Page\n\n![A screenshot](../_images/screenshot.png)");

        $this->get('/manual/guide/page')
            ->assertOk()
            ->assertSee('src="' . route('manual.image', ['path' => '_images/screenshot.png']) . '"', false);
    }

    public function test_external_image_src_is_not_rewritten(): void {
        $this->writeDoc('index.md', "# Home\n\n![Logo](https://example.com/logo.png)");

        $this->get('/manual')
            ->assertOk()
            ->assertSee('src="https://example.com/logo.png"', false);
    }

    public function test_absolute_image_src_is_not_rewritten(): void {
        $this->writeDoc('index.md', "# Home\n\n![Logo](/public/logo.png)");

        $this->get('/manual')
            ->assertOk()
            ->assertSee('src="/public/logo.png"', false);
    }

    public function test_image_src_is_not_rewritten_when_images_disabled(): void {
        $this->app['config']->set('manual.images.enabled', false);
        $this->writeDoc('index.md', "# Home\n\n![A screenshot](_images/screenshot.png)");

        $this->get('/manual')
            ->assertOk()
            ->assertSee('src="_images/screenshot.png"', false);
    }

    public function test_at_image_alias_resolves_from_root_document(): void {
        $this->writeImage('logo.png');
        $this->writeDoc('index.md', "# Home\n\n![Logo](@image/logo.png)");

        $this->get('/manual')
            ->assertOk()
            ->assertSee('src="' . route('manual.image', ['path' => '_images/logo.png']) . '"', false);
    }

    public function test_at_image_alias_resolves_from_subdirectory(): void {
        $this->writeImage('logo.png');
        $this->writeDoc('getting-started/deep/page.md', "# Deep\n\n![Logo](@image/logo.png)");

        $this->get('/manual/getting-started/deep/page')
            ->assertOk()
            ->assertSee('src="' . route('manual.image', ['path' => '_images/logo.png']) . '"', false);
    }

    public function test_at_image_alias_supports_subdirectory_images(): void {
        $this->writeImage('icons/arrow.png');
        $this->writeDoc('index.md', "# Home\n\n![Arrow](@image/icons/arrow.png)");

        $this->get('/manual')
            ->assertOk()
            ->assertSee('src="' . route('manual.image', ['path' => '_images/icons/arrow.png']) . '"', false);
    }

    public function test_at_image_alias_not_rewritten_when_images_disabled(): void {
        $this->app['config']->set('manual.images.enabled', false);
        $this->writeDoc('index.md', "# Home\n\n![Logo](@image/logo.png)");

        $this->get('/manual')
            ->assertOk()
            ->assertSee('src="@image/logo.png"', false);
    }

    public function test_at_image_alias_with_custom_images_path(): void {
        $this->app['config']->set('manual.images.path', 'assets/imgs');

        $customDir = $this->docsPath . '/assets/imgs';
        $this->files->ensureDirectoryExists($customDir);
        $this->files->put($customDir . '/logo.png', self::$minimalPng);

        $this->writeDoc('index.md', "# Home\n\n![Logo](@image/logo.png)");

        $this->get('/manual')
            ->assertOk()
            ->assertSee('src="' . route('manual.image', ['path' => 'assets/imgs/logo.png']) . '"', false);
    }

    public function test_at_image_alias_with_empty_remainder_is_not_rewritten(): void {
        $this->writeDoc('index.md', "# Home\n\n![Empty](@image/)");

        $this->get('/manual')
            ->assertOk()
            ->assertSee('src="@image/"', false);
    }
}
